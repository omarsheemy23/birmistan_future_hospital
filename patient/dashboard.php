<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وصلاحية المستخدم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// استدعاء ملف الهيدر
include('header.php');

// جلب معلومات المريض
$stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// جلب المواعيد القادمة
$stmt = $pdo->prepare("
    SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name,
           dep.name as department_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN departments dep ON d.department_id = dep.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
");
$stmt->execute([$patient['id']]);
$upcoming_appointments = $stmt->fetchAll();

// جلب السجلات الطبية الحديثة
$stmt = $pdo->prepare("
    SELECT mr.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name
    FROM medical_records mr
    JOIN doctors d ON mr.doctor_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.created_at DESC
    LIMIT 5
");
$stmt->execute([$patient['id']]);
$recent_records = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <!-- Hospital Logo and Name -->
                <div class="text-center mb-4">
                    <h4 class="text-primary">مستشفى المستقبل</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="profile.php">
                            <i class="fas fa-user"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">لوحة التحكم</h1>
            </div>

            <!-- بطاقة الترحيب -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($patient['first_name'] ?? '') . ' ' . htmlspecialchars($patient['last_name'] ?? ''); ?></h4>
                            <p class="card-text">لوحة تحكم المريض تعرض ملخصاً للمواعيد والسجلات الطبية</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-right-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">المواعيد القادمة</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($upcoming_appointments); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-right-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">السجلات الطبية</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($recent_records); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-notes-medical fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-right-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">فصيلة الدم</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($patient['blood_type'] ?? 'غير محدد'); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tint fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-right-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">العمر</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($patient['age'] ?? 'غير محدد'); ?> سنة</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المواعيد القادمة -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">المواعيد القادمة</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_appointments)): ?>
                                <p class="text-center">لا توجد مواعيد قادمة</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الطبيب</th>
                                                <th>القسم</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                                    <td>د. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['department_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $appointment['status'] === 'confirmed' ? 'success' : 
                                                                ($appointment['status'] === 'pending' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php 
                                                            echo $appointment['status'] === 'confirmed' ? 'مؤكد' : 
                                                                ($appointment['status'] === 'pending' ? 'معلق' : 'ملغي'); 
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($appointment['status'] === 'pending' && (!isset($appointment['payment_status']) || $appointment['payment_status'] === 'pending')): ?>
                                                        <a href="payment_new.php?appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-credit-card"></i> دفع
                                                        </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- السجلات الطبية الحديثة -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">السجلات الطبية الحديثة</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_records)): ?>
                                <p class="text-center">لا توجد سجلات طبية</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>التاريخ</th>
                                                <th>الطبيب</th>
                                                <th>التشخيص</th>
                                                <th>الوصفة الطبية</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($record['created_at'])); ?></td>
                                                    <td>د. <?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['prescription']); ?></td>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>