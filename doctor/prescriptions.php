<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("
        SELECT d.id, d.first_name, d.last_name, dep.name as department_name
        FROM doctors d
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب جميع الوصفات الطبية الخاصة بالطبيب
    $stmt = $pdo->prepare("
        SELECT p.*, 
               CONCAT(pat.first_name, ' ', pat.last_name) as patient_name,
               mr.diagnosis
        FROM prescriptions p
        JOIN patients pat ON p.patient_id = pat.id
        LEFT JOIN medical_records mr ON p.medical_record_id = mr.id
        WHERE p.doctor_id = ?
        ORDER BY p.prescription_date DESC
    ");
    $stmt->execute([$doctor['id']]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// تضمين ملف الهيدر بعد عمليات إعادة التوجيه
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="prescriptions.php">
                            <i class="fas fa-prescription"></i> الوصفات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pharmacy_inventory.php">
                            <i class="fas fa-pills"></i> مخزون الأدوية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">الوصفات الطبية</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="medical_records.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-file-medical"></i> السجلات الطبية
                    </a>
                    <a href="pharmacy_inventory.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-pills"></i> مخزون الأدوية
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    تم صرف الوصفة الطبية بنجاح
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">قائمة الوصفات الطبية</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($prescriptions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الوصفة</th>
                                        <th>اسم المريض</th>
                                        <th>تاريخ الوصفة</th>
                                        <th>حالة الوصفة</th>
                                        <th>رقم السجل الطبي</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription): ?>
                                        <tr>
                                            <td><?php echo $prescription['id']; ?></td>
                                            <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($prescription['prescription_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $prescription['status'] === 'صادرة' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo htmlspecialchars($prescription['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($prescription['medical_record_id']): ?>
                                                    <a href="view_medical_record.php?id=<?php echo $prescription['medical_record_id']; ?>">
                                                        <?php echo $prescription['medical_record_id']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_prescription.php?id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> عرض
                                                </a>
                                                <a href="print_prescription.php?id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="fas fa-print"></i> طباعة
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            لا توجد وصفات طبية حتى الآن
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 