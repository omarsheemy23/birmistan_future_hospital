<?php
session_start();

// فحص إذا كان الطلب آتياً من صفحة الاختبار أو المطور
$is_test_mode = isset($_GET['test_mode']) || strpos($_SERVER['HTTP_REFERER'] ?? '', 'test_pharmacy.php') !== false;

// التحقق من تسجيل دخول الصيدلي
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    // إذا كان في وضع الاختبار، نقوم بتعيين جلسة مؤقتة
    if ($is_test_mode) {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'pharmacist';
        $_SESSION['is_test_mode'] = true;
    } else {
        // إعادة التوجيه إلى صفحة تسجيل الدخول
        header("Location: ../login.php");
        exit();
    }
}

// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الصيدلية - مستشفى بيرمستان المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #1e3c72 !important;
            overflow: hidden; /* منع العناصر من الخروج عن حدود الشريط */
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }
        .btn-logout {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .btn-logout:hover {
            background-color: #c82333;
            color: white;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2a5298;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #2a5298;
            border-color: #2a5298;
        }
        .btn-primary:hover {
            background-color: #1e3c72;
            border-color: #1e3c72;
        }
        .hospital-name {
            font-weight: bold;
            color: #fff;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['is_test_mode'])): ?>
    <div class="alert alert-warning text-center m-0" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        أنت في وضع الاختبار! إعدادات الجلسة مؤقتة ولن يتم حفظ التغييرات.
    </div>
    <?php endif; ?>
    
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-clinic-medical me-2"></i>مستشفى بيرمستان المستقبل - نظام الصيدلية
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'view_prescriptions.php' ? 'active' : ''; ?>" href="view_prescriptions.php">
                            <i class="fas fa-prescription me-1"></i> الروشتات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dispense_medicine.php' ? 'active' : ''; ?>" href="dispense_medicine.php">
                            <i class="fas fa-pills me-1"></i> صرف الدواء
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                            <i class="fas fa-boxes me-1"></i> إدارة المخزون
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i> التقارير
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> تسجيل الخروج
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>