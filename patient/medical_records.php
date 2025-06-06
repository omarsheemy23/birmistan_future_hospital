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
    // جلب بيانات المريض
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM patients p
        WHERE p.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // جلب السجلات الطبية للمريض
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name, 
               dep.name as department_name
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.id
        JOIN departments dep ON d.department_id = dep.id
        WHERE mr.patient_id = ?
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$patient['id']]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
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
                        <a class="nav-link active" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
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
                <h1 class="h2">السجلات الطبية</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($medical_records)): ?>
                <div class="alert alert-info">
                    لا توجد سجلات طبية متاحة.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($medical_records as $record): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <?php echo htmlspecialchars($record['doctor_name'] ?? ''); ?>
                                        <small class="text-muted">(<?php echo htmlspecialchars($record['department_name'] ?? ''); ?>)</small>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?>
                                    </p>
                                    <p class="card-text">
                                        <strong>التشخيص:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($record['diagnosis'] ?? '')); ?>
                                    </p>
                                    <?php if (!empty($record['prescription'])): ?>
                                        <p class="card-text">
                                            <strong>الوصفة الطبية:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($record['prescription'] ?? '')); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($record['treatment_frequency'])): ?>
                                        <p class="card-text">
                                            <strong>عدد مرات العلاج:</strong><br>
                                            <?php echo htmlspecialchars($record['treatment_frequency'] ?? ''); ?> مرة/مرات
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($record['notes'])): ?>
                                        <p class="card-text">
                                            <strong>ملاحظات إضافية:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($record['notes'] ?? '')); ?>
                                        </p>
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

<?php require_once 'footer.php'; ?>