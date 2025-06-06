<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات الممرض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

// جلب عدد المواعيد اليوم
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE DATE(appointment_date) = CURDATE() 
        AND status = 'pending'
    ");
    $stmt->execute();
    $today_appointments = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $today_appointments = 0;
}

// جلب عدد السجلات الطبية الجديدة
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM medical_records 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $today_records = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $today_records = 0;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الممرضين - مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2>لوحة تحكم الممرضين</h2>
            </div>
        </div>

        <!-- بطاقة الترحيب -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($_SESSION['first_name'] ?? '') . ' ' . htmlspecialchars($_SESSION['last_name'] ?? ''); ?></h4>
                        <p class="card-text">لوحة تحكم الممرضين تعرض ملخصاً للمواعيد والمهام</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check card-icon text-primary"></i>
                        <h3 class="card-title">مواعيد اليوم</h3>
                        <p class="card-text display-4"><?php echo $today_appointments; ?></p>
                        <a href="appointments.php" class="btn btn-primary">عرض المواعيد</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-file-medical card-icon text-success"></i>
                        <h3 class="card-title">السجلات الطبية الجديدة</h3>
                        <p class="card-text display-4"><?php echo $today_records; ?></p>
                        <a href="medical_records.php" class="btn btn-success">عرض السجلات</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">المواعيد القادمة</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>التاريخ والوقت</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
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
                                        while ($appointment = $stmt->fetch()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']) . '</td>';
                                            echo '<td>' . htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']) . '</td>';
                                            echo '<td>' . date('Y-m-d H:i', strtotime($appointment['appointment_date'])) . '</td>';
                                            echo '<td>' . get_appointment_status($appointment['status']) . '</td>';
                                            echo '<td>
                                                    <a href="appointments.php?id=' . $appointment['id'] . '" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                  </td>';
                                            echo '</tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="5" class="text-center">حدث خطأ أثناء جلب المواعيد</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 