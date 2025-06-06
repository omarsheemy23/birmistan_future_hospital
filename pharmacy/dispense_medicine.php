<?php
session_start();
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
            // تحديث حالة الروشتة
            $update_stmt = $conn->prepare("UPDATE prescriptions SET status = 'dispensed', updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$prescription_id]);
            
            // تسجيل عملية صرف الأدوية
            $medicines_stmt = $conn->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
            $medicines_stmt->execute([$prescription_id]);
            $prescription_medicines = $medicines_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($prescription_medicines as $medicine) {
                // تحديث مخزون الدواء
                $update_inventory = $conn->prepare("UPDATE medicines SET quantity_in_stock = quantity_in_stock - ? WHERE id = ?");
                $update_inventory->execute([$medicine['quantity'], $medicine['medicine_id']]);
                
                // تسجيل عملية الصرف
                $dispense_stmt = $conn->prepare("INSERT INTO dispensed_medicines (prescription_id, medicine_id, quantity, pharmacist_id, dispensed_date) VALUES (?, ?, ?, ?, NOW())");
                $dispense_stmt->execute([
                    $prescription_id,
                    $medicine['medicine_id'],
                    $medicine['quantity'],
                    $_SESSION['user_id']
                ]);
            }
            
            $success_message = "تم تأكيد صرف الأدوية بنجاح";
        }
    } catch (PDOException $e) {
        $error_message = "حدث خطأ: " . $e->getMessage();
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
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الدواء</th>
                                    <th>النوع</th>
                                    <th>الكمية</th>
                                    <th>تعليمات الجرعة</th>
                                    <th>السعر</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                $total_price = 0;
                                foreach ($prescription_medicines as $medicine): 
                                    $medicine_total = $medicine['quantity'] * $medicine['price'];
                                    $total_price += $medicine_total;
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['type']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['dosage_instructions'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['price']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine_total); ?></td>
                                    </tr>
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
                    
                    <!-- زر تأكيد صرف الدواء -->
                    <?php if ($prescription['status'] === 'pending'): ?>
                        <div class="mt-4 text-center">
                            <form method="post">
                                <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                <button type="submit" name="confirm_dispense" class="btn btn-success btn-lg">
                                    <i class="fas fa-check-circle me-1"></i> تأكيد صرف الدواء
                                </button>
                            </form>
                        </div>
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
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد أدوية مسجلة في هذه الروشتة
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
            
            <?php if (!empty($pending_prescriptions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الروشتة</th>
                                <th>تاريخ الروشتة</th>
                                <th>المريض</th>
                                <th>الطبيب</th>
                                <th>عدد الأدوية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_prescriptions as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['prescription_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['patient_first_name'] . ' ' . $row['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctor_first_name'] . ' ' . $row['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medicine_count']); ?></td>
                                    <td>
                                        <a href="dispense_medicine.php?prescription_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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