<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// التحقق من وجود معرف المريض
if (!isset($_GET['id'])) {
    header('Location: patients.php');
    exit();
}

$patient_id = $_GET['id'];

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $doctor['id'] ?? 0;

    if (!$doctor_id) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب بيانات المريض
    $stmt = $pdo->prepare("
        SELECT p.*, u.email, u.phone, u.created_at as registration_date
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // جلب قائمة المواعيد السابقة للمريض مع هذا الطبيب
    $stmt = $pdo->prepare("
        SELECT a.*, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               dep.name as department_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN departments dep ON d.department_id = dep.id
        WHERE a.patient_id = ? AND a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$patient_id, $doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب قائمة السجلات الطبية للمريض
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.id
        WHERE mr.patient_id = ? AND mr.doctor_id = ?
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$patient_id, $doctor_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة الوصفات الطبية للمريض
    $stmt = $pdo->prepare("
        SELECT p.*, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM prescriptions p
        JOIN doctors d ON p.doctor_id = d.id
        WHERE p.patient_id = ? AND p.doctor_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$patient_id, $doctor_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// استدعاء ملف الهيدر بعد معالجة البيانات
include('header.php');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar removed since there's already a navbar -->
        <!-- Main Content -->
        <main class="col-12 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                <h1 class="h2">تفاصيل المريض</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="patients.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> العودة إلى قائمة المرضى
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($patient)): ?>
                <!-- Patient Info Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">بيانات المريض</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>الاسم:</strong> <?php echo htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')); ?></p>
                                <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($patient['email'] ?? ''); ?></p>
                                <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($patient['phone'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>العنوان:</strong> <?php echo htmlspecialchars($patient['address'] ?? 'غير محدد'); ?></p>
                                <p><strong>تاريخ الميلاد:</strong> <?php echo isset($patient['birth_date']) ? date('Y-m-d', strtotime($patient['birth_date'])) : 'غير محدد'; ?></p>
                                <p><strong>تاريخ التسجيل:</strong> <?php echo date('Y-m-d', strtotime($patient['registration_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Buttons -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="d-flex gap-2">
                            <a href="add_medical_record.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> إضافة سجل طبي
                            </a>
                            <a href="prescribe_medicine.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success">
                                <i class="fas fa-prescription"></i> صرف أدوية
                            </a>
                            <a href="video_call.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-info">
                                <i class="fas fa-video"></i> مكالمة فيديو
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tabs for different sections -->
                <ul class="nav nav-tabs mb-4" id="patientTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab" aria-controls="appointments" aria-selected="true">المواعيد</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="medical-records-tab" data-bs-toggle="tab" data-bs-target="#medical-records" type="button" role="tab" aria-controls="medical-records" aria-selected="false">السجلات الطبية</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab" aria-controls="prescriptions" aria-selected="false">الروشتات</button>
                    </li>
                </ul>

                <div class="tab-content" id="patientTabsContent">
                    <!-- Appointments Tab -->
                    <div class="tab-pane fade show active" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
                        <?php if (empty($appointments)): ?>
                            <div class="alert alert-info">لا توجد مواعيد سابقة لهذا المريض</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>القسم</th>
                                            <th>الحالة</th>
                                            <th>حالة الدفع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['department_name'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $appointment['status'] === 'confirmed' ? 'bg-success' : 
                                                            ($appointment['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); 
                                                    ?>">
                                                        <?php 
                                                        echo $appointment['status'] === 'confirmed' ? 'مؤكد' : 
                                                            ($appointment['status'] === 'cancelled' ? 'ملغي' : 'معلق'); 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $appointment['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'; 
                                                    ?>">
                                                        <?php 
                                                        echo $appointment['payment_status'] === 'paid' ? 'مدفوع' : 'معلق'; 
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Medical Records Tab -->
                    <div class="tab-pane fade" id="medical-records" role="tabpanel" aria-labelledby="medical-records-tab">
                        <?php if (empty($medical_records)): ?>
                            <div class="alert alert-info">لا توجد سجلات طبية لهذا المريض</div>
                            <a href="add_medical_record.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> إضافة سجل طبي
                            </a>
                        <?php else: ?>
                            <div class="accordion" id="medicalRecordsAccordion">
                                <?php foreach ($medical_records as $index => $record): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                <div class="d-flex justify-content-between w-100">
                                                    <span>تاريخ الكشف: <?php echo date('Y-m-d', strtotime($record['created_at'])); ?></span>
                                                    <span>الطبيب: د. <?php echo htmlspecialchars(($record['doctor_first_name'] ?? '') . ' ' . ($record['doctor_last_name'] ?? '')); ?></span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#medicalRecordsAccordion">
                                            <div class="accordion-body">
                                                <div class="mb-3">
                                                    <h5>التشخيص:</h5>
                                                    <p><?php echo nl2br(htmlspecialchars($record['diagnosis'] ?? '')); ?></p>
                                                </div>
                                                <?php if (!empty($record['prescription'])): ?>
                                                    <div class="mb-3">
                                                        <h5>الوصفة الطبية:</h5>
                                                        <p><?php echo nl2br(htmlspecialchars($record['prescription'] ?? '')); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($record['notes'])): ?>
                                                    <div class="mb-3">
                                                        <h5>ملاحظات:</h5>
                                                        <p><?php echo nl2br(htmlspecialchars($record['notes'] ?? '')); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-3">
                                                    <a href="prescribe_medicine.php?patient_id=<?php echo $patient_id; ?>&record_id=<?php echo $record['id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-prescription"></i> صرف أدوية
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Prescriptions Tab -->
                    <div class="tab-pane fade" id="prescriptions" role="tabpanel" aria-labelledby="prescriptions-tab">
                        <?php if (empty($prescriptions)): ?>
                            <div class="alert alert-info">لا توجد روشتات طبية لهذا المريض</div>
                            <a href="prescribe_medicine.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success">
                                <i class="fas fa-prescription"></i> صرف أدوية
                            </a>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>رقم الروشتة</th>
                                            <th>التاريخ</th>
                                            <th>الطبيب</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prescriptions as $prescription): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prescription['id'] ?? ''); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($prescription['created_at'])); ?></td>
                                                <td>د. <?php echo htmlspecialchars(($prescription['doctor_first_name'] ?? '') . ' ' . ($prescription['doctor_last_name'] ?? '')); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $prescription['status'] === 'dispensed' ? 'bg-success' : 
                                                            ($prescription['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); 
                                                    ?>">
                                                        <?php 
                                                        echo $prescription['status'] === 'dispensed' ? 'تم الصرف' : 
                                                            ($prescription['status'] === 'cancelled' ? 'ملغية' : 'معلقة'); 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#prescriptionModal<?php echo $prescription['id']; ?>">
                                                        <i class="fas fa-eye"></i> عرض
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-print"></i> طباعة
                                                    </a>
                                                </td>
                                            </tr>

                                            <!-- Modal for Prescription Details -->
                                            <div class="modal fade" id="prescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">تفاصيل الروشتة #<?php echo $prescription['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php
                                                            // جلب الأدوية المرتبطة بالروشتة
                                                            $stmt = $pdo->prepare("SELECT pm.*, m.name as medicine_name 
                                                                                  FROM prescription_medicines pm
                                                                                  JOIN medicines m ON pm.medicine_id = m.id
                                                                                  WHERE pm.prescription_id = ?");
                                                            $stmt->execute([$prescription['id']]);
                                                            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                            ?>

                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <p><strong>التاريخ:</strong> <?php echo date('Y-m-d', strtotime($prescription['created_at'])); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>الطبيب:</strong> د. <?php echo htmlspecialchars(($prescription['doctor_first_name'] ?? '') . ' ' . ($prescription['doctor_last_name'] ?? '')); ?></p>
                                                                </div>
                                                            </div>

                                                            <h6>الأدوية:</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>اسم الدواء</th>
                                                                            <th>الكمية</th>
                                                                            <th>تعليمات الاستخدام</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php if (!empty($medicines)): ?>
                                                                            <?php foreach ($medicines as $medicine): ?>
                                                                                <tr>
                                                                                    <td><?php echo htmlspecialchars($medicine['medicine_name'] ?? ''); ?></td>
                                                                                    <td><?php echo htmlspecialchars($medicine['quantity'] ?? ''); ?></td>
                                                                                    <td><?php echo htmlspecialchars($medicine['dosage_instructions'] ?? ''); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <tr>
                                                                                <td colspan="4" class="text-center">لا توجد أدوية مسجلة</td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <?php if (!empty($prescription['notes'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>ملاحظات:</h6>
                                                                    <p><?php echo nl2br(htmlspecialchars($prescription['notes'] ?? '')); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                            <a href="#" class="btn btn-primary">
                                                                <i class="fas fa-print"></i> طباعة الروشتة
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 