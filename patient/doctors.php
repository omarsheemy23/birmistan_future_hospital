<?php
session_start();
require_once '../includes/config.php';
require_once 'header.php';

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب بيانات الأطباء
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            u.email,
            u.phone,
            dep.name as department_name
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        JOIN departments dep ON d.department_id = dep.id
        ORDER BY d.first_name, d.last_name
    ");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">قائمة الأطباء</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        د. <?php echo htmlspecialchars($doctor['first_name'] ?? '') . ' ' . htmlspecialchars($doctor['last_name'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <strong>التخصص:</strong> <?php echo htmlspecialchars($doctor['department_name'] ?? ''); ?><br>
                                        <strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($doctor['email'] ?? ''); ?><br>
                                        <strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>
                                    </p>
                                    <div class="d-flex justify-content-between">
                                        <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-calendar-plus"></i> حجز موعد
                                        </a>
                                        <a href="video_call.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-video"></i> مكالمة فيديو
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 