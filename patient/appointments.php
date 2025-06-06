<?php
session_start();
require_once '../includes/config.php';
require_once 'header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

try {
    // Get patient ID
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch();

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // Get all appointments for this patient
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            d.specialization as specialty
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$patient['id']]);
    $appointments = $stmt->fetchAll();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_appointment.php">
                            <i class="fas fa-calendar-plus"></i> حجز موعد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> مواعيدي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجل الطبي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">مواعيدي</h1>
                <a href="book_appointment.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> حجز موعد جديد
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (empty($appointments)): ?>
                <div class="alert alert-info">
                    لا توجد مواعيد مسجلة. <a href="book_appointment.php">احجز موعد جديد</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-check"></i>
                                        موعد مع د. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                                    </h5>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php
                                        switch($appointment['status']) {
                                            case 'confirmed':
                                                echo 'مؤكد';
                                                break;
                                            case 'pending':
                                                echo 'قيد الانتظار';
                                                break;
                                            case 'cancelled':
                                                echo 'ملغي';
                                                break;
                                            default:
                                                echo $appointment['status'];
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>التخصص:</strong>
                                        <?php echo htmlspecialchars($appointment['specialty']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>التاريخ:</strong>
                                        <?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>الوقت:</strong>
                                        <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <?php if (isset($appointment['reason']) && !empty($appointment['reason'])): ?>
                                        <div class="mb-3">
                                            <strong>سبب الزيارة:</strong>
                                            <?php echo htmlspecialchars($appointment['reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                        <a href="video_call.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-video"></i> مكالمة فيديو
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] === 'pending' && (!isset($appointment['payment_status']) || $appointment['payment_status'] === 'pending')): ?>
                                        <a href="payment_new.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-credit-card"></i> دفع الرسوم
                                        </a>
                                        <a href="process_payment.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من رغبتك في الدفع بشكل مباشر؟');">
                                            <i class="fas fa-check-circle"></i> دفع مباشر
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] !== 'cancelled'): ?>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="showCancelModal(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-times"></i> إلغاء الموعد
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">تأكيد إلغاء الموعد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <form action="cancel_appointment.php" method="POST" style="display: inline;">
                    <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                    <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showCancelModal(appointmentId) {
    document.getElementById('cancelAppointmentId').value = appointmentId;
    var modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>

<?php require_once 'footer.php'; ?>