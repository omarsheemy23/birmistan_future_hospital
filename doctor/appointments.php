<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// استدعاء ملف الهيدر
include('header.php');

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("
        SELECT d.*, dep.name as department_name
        FROM doctors d
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب المواعيد
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name, p.phone as patient_phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctor['id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

function getStatusBadge($status, $payment_status) {
    if ($payment_status == 'paid') {
        // إذا كان الموعد مدفوعاً، نعتبره مؤكداً حتى لو كانت حالته معلقة
        if ($status == 'pending') {
            return '<span class="badge bg-success">مؤكد</span>';
        }
        switch ($status) {
            case 'confirmed':
                return '<span class="badge bg-success">مؤكد</span>';
            case 'completed':
                return '<span class="badge bg-info">مكتمل</span>';
            case 'cancelled':
                return '<span class="badge bg-danger">ملغي</span>';
            default:
                return '<span class="badge bg-success">مؤكد</span>';
        }
    } else {
        return '<span class="badge bg-warning">معلق</span>';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">المواعيد</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوقت</th>
                                    <th>المريض</th>
                                    <th>رقم الهاتف</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_time'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(($appointment['patient_first_name'] ?? '') . ' ' . ($appointment['patient_last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($appointment['status']) {
                                                case 'scheduled':
                                                    $status_class = 'text-primary';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'text-success';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'text-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($appointment['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] === 'scheduled'): ?>
                                                <a href="complete_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> إكمال
                                                </a>
                                                <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i> إلغاء
                                                </a>
                                            <?php endif; ?>
                                            <a href="add_medical_record.php?patient_id=<?php echo $appointment['patient_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-file-medical"></i> سجل طبي
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">بيانات الطبيب</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($doctor['first_name'] ?? '') . ' ' . htmlspecialchars($doctor['last_name'] ?? ''); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($doctor['department_name'] ?? ''); ?></p>
                    <p class="card-text"><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></p>
                    <p class="card-text"><?php echo htmlspecialchars($doctor['phone'] ?? ''); ?></p>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>