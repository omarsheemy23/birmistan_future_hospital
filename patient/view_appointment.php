<?php
session_start();
require_once '../includes/config.php';
require_once 'header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "رقم الموعد غير صحيح";
    header('Location: appointments.php');
    exit();
}

try {
    // Get appointment details with doctor information
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            d.specialty,
            d.phone as doctor_phone,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            p.phone as patient_phone
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN patients p ON a.patient_id = p.id
        WHERE a.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $_SESSION['error'] = "لم يتم العثور على الموعد";
        header('Location: appointments.php');
        exit();
    }

} catch (Exception $e) {
    $_SESSION['error'] = "حدث خطأ أثناء جلب بيانات الموعد";
    header('Location: appointments.php');
    exit();
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
                <h1 class="h2">تفاصيل الموعد</h1>
                <a href="appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> العودة للمواعيد
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

            <div class="card medical-record-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check"></i>
                        موعد مع د. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>معلومات الموعد</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>التاريخ:</th>
                                    <td><?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>الوقت:</th>
                                    <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>الحالة:</th>
                                    <td>
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
                                    </td>
                                </tr>
                                <tr>
                                    <th>سبب الزيارة:</th>
                                    <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                </tr>
                                <?php if ($appointment['notes']): ?>
                                    <tr>
                                        <th>ملاحظات:</th>
                                        <td><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>معلومات الطبيب</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>الاسم:</th>
                                    <td>د. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>التخصص:</th>
                                    <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                </tr>
                                <tr>
                                    <th>رقم الهاتف:</th>
                                    <td><?php echo htmlspecialchars($appointment['doctor_phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if ($appointment['status'] !== 'cancelled'): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="fas fa-times"></i> إلغاء الموعد
                        </button>
                    <?php endif; ?>
                </div>
            </div>
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
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 