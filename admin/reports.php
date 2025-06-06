<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php';

// التحقق من تسجيل الدخول وأن المستخدم مسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

try {
    // إحصائيات عامة
    $stats = [
        'total_patients' => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
        'total_doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
        'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
        'total_departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
        'active_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'scheduled'")->fetchColumn(),
        'completed_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn()
    ];

    // إحصائيات الأقسام
    $department_stats = $pdo->query("
        SELECT d.name, COUNT(doc.id) as doctor_count, COUNT(a.id) as appointment_count
        FROM departments d
        LEFT JOIN doctors doc ON d.id = doc.department_id
        LEFT JOIN appointments a ON doc.id = a.doctor_id
        GROUP BY d.id, d.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // إحصائيات المواعيد حسب الشهر
    $appointment_stats = $pdo->query("
        SELECT 
            DATE_FORMAT(appointment_date, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM appointments
        WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">التقارير والإحصائيات</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- إحصائيات عامة -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي المرضى</h5>
                            <h2 class="card-text"><?php echo $stats['total_patients']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي الأطباء</h5>
                            <h2 class="card-text"><?php echo $stats['total_doctors']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي المواعيد</h5>
                            <h2 class="card-text"><?php echo $stats['total_appointments']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي الأقسام</h5>
                            <h2 class="card-text"><?php echo $stats['total_departments']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الأقسام -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">إحصائيات الأقسام</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>القسم</th>
                                    <th>عدد الأطباء</th>
                                    <th>عدد المواعيد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department_stats as $dept): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                        <td><?php echo $dept['doctor_count']; ?></td>
                                        <td><?php echo $dept['appointment_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- إحصائيات المواعيد -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">إحصائيات المواعيد (آخر 6 أشهر)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>الشهر</th>
                                    <th>إجمالي المواعيد</th>
                                    <th>المواعيد المكتملة</th>
                                    <th>المواعيد الملغاة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointment_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['month']); ?></td>
                                        <td><?php echo $stat['total']; ?></td>
                                        <td><?php echo $stat['completed']; ?></td>
                                        <td><?php echo $stat['cancelled']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 