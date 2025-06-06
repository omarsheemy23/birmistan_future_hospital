<?php
// تفعيل عرض الأخطاء بشكل كامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_dispense_error.log');

// وظيفة تسجيل الأخطاء
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

// نسجل دخول الصفحة للتشخيص
logError("Accessing dispense_medicine.php - " . (isset($_GET['prescription_id']) ? "Prescription ID: " . $_GET['prescription_id'] : "No prescription ID"));

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // التحقق من تسجيل دخول الصيدلي
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
        header("Location: ../login.php");
        exit();
    }

    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // إنشاء اتصال بقاعدة البيانات
    $database = new Database();
    $conn = $database->getConnection();

    $success_message = '';
    $error_message = '';

    // معالجة تأكيد صرف الدواء
    if (isset($_POST['confirm_dispense'])) {
        $prescription_id = $_POST['prescription_id'] ?? 0;
        
        try {
            // التحقق من وجود الروشتة
            $check_stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
            $check_stmt->execute([$prescription_id]);
            $prescription = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prescription) {
                $error_message = "الروشتة غير موجودة";
            } elseif ($prescription['status'] === 'dispensed') {
                $error_message = "تم صرف هذه الروشتة بالفعل";
            } else {
                // جلب الأدوية المرتبطة بالروشتة
                $medicines_stmt = $conn->prepare("SELECT pm.*, m.name, m.quantity_in_stock FROM prescription_medicines pm JOIN medicines m ON pm.medicine_id = m.id WHERE pm.prescription_id = ?");
                $medicines_stmt->execute([$prescription_id]);
                $prescription_medicines = $medicines_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // التحقق من توفر الأدوية في المخزون
                $unavailable_medicines = [];
                foreach ($prescription_medicines as $medicine) {
                    if ($medicine['quantity_in_stock'] < $medicine['quantity']) {
                        $unavailable_medicines[] = $medicine['name'] . " (المطلوب: " . $medicine['quantity'] . ", المتوفر: " . $medicine['quantity_in_stock'] . ")";
                    }
                }
                
                if (!empty($unavailable_medicines)) {
                    $error_message = "لا يمكن صرف الأدوية - الأدوية التالية غير متوفرة بالكمية المطلوبة: " . implode("، ", $unavailable_medicines);
                } else {
                    // بدء المعاملة لضمان تماسك البيانات
                    $conn->beginTransaction();
                    
                    try {
                        // تحديث حالة الروشتة
                        $update_stmt = $conn->prepare("UPDATE prescriptions SET status = 'dispensed', updated_at = NOW() WHERE id = ?");
                        $update_stmt->execute([$prescription_id]);
                        
                        // الحصول على معرف الصيدلي
                        $pharmacist_stmt = $conn->prepare("SELECT id FROM pharmacists WHERE user_id = ?");
                        $pharmacist_stmt->execute([$_SESSION['user_id']]);
                        $pharmacist = $pharmacist_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$pharmacist) {
                            // لم يتم العثور على بيانات الصيدلي - إلغاء العملية
                            $conn->rollBack();
                            logError("Pharmacist record not found for user_id: " . $_SESSION['user_id']);
                            $error_message = "لم يتم العثور على بيانات الصيدلي في النظام. يرجى التواصل مع مسؤول النظام.";
                            // الخروج من المعالجة - لا يمكننا الاستمرار بدون معرف الصيدلي
                        } else {
                            $pharmacist_id = $pharmacist['id'];
                            logError("Found pharmacist ID: " . $pharmacist_id . " for user: " . $_SESSION['user_id']);
                            
                            // تسجيل عملية صرف الأدوية
                            foreach ($prescription_medicines as $medicine) {
                                // تحديث مخزون الدواء
                                $update_inventory = $conn->prepare("UPDATE medicines SET quantity_in_stock = quantity_in_stock - ?, updated_at = NOW() WHERE id = ?");
                                $update_inventory->execute([$medicine['quantity'], $medicine['medicine_id']]);
                                
                                // تسجيل عملية الصرف - تم التعديل ليتوافق مع هيكل الجدول الفعلي
                            }
                            
                            // إنشاء سجل صرف واحد للروشتة بدلاً من سجل لكل دواء
                            $dispense_stmt = $conn->prepare("INSERT INTO dispensed_medicines (prescription_id, pharmacist_id, status, notes, created_at, updated_at) VALUES (?, ?, 'complete', ?, NOW(), NOW())");
                            $dispense_stmt->execute([
                                $prescription_id,
                                $pharmacist_id,
                                'تم صرف الأدوية بواسطة الصيدلي'
                            ]);
                            
                            // تأكيد المعاملة
                            $conn->commit();
                            
                            $success_message = "تم تأكيد صرف الأدوية بنجاح";
                        }
                    } catch (PDOException $e) {
                        // التراجع عن المعاملة في حالة حدوث خطأ
                        $conn->rollBack();
                        
                        if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                            logError("Integrity constraint error: " . $e->getMessage());
                            
                            if (strpos($e->getMessage(), 'pharmacist_id') !== false) {
                                $error_message = "خطأ في ربط بيانات الصيدلي. يرجى التأكد من وجود حساب صيدلي مرتبط بحسابك.";
                            } else {
                                $error_message = "خطأ في قيود قاعدة البيانات عند صرف الأدوية. يرجى التواصل مع مسؤول النظام.";
                            }
                        } else {
                            $error_message = "حدث خطأ أثناء صرف الأدوية: " . $e->getMessage();
                            logError("Error dispensing medicines: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "حدث خطأ: " . $e->getMessage();
            logError("Error checking prescription: " . $e->getMessage());
        }
    }

    // البحث عن روشتة محددة
    $prescription_id = isset($_GET['prescription_id']) ? intval($_GET['prescription_id']) : 0;
    $prescription = null;
    $prescription_medicines = [];

    if ($prescription_id > 0) {
        // جلب بيانات الروشتة
        $stmt = $conn->prepare("
            SELECT p.*, 
                   pt.first_name as patient_first_name, pt.last_name as patient_last_name,
                   d.first_name as doctor_first_name, d.last_name as doctor_last_name
            FROM prescriptions p
            JOIN patients pt ON p.patient_id = pt.id
            JOIN doctors d ON p.doctor_id = d.id
            WHERE p.id = ?
        ");
        $stmt->execute([$prescription_id]);
        $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prescription) {
            // جلب الأدوية في الروشتة
            $med_stmt = $conn->prepare("
                SELECT pm.*, m.name, m.type, m.price, m.quantity_in_stock
                FROM prescription_medicines pm
                JOIN medicines m ON pm.medicine_id = m.id
                WHERE pm.prescription_id = ?
            ");
            $med_stmt->execute([$prescription_id]);
            $prescription_medicines = $med_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // تضمين ملف الهيدر
    include_once 'header.php';
} catch (Exception $e) {
    // التعامل مع أي استثناء
    logError("Fatal Error: " . $e->getMessage());
    echo "<div style='direction: rtl; text-align: right; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #d9534f;'>خطأ في النظام</h2>";
    echo "<p>حدث خطأ غير متوقع في النظام:</p>";
    echo "<p><strong>الخطأ:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='dashboard.php' class='btn btn-primary'>العودة للوحة التحكم</a> ";
    echo "<a href='../setup_pharmacy_tables.php' class='btn btn-success'>إصلاح قاعدة البيانات</a></p>";
    echo "</div>";
    exit();
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-pills me-2"></i>صرف الأدوية</h2>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- نموذج البحث -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search me-2"></i>البحث عن روشتة
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-8">
                    <label for="prescription_id" class="form-label">رقم الروشتة</label>
                    <input type="number" class="form-control" id="prescription_id" name="prescription_id" value="<?php echo $prescription_id; ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- تفاصيل الروشتة -->
    <?php if ($prescription): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clipboard-list me-2"></i>تفاصيل الروشتة #<?php echo $prescription['id']; ?>
                </div>
                <div>
                    <span class="badge <?php echo ($prescription['status'] === 'pending') ? 'bg-warning' : (($prescription['status'] === 'dispensed') ? 'bg-success' : 'bg-danger'); ?>">
                        <?php echo ($prescription['status'] === 'pending') ? 'قيد الانتظار' : (($prescription['status'] === 'dispensed') ? 'تم الصرف' : 'ملغاة'); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>بيانات المريض</h5>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>بيانات الطبيب</h5>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>تاريخ الروشتة:</strong> <?php echo htmlspecialchars($prescription['prescription_date']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>ملاحظات:</strong> <?php echo htmlspecialchars($prescription['notes'] ?? 'لا توجد ملاحظات'); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($prescription_medicines)): ?>
                    <h5 class="mb-3">الأدوية المطلوبة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                                                        <thead class="table-light">                                <tr>                                    <th>#</th>                                    <th>اسم الدواء</th>                                    <th>النوع</th>                                    <th>الكمية</th>                                    <th>تعليمات الجرعة</th>                                    <th>السعر</th>                                    <th>الإجمالي</th>                                </tr>                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                $total_price = 0;
                                foreach ($prescription_medicines as $medicine): 
                                    $medicine_total = $medicine['quantity'] * $medicine['price'];
                                    $total_price += $medicine_total;
                                ?>
                                                                        <tr class="<?php echo ($medicine['quantity_in_stock'] < $medicine['quantity']) ? 'table-danger' : ''; ?>">                                        <td><?php echo $counter++; ?></td>                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>                                        <td><?php echo htmlspecialchars($medicine['type']); ?></td>                                        <td>                                            <?php echo htmlspecialchars($medicine['quantity']); ?>                                            <?php if ($medicine['quantity_in_stock'] < $medicine['quantity']): ?>                                                <br><small class="text-danger">المتوفر: <?php echo htmlspecialchars($medicine['quantity_in_stock']); ?></small>                                            <?php else: ?>                                                <br><small class="text-success">المتوفر: <?php echo htmlspecialchars($medicine['quantity_in_stock']); ?></small>                                            <?php endif; ?>                                        </td>                                        <td><?php echo htmlspecialchars($medicine['dosage_instructions'] ?? ''); ?></td>                                        <td><?php echo htmlspecialchars($medicine['price']); ?></td>                                        <td><?php echo htmlspecialchars($medicine_total); ?></td>                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-start"><strong>الإجمالي</strong></td>
                                    <td><strong><?php echo htmlspecialchars($total_price); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                                        <!-- التحقق من توفر جميع الأدوية للصرف -->                    <?php                     $all_medicines_available = true;                    $unavailable_count = 0;                                        foreach ($prescription_medicines as $medicine) {                        if ($medicine['quantity_in_stock'] < $medicine['quantity']) {                            $all_medicines_available = false;                            $unavailable_count++;                        }                    }                    ?>                                        <!-- زر تأكيد صرف الدواء -->                    <?php if ($prescription['status'] === 'pending'): ?>                        <div class="mt-4">                            <?php if (!$all_medicines_available): ?>                                <div class="alert alert-warning text-center">                                    <i class="fas fa-exclamation-triangle me-2"></i>                                    لا يمكن صرف الدواء: <?php echo $unavailable_count; ?> من الأدوية غير متوفرة بالكمية المطلوبة                                    <div class="mt-2">                                        <a href="inventory.php" class="btn btn-sm btn-warning me-2">                                            <i class="fas fa-boxes me-1"></i>إدارة المخزون                                        </a>                                        <a href="dispense_medicine.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-info">                                            <i class="fas fa-sync me-1"></i>تحديث                                        </a>                                    </div>                                </div>                            <?php else: ?>                                <div class="alert alert-success text-center">                                    <i class="fas fa-check-circle me-2"></i>                                    جميع الأدوية متوفرة ويمكن صرف الروشتة                                </div>                            <?php endif; ?>                                                        <div class="text-center">                                <form method="post">                                    <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">                                    <button type="submit" name="confirm_dispense" class="btn btn-success btn-lg" <?php echo !$all_medicines_available ? 'disabled' : ''; ?>>                                        <i class="fas fa-check-circle me-1"></i> تأكيد صرف الدواء                                    </button>                                </form>                            </div>                        </div>
                    <?php elseif ($prescription['status'] === 'dispensed'): ?>
                        <div class="mt-4 text-center">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>تم صرف هذه الروشتة بالفعل
                            </div>
                            <a href="view_prescriptions.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i> عودة إلى قائمة الروشتات
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 text-center">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>هذه الروشتة ملغاة
                            </div>
                            <a href="view_prescriptions.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i> عودة إلى قائمة الروشتات
                            </a>
                        </div>
                    <?php endif; ?>
                <?php elseif (empty($prescription_medicines)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد أدوية مسجلة في هذه الروشتة
                    </div>
                    <div class="mt-4 text-center">
                        <a href="view_prescriptions.php" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i> عودة إلى قائمة الروشتات
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>حدث خطأ في عرض الأدوية
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($prescription_id > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>الروشتة غير موجودة
        </div>
    <?php endif; ?>
    
    <!-- روشتات بانتظار الصرف -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-clipboard-list me-2"></i>روشتات بانتظار الصرف
        </div>
        <div class="card-body">
            <?php
            // جلب الروشتات التي بانتظار الصرف
            $pending_stmt = $conn->prepare("
                SELECT p.id, p.prescription_date, 
                       pt.first_name as patient_first_name, pt.last_name as patient_last_name,
                       d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                       COUNT(pm.id) as medicine_count
                FROM prescriptions p
                JOIN patients pt ON p.patient_id = pt.id
                JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN prescription_medicines pm ON p.id = pm.prescription_id
                WHERE p.status = 'pending'
                GROUP BY p.id
                ORDER BY p.prescription_date DESC
                LIMIT 10
            ");
            $pending_stmt->execute();
            $pending_prescriptions = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
                        <?php if (!empty($pending_prescriptions)): ?>                <div class="table-responsive">                    <table class="table table-hover">                        <thead>                            <tr>                                <th>رقم الروشتة</th>                                <th>تاريخ الروشتة</th>                                <th>المريض</th>                                <th>الطبيب</th>                                <th>عدد الأدوية</th>                                <th>حالة المخزون</th>                                <th>الإجراءات</th>                            </tr>                        </thead>                        <tbody>                            <?php foreach ($pending_prescriptions as $row):                                 // التحقق من توفر الأدوية في المخزون لكل روشتة                                $stock_check_stmt = $conn->prepare("                                    SELECT                                         SUM(CASE WHEN m.quantity_in_stock >= pm.quantity THEN 1 ELSE 0 END) as available_count,                                        COUNT(pm.id) as total_count                                    FROM prescription_medicines pm                                    JOIN medicines m ON pm.medicine_id = m.id                                    WHERE pm.prescription_id = ?                                ");                                $stock_check_stmt->execute([$row['id']]);                                $stock_status = $stock_check_stmt->fetch(PDO::FETCH_ASSOC);                                                                $all_available = ($stock_status['available_count'] == $stock_status['total_count']);                            ?>                                <tr>                                    <td><?php echo htmlspecialchars($row['id']); ?></td>                                    <td><?php echo htmlspecialchars($row['prescription_date']); ?></td>                                    <td><?php echo htmlspecialchars($row['patient_first_name'] . ' ' . $row['patient_last_name']); ?></td>                                    <td><?php echo htmlspecialchars($row['doctor_first_name'] . ' ' . $row['doctor_last_name']); ?></td>                                    <td><?php echo htmlspecialchars($row['medicine_count']); ?></td>                                    <td>                                        <?php if ($all_available): ?>                                            <span class="badge bg-success">متوفر</span>                                        <?php else: ?>                                            <span class="badge bg-warning">نقص في المخزون</span>                                        <?php endif; ?>                                    </td>                                    <td>                                        <a href="dispense_medicine.php?prescription_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">                                            <i class="fas fa-eye me-1"></i> عرض                                        </a>                                    </td>                                </tr>                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>لا توجد روشتات بانتظار الصرف حالياً
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // يمكن إضافة أي سكريبت خاص بالصفحة هنا
</script>

<?php
// تضمين ملف الفوتر إذا كان موجودًا
if (file_exists('footer.php')) {
    include_once 'footer.php';
}
?>