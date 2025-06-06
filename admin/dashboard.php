<?php
session_start();

// التحقق من تسجيل الدخول وأن المستخدم إداري
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

// تهيئة المتغيرات
$stats = [
    'total_patients' => 0,
    'total_doctors' => 0,
    'today_appointments' => 0,
    'pending_appointments' => 0
];
$today_appointments = [];
$recent_patients = [];

try {
    // جلب بيانات الإداري
    $stmt = $pdo->prepare("SELECT u.* FROM users u WHERE u.id = ? AND u.role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // تهيئة بيانات الإداري
    $admin = [
        'first_name' => isset($admin_data['first_name']) ? $admin_data['first_name'] : 'إداري',
        'last_name' => isset($admin_data['last_name']) ? $admin_data['last_name'] : 'النظام',
        'email' => isset($admin_data['email']) ? $admin_data['email'] : 'admin@hospital.com',
        'phone' => isset($admin_data['phone']) ? $admin_data['phone'] : 'غير محدد',
        'role' => isset($admin_data['role']) ? $admin_data['role'] : 'إداري',
        'profile_image' => isset($admin_data['profile_image']) ? $admin_data['profile_image'] : null
    ];

    // جلب إحصائيات عامة
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM patients) as total_patients,
            (SELECT COUNT(*) FROM doctors) as total_doctors,
            (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // جلب المواعيد اليومية
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name,
               d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               dep.name as department_name
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE DATE(a.appointment_date) = ?
        ORDER BY a.appointment_time ASC
    ");
    $stmt->execute([$today]);
    $today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب آخر 5 مرضى مسجلين
    $stmt = $pdo->prepare("
        SELECT p.*, u.email 
        FROM patients p
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // عدد الأطباء
    $stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
    $doctors_count = $stmt->fetchColumn();

    // عدد المرضى
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $patients_count = $stmt->fetchColumn();

    // عدد المواعيد اليوم
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $stmt->execute();
    $today_appointments_count = $stmt->fetchColumn();

    // عدد طلبات الإسعاف النشطة
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ambulance_requests WHERE status = 'pending'");
    $stmt->execute();
    $active_ambulance_requests = $stmt->fetchColumn();

    // عدد سيارات الإسعاف المتاحة
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ambulances WHERE status = 'available'");
    $stmt->execute();
    $available_ambulances = $stmt->fetchColumn();

    // المواعيد القادمة
    $stmt = $pdo->query("
        SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name,
               d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE DATE(a.appointment_date) >= CURDATE()
        ORDER BY a.appointment_date ASC
        LIMIT 5
    ");
    $upcoming_appointments = $stmt->fetchAll();

    // طلبات الإسعاف الأخيرة
    $stmt = $pdo->query("
        SELECT ar.*, p.first_name as patient_first_name, p.last_name as patient_last_name
        FROM ambulance_requests ar
        JOIN patients p ON ar.patient_id = p.id
        ORDER BY ar.request_date DESC
        LIMIT 5
    ");
    $recent_ambulance_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error in admin dashboard: " . $e->getMessage());
    $error_message = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<style>
    /* تنسيق القائمة الجانبية فقط */
    .sidebar .nav-link {
        color: #333;
        padding: 0.8rem 1rem;
        margin: 0.2rem 0;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.9);
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .sidebar .nav-link.active {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        font-weight: bold;
    }

    .sidebar .nav-link i {
        margin-left: 0.5rem;
        color: #0d6efd;
    }

    .sidebar .nav-link:hover i {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }
</style>

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    <br>
                    <small>يرجى التحقق من سجلات الخطأ لمزيد من التفاصيل.</small>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">لوحة التحكم</h1>
            </div>

            <!-- بطاقة الترحيب -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($admin['first_name'] ?? '') . ' ' . htmlspecialchars($admin['last_name'] ?? ''); ?></h4>
                            <p class="card-text">لوحة التحكم الرئيسية لإدارة نظام المستشفى</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي المرضى</h5>
                            <p class="card-text display-6"><?php echo isset($stats['total_patients']) ? $stats['total_patients'] : '0'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي الأطباء</h5>
                            <p class="card-text display-6"><?php echo isset($stats['total_doctors']) ? $stats['total_doctors'] : '0'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">مواعيد اليوم</h5>
                            <p class="card-text display-6"><?php echo $today_appointments_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">المواعيد المعلقة</h5>
                            <p class="card-text display-6"><?php echo isset($stats['pending_appointments']) ? $stats['pending_appointments'] : '0'; ?></p>
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
                                            <th>المريض</th>
                                            <th>الطبيب</th>
                                            <th>القسم</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($today_appointments)): ?>
                                            <?php foreach ($today_appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
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
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">لا توجد مواعيد لهذا اليوم</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- آخر المرضى المسجلين -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">آخر المرضى المسجلين</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>رقم الهاتف</th>
                                            <th>تاريخ التسجيل</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_patients)): ?>
                                            <?php foreach ($recent_patients as $patient): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['email'] ?? 'غير متوفر'); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['phone'] ?? 'غير متوفر'); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($patient['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">لا يوجد مرضى مسجلين حديثاً</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Profile Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">معلومات الإداري</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="<?php echo !empty($admin['profile_image']) ? '../uploads/' . htmlspecialchars($admin['profile_image']) : '../assets/images/default-avatar.png'; ?>" 
                                 class="rounded-circle" 
                                 alt="صورة الإداري" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                        <h6 class="mb-2"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h6>
                        <p class="text-muted mb-1">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?>
                        </p>
                        <p class="text-muted mb-1">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($admin['phone']); ?>
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($admin['role']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 