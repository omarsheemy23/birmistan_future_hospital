<?php
session_start();
require_once '../includes/config.php';

// استدعاء ملف الهيدر
include('header.php');

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $doctor['id'] ?? 0;
    
    // جلب الروشتات التي لم يتم صرفها بعد
    $stmt = $pdo->prepare("SELECT p.*, 
                          CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                          CONCAT(d.first_name, ' ', d.last_name) as doctor_name
                          FROM prescriptions p
                          JOIN patients pt ON p.patient_id = pt.id
                          JOIN doctors d ON p.doctor_id = d.id
                          WHERE p.status = 'pending'
                          ORDER BY p.created_at DESC");
    $stmt->execute();
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">صرف الأدوية</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="searchPrescription" class="form-control" placeholder="ابحث عن روشتة...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>رقم الروشتة</th>
                            <th>اسم المريض</th>
                            <th>اسم الطبيب</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($prescriptions) && count($prescriptions) > 0): ?>
                            <?php foreach ($prescriptions as $index => $prescription): ?>
                                <tr class="prescription-row" data-id="<?php echo $prescription['id']; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($prescription['prescription_number'] ?? "P-" . $prescription['id']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['patient_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($prescription['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-warning">قيد الانتظار</span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary view-details" data-bs-toggle="modal" data-bs-target="#prescriptionModal<?php echo $prescription['id']; ?>">
                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal for Prescription Details -->
                                <div class="modal fade" id="prescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">تفاصيل الروشتة #<?php echo htmlspecialchars($prescription['prescription_number'] ?? "P-" . $prescription['id']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>اسم المريض:</strong> <?php echo htmlspecialchars($prescription['patient_name'] ?? ''); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>اسم الطبيب:</strong> <?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>تاريخ الإنشاء:</strong> <?php echo date('Y-m-d H:i', strtotime($prescription['created_at'])); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>الحالة:</strong> <span class="badge bg-warning">قيد الانتظار</span></p>
                                                    </div>
                                                </div>
                                                
                                                <h6 class="mt-4 mb-3">الأدوية الموصوفة:</h6>
                                                <?php
                                                // جلب الأدوية المرتبطة بالروشتة
                                                $stmt = $pdo->prepare("SELECT pm.*, m.name as medicine_name, m.quantity_in_stock
                                                                      FROM prescription_medicines pm
                                                                      JOIN medicines m ON pm.medicine_id = m.id
                                                                      WHERE pm.prescription_id = ?");
                                                $stmt->execute([$prescription['id']]);
                                                $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>اسم الدواء</th>
                                                                <th>الجرعة</th>
                                                                <th>التعليمات</th>
                                                                <th>المخزون المتاح</th>
                                                                <th>الحالة</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($medicines as $medicine): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($medicine['medicine_name'] ?? ''); ?></td>
                                                                    <td><?php echo htmlspecialchars($medicine['dosage'] ?? ''); ?></td>
                                                                    <td><?php echo htmlspecialchars($medicine['instructions'] ?? ''); ?></td>
                                                                    <td><?php echo htmlspecialchars($medicine['quantity_in_stock'] ?? '0'); ?></td>
                                                                    <td>
                                                                        <?php if (($medicine['quantity_in_stock'] ?? 0) >= 1): ?>
                                                                            <span class="badge bg-success">متوفر</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-danger">غير متوفر</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <div class="mt-4">
                                                    <p><strong>ملاحظات:</strong></p>
                                                    <p><?php echo nl2br(htmlspecialchars($prescription['notes'] ?? 'لا توجد ملاحظات')); ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                <button type="button" class="btn btn-success dispense-btn" data-id="<?php echo $prescription['id']; ?>">
                                                    <i class="fas fa-check-circle"></i> صرف الأدوية
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">لا توجد روشتات في انتظار الصرف</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
// وظيفة البحث
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPrescription');
    const prescriptionRows = document.querySelectorAll('.prescription-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        prescriptionRows.forEach(row => {
            const prescriptionNumber = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const patientName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (prescriptionNumber.includes(searchTerm) || patientName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // معالجة زر صرف الأدوية
    const dispenseBtns = document.querySelectorAll('.dispense-btn');
    dispenseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const prescriptionId = this.getAttribute('data-id');
            if (confirm('هل أنت متأكد من صرف هذه الأدوية؟')) {
                // هنا يمكن إضافة كود AJAX لتحديث حالة الروشتة
                alert('تم صرف الأدوية بنجاح!');
                location.reload();
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>