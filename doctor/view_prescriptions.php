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

    // جلب الوصفات الطبية الخاصة بالطبيب
    $stmt = $pdo->prepare("SELECT p.*, pt.first_name as patient_first_name, pt.last_name as patient_last_name, 
                          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                          CASE 
                            WHEN p.status = 'pending' THEN 'قيد الانتظار'
                            WHEN p.status = 'dispensed' THEN 'تم صرفها'
                            WHEN p.status = 'cancelled' THEN 'ملغية'
                            ELSE p.status
                          END as status_ar
                          FROM prescriptions p
                          JOIN patients pt ON p.patient_id = pt.id
                          JOIN doctors d ON p.doctor_id = d.id
                          WHERE p.doctor_id = ?
                          ORDER BY p.created_at DESC");
    $stmt->execute([$doctor_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar removed since there's already a navbar -->
        <!-- Main Content -->
        <main class="col-12 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                <h1 class="h2">عرض الروشتات الطبية</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>اسم المريض</th>
                            <th>تاريخ الوصفة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($prescriptions) && count($prescriptions) > 0): ?>
                            <?php foreach ($prescriptions as $index => $prescription): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($prescription['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $prescription['status'] === 'dispensed' ? 'bg-success' : ($prescription['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); ?>">
                                            <?php echo htmlspecialchars($prescription['status_ar']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#prescriptionModal<?php echo $prescription['id']; ?>">
                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for Prescription Details -->
                                <div class="modal fade" id="prescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-labelledby="prescriptionModalLabel<?php echo $prescription['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="prescriptionModalLabel<?php echo $prescription['id']; ?>">تفاصيل الوصفة الطبية</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>اسم المريض:</strong> <?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>اسم الطبيب:</strong> <?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>تاريخ الوصفة:</strong> <?php echo date('Y-m-d H:i', strtotime($prescription['created_at'])); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>الحالة:</strong> 
                                                            <span class="badge <?php echo $prescription['status'] === 'dispensed' ? 'bg-success' : ($prescription['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); ?>">
                                                                <?php echo htmlspecialchars($prescription['status_ar']); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <h6>الأدوية:</h6>
                                                    <?php 
                                                    // جلب الأدوية المرتبطة بالوصفة
                                                    $stmt = $pdo->prepare("SELECT pm.medicine_id, pm.quantity, pm.dosage_instructions, 
                                                                          m.name as medicine_name 
                                                                          FROM prescription_medicines pm
                                                                          JOIN medicines m ON pm.medicine_id = m.id
                                                                          WHERE pm.prescription_id = ?");
                                                    $stmt->execute([$prescription['id']]);
                                                    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <table class="table table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>اسم الدواء</th>
                                                                <th>الكمية</th>
                                                                <th>التعليمات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($medicines as $medicine): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($medicine['quantity'] ?? ''); ?></td>
                                                                    <td><?php echo htmlspecialchars($medicine['dosage_instructions'] ?? ''); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mb-3">
                                                    <h6>ملاحظات:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($prescription['notes'] ?? 'لا توجد ملاحظات')); ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                <?php if ($prescription['status'] === 'pending'): ?>
                                                    <a href="#" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من إلغاء هذه الوصفة؟')">إلغاء الوصفة</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">لا توجد وصفات طبية</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>