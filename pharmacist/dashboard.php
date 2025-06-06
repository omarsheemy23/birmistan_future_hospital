<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_error.log');

// وظيفة تسجيل الأخطاء
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

// بدء الجلسة
session_start();

try {
    // التحقق من وجود الملفات المطلوبة
    if (!file_exists('../config/database.php')) {
        throw new Exception('خطأ: ملف قاعدة البيانات غير موجود');
    }

    if (!file_exists('../includes/functions.php')) {
        throw new Exception('خطأ: ملف الدوال غير موجود');
    }

    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // التحقق من تسجيل دخول الصيدلي
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
        header("Location: ../login.php");
        exit();
    }

    // إنشاء اتصال بقاعدة البيانات
    $database = new Database();
    $conn = $database->getConnection();

    // جلب معلومات الصيدلي
    $stmt = $conn->prepare("SELECT * FROM pharmacists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);

    // الإحصائيات
    $stats_query = "SELECT 
                (SELECT COUNT(*) FROM prescriptions WHERE status = 'pending') as pending_prescriptions,
                (SELECT COUNT(*) FROM prescriptions WHERE status = 'dispensed') as dispensed_prescriptions,
                (SELECT COUNT(*) FROM medicines WHERE quantity_in_stock < 20) as low_stock_medicines,
                (SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)) as expiring_medicines";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // فحص إذا كان جدول prescription_medicines موجود
    $check_table = $conn->query("SHOW TABLES LIKE 'prescription_medicines'");
    if ($check_table->rowCount() == 0) {
        // الجدول غير موجود - نسجل رسالة ونعيد التوجيه
        logError("Table 'prescription_medicines' not found. Redirecting to fallback dashboard.");
        header("Location: dashboard_fallback.php");
        exit();
    }

    // الروشتات الأخيرة
    $recent_prescriptions_query = "
        SELECT p.id, p.prescription_date, p.status,
            pt.first_name as patient_first_name, pt.last_name as patient_last_name,
            d.first_name as doctor_first_name, d.last_name as doctor_last_name,
            COUNT(pm.id) as medicine_count
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.id
        JOIN doctors d ON p.doctor_id = d.id
        LEFT JOIN prescription_medicines pm ON p.id = pm.prescription_id
        GROUP BY p.id
        ORDER BY p.prescription_date DESC
        LIMIT 5";
    $recent_stmt = $conn->prepare($recent_prescriptions_query);
    $recent_stmt->execute();
    $recent_prescriptions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // الأدوية منخفضة المخزون
    $low_stock_query = "
        SELECT * FROM medicines 
        WHERE quantity_in_stock < 20 
        ORDER BY quantity_in_stock ASC
        LIMIT 5";
    $low_stock_stmt = $conn->prepare($low_stock_query);
    $low_stock_stmt->execute();
    $low_stock_medicines = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

    // الأدوية التي توشك على انتهاء الصلاحية
    $expiring_query = "
        SELECT * FROM medicines 
        WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY expiry_date ASC
        LIMIT 5";
    $expiring_stmt = $conn->prepare($expiring_query);
    $expiring_stmt->execute();
    $expiring_medicines = $expiring_stmt->fetchAll(PDO::FETCH_ASSOC);

    // تضمين ملف الهيدر
    include_once 'header.php';
} catch (PDOException $e) {
    // نسجل الخطأ
    logError("Database Error: " . $e->getMessage());
    
    // إذا كان الخطأ يتعلق بجدول مفقود
    if (strpos($e->getMessage(), "Table 'hospital_db.prescription_medicines' doesn't exist") !== false) {
        // إعادة توجيه إلى النسخة الاحتياطية
        header("Location: dashboard_fallback.php");
        exit();
    }
    
    // عرض رسالة الخطأ وإعادة التوجيه
    echo '<div style="direction: rtl; text-align: right; margin: 20px; font-family: Arial, sans-serif;">';
    echo '<h2 style="color: #d9534f;">خطأ في قاعدة البيانات</h2>';
    echo '<p>حدث خطأ أثناء الاتصال بقاعدة البيانات. سيتم توجيهك إلى النسخة الاحتياطية...</p>';
    echo '<p><strong>رسالة الخطأ:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>جاري التحويل... <a href="dashboard_fallback.php">انقر هنا</a> إذا لم يتم تحويلك تلقائيًا.</p>';
    echo '</div>';
    echo '<script>setTimeout(function(){ window.location = "dashboard_fallback.php"; }, 3000);</script>';
    exit();
} catch (Exception $e) {
    // نسجل الخطأ
    logError("General Error: " . $e->getMessage());
    
    // عرض رسالة الخطأ وإعادة التوجيه
    echo '<div style="direction: rtl; text-align: right; margin: 20px; font-family: Arial, sans-serif;">';
    echo '<h2 style="color: #d9534f;">خطأ في النظام</h2>';
    echo '<p>حدث خطأ أثناء تحميل لوحة التحكم. سيتم توجيهك إلى النسخة الاحتياطية...</p>';
    echo '<p><strong>رسالة الخطأ:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>جاري التحويل... <a href="dashboard_fallback.php">انقر هنا</a> إذا لم يتم تحويلك تلقائيًا.</p>';
    echo '</div>';
    echo '<script>setTimeout(function(){ window.location = "dashboard_fallback.php"; }, 3000);</script>';
    exit();
}
?>

<div class="container mt-4">
    <!-- ترحيب وملخص -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($pharmacist['first_name'] ?? '') . ' ' . htmlspecialchars($pharmacist['last_name'] ?? ''); ?></h4>
                    <p class="card-text">لوحة تحكم نظام الصيدلية تعرض ملخصاً للأنشطة والمهام.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $stats['pending_prescriptions']; ?></h3>
                    <p class="card-text">روشتات بانتظار الصرف</p>
                    <a href="dispense_medicine.php" class="btn btn-light btn-sm mt-2">عرض</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $stats['dispensed_prescriptions']; ?></h3>
                    <p class="card-text">روشتات تم صرفها</p>
                    <a href="view_prescriptions.php" class="btn btn-light btn-sm mt-2">عرض</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $stats['low_stock_medicines']; ?></h3>
                    <p class="card-text">أدوية منخفضة المخزون</p>
                    <a href="inventory.php?filter_stock=low" class="btn btn-dark btn-sm mt-2">عرض</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $stats['expiring_medicines']; ?></h3>
                    <p class="card-text">أدوية توشك على الانتهاء</p>
                    <a href="inventory.php?filter_stock=expiring" class="btn btn-light btn-sm mt-2">عرض</a>
                </div>
            </div>
        </div>
    </div>

    <!-- المهام السريعة -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tasks me-2"></i>المهام السريعة
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="dispense_medicine.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-pills me-2"></i>صرف دواء
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="inventory.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-boxes me-2"></i>إدارة المخزون
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="view_prescriptions.php" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-clipboard-list me-2"></i>عرض الروشتات
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-chart-bar me-2"></i>التقارير
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- الروشتات الأخيرة -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-2"></i>أحدث الروشتات
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_prescriptions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رقم</th>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>الحالة</th>
                                        <th>الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_prescriptions as $prescription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['id']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                            <td>
                                                <?php if ($prescription['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">قيد الانتظار</span>
                                                <?php elseif ($prescription['status'] === 'dispensed'): ?>
                                                    <span class="badge bg-success">تم الصرف</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($prescription['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="dispense_medicine.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> عرض
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="view_prescriptions.php" class="btn btn-outline-primary">عرض الكل</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>لا توجد روشتات حديثة
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- الأدوية منخفضة المخزون -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-2"></i>الأدوية منخفضة المخزون
                </div>
                <div class="card-body">
                    <?php if (!empty($low_stock_medicines)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم الدواء</th>
                                        <th>النوع</th>
                                        <th>الكمية المتبقية</th>
                                        <th>الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_medicines as $medicine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['type']); ?></td>
                                            <td>
                                                <?php if ($medicine['quantity_in_stock'] == 0): ?>
                                                    <span class="badge bg-danger">نفذ</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><?php echo $medicine['quantity_in_stock']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updateQuantityModal" 
                                                    data-medicine-id="<?php echo $medicine['id']; ?>"
                                                    data-medicine-name="<?php echo htmlspecialchars($medicine['name']); ?>"
                                                    data-current-quantity="<?php echo $medicine['quantity_in_stock']; ?>">
                                                    <i class="fas fa-edit me-1"></i> تحديث
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="inventory.php?filter_stock=low" class="btn btn-outline-warning">عرض الكل</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>لا توجد أدوية منخفضة المخزون
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- الأدوية التي توشك على انتهاء الصلاحية -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-times me-2"></i>الأدوية التي توشك على انتهاء الصلاحية
                </div>
                <div class="card-body">
                    <?php if (!empty($expiring_medicines)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم الدواء</th>
                                        <th>النوع</th>
                                        <th>تاريخ انتهاء الصلاحية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiring_medicines as $medicine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['type']); ?></td>
                                            <td>
                                                <?php 
                                                $expiry_date = new DateTime($medicine['expiry_date']);
                                                $current_date = new DateTime();
                                                $diff = $current_date->diff($expiry_date);
                                                $days_until_expiry = $expiry_date > $current_date ? $diff->days : -$diff->days;
                                                
                                                if ($days_until_expiry < 30): 
                                                ?>
                                                    <span class="badge bg-danger"><?php echo htmlspecialchars($medicine['expiry_date']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><?php echo htmlspecialchars($medicine['expiry_date']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="inventory.php?filter_stock=expiring" class="btn btn-outline-danger">عرض الكل</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>لا توجد أدوية توشك على انتهاء الصلاحية
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تحديث كمية الدواء -->
<div class="modal fade" id="updateQuantityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تحديث كمية الدواء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="inventory.php" id="updateQuantityForm">
                    <input type="hidden" id="update_medicine_id" name="medicine_id">
                    
                    <div class="mb-3">
                        <label class="form-label">اسم الدواء</label>
                        <input type="text" class="form-control" id="medicine_name_display" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_quantity" class="form-label">الكمية الحالية</label>
                        <input type="number" class="form-control" id="current_quantity" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_quantity" class="form-label">الكمية الجديدة *</label>
                        <input type="number" class="form-control" id="new_quantity" name="new_quantity" min="0" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="update_quantity" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> تحديث
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // تهيئة مودال تحديث الكمية
    document.addEventListener('DOMContentLoaded', function() {
        const updateQuantityModal = document.getElementById('updateQuantityModal');
        if (updateQuantityModal) {
            updateQuantityModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const medicineId = button.getAttribute('data-medicine-id');
                const medicineName = button.getAttribute('data-medicine-name');
                const currentQuantity = button.getAttribute('data-current-quantity');
                
                updateQuantityModal.querySelector('#update_medicine_id').value = medicineId;
                updateQuantityModal.querySelector('#medicine_name_display').value = medicineName;
                updateQuantityModal.querySelector('#current_quantity').value = currentQuantity;
                updateQuantityModal.querySelector('#new_quantity').value = currentQuantity;
            });
        }
    });
</script>

<?php
// تضمين ملف الفوتر إذا كان موجودًا
if (file_exists('footer.php')) {
    include_once 'footer.php';
}
?>