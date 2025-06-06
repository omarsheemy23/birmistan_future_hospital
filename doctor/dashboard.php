<?php
session_start();
// التحقق من تسجيل الدخول وصلاحية المستخدم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// استدعاء ملف الهيدر
include('header.php');

require_once '../includes/config.php';

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT u.*, d.*, dep.name as department_name 
                          FROM users u 
                          JOIN doctors d ON u.id = d.user_id 
                          JOIN departments dep ON d.department_id = dep.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        // إذا لم يتم العثور على بيانات الطبيب، قم بإنشاء بيانات افتراضية
        $doctor = [
            'id' => $_SESSION['user_id'],
            'first_name' => 'طبيب',
            'last_name' => 'النظام',
            'email' => 'doctor@hospital.com',
            'phone' => 'غير محدد',
            'role' => 'طبيب',
            'profile_image' => null,
            'department_name' => 'غير محدد',
            'specialization' => 'غير محدد'
        ];
    }

    // جلب مواعيد اليوم
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name, p.last_name, p.phone as patient_phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_time ASC
    ");
    $stmt->execute([$doctor['id']]);
    $today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب المواعيد القادمة
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name, p.last_name, p.phone as patient_phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ? AND DATE(a.appointment_date) > CURDATE()
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5
    ");
    $stmt->execute([$doctor['id']]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب إحصائيات المواعيد
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$doctor['id']]);
    $appointment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // جلب الأقسام
    $stmt = $pdo->prepare("SELECT * FROM departments ORDER BY name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب عدد المرضى في كل قسم
    $stmt = $pdo->prepare("
        SELECT d.id, d.name, COUNT(DISTINCT a.patient_id) as patient_count
        FROM departments d
        LEFT JOIN doctors doc ON d.id = doc.department_id
        LEFT JOIN appointments a ON doc.id = a.doctor_id
        WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY d.id, d.name
    ");
    $stmt->execute();
    $department_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar removed since there's already a navbar -->
        <!-- Main Content -->
        <main class="col-12 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                <h1 class="h2">لوحة التحكم</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">مشاركة</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">تصدير</button>
                    </div>
                </div>
            </div>

            <!-- بطاقة الترحيب -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">أهلاً بك، د. <?php echo htmlspecialchars($doctor['first_name'] ?? '') . ' ' . htmlspecialchars($doctor['last_name'] ?? ''); ?></h4>
                            <p class="card-text">لوحة تحكم الطبيب تعرض ملخصاً للمواعيد والمرضى</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي المواعيد</h5>
                            <p class="card-text display-6"><?php echo $appointment_stats['total_appointments']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">المواعيد المؤكدة</h5>
                            <p class="card-text display-6"><?php echo $appointment_stats['completed_appointments']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">المواعيد المعلقة</h5>
                            <p class="card-text display-6"><?php echo $appointment_stats['pending_appointments']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الأقسام -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">الأقسام</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($departments as $dept): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($dept['name'] ?? ''); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($dept['description'] ?? ''); ?></p>
                                                <?php
                                                $patient_count = 0;
                                                foreach ($department_stats as $stat) {
                                                    if ($stat['id'] == $dept['id']) {
                                                        $patient_count = $stat['patient_count'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        عدد المرضى: <?php echo $patient_count; ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مواعيد اليوم -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">مواعيد اليوم</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الوقت</th>
                                            <th>اسم المريض</th>
                                            <th>رقم الهاتف</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($today_appointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                                <td><?php echo htmlspecialchars(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $appointment['status'] === 'completed' ? 'success' : 'warning'; 
                                                    ?>">
                                                        <?php 
                                                        echo $appointment['status'] === 'completed' ? 'مؤكد' : 'معلق'; 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($appointment['status'] === 'pending'): ?>
                                                        <a href="confirm_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من تأكيد هذا الموعد؟')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($appointment['status'] === 'completed'): ?>
                                                        <a href="video_call.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-video"></i>
                                                        </a>
                                                        <a href="add_medical_record.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-file-medical"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المواعيد القادمة -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">المواعيد القادمة</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>اسم المريض</th>
                                            <th>رقم الهاتف</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_appointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                                <td><?php echo htmlspecialchars(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $appointment['status'] === 'completed' ? 'success' : 'warning'; 
                                                    ?>">
                                                        <?php 
                                                        echo $appointment['status'] === 'completed' ? 'مؤكد' : 'معلق'; 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($appointment['status'] === 'pending'): ?>
                                                        <a href="confirm_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من تأكيد هذا الموعد؟')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html> 