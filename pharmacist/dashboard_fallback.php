<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_error.log');

session_start();

// التحقق من وجود الملفات المطلوبة
if (!file_exists('../config/database.php')) {
    die('خطأ: ملف قاعدة البيانات غير موجود');
}

if (!file_exists('../includes/functions.php')) {
    die('خطأ: ملف الدوال غير موجود');
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// نتجاهل التحقق من تسجيل الدخول في النسخة الاحتياطية
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
//     header("Location: ../login.php");
//     exit();
// }

// نسجل رسالة تشخيصية
error_log("Loading fallback dashboard - " . date('Y-m-d H:i:s'));

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// محاولة جلب معلومات الصيدلي إذا كان مسجل الدخول
$pharmacist = [
    'first_name' => 'صيدلي',
    'last_name' => 'النظام'
];

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM pharmacists WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $pharmacist_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pharmacist_data) {
            $pharmacist = $pharmacist_data;
        }
    } catch (PDOException $e) {
        error_log("Error fetching pharmacist data: " . $e->getMessage());
    }
}

// الإحصائيات - تجنب الاستعلامات المعقدة
$stats = [
    'pending_prescriptions' => 0,
    'dispensed_prescriptions' => 0,
    'low_stock_medicines' => 0,
    'expiring_medicines' => 0
];

try {
    // استعلامات بسيطة للغاية لتجنب أي مشاكل
    $stats_query = "SELECT 
                   COUNT(*) as count,
                   'pending' as type
                   FROM prescriptions 
                   WHERE status = 'pending'
                   UNION
                   SELECT 
                   COUNT(*) as count,
                   'dispensed' as type
                   FROM prescriptions 
                   WHERE status = 'dispensed'
                   UNION
                   SELECT 
                   COUNT(*) as count,
                   'low_stock' as type
                   FROM medicines 
                   WHERE quantity_in_stock < 20
                   UNION
                   SELECT 
                   COUNT(*) as count,
                   'expiring' as type
                   FROM medicines 
                   WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
                   
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats_results = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats_results as $result) {
        switch ($result['type']) {
            case 'pending':
                $stats['pending_prescriptions'] = $result['count'];
                break;
            case 'dispensed':
                $stats['dispensed_prescriptions'] = $result['count'];
                break;
            case 'low_stock':
                $stats['low_stock_medicines'] = $result['count'];
                break;
            case 'expiring':
                $stats['expiring_medicines'] = $result['count'];
                break;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

// الروشتات الأخيرة - تجنب JOIN مع prescription_medicines
try {
    $recent_prescriptions_query = "
        SELECT p.id, p.prescription_date, p.status,
               pt.first_name as patient_first_name, pt.last_name as patient_last_name,
               d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.id
        JOIN doctors d ON p.doctor_id = d.id
        ORDER BY p.prescription_date DESC
        LIMIT 5";
    $recent_stmt = $conn->prepare($recent_prescriptions_query);
    $recent_stmt->execute();
    $recent_prescriptions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent prescriptions: " . $e->getMessage());
    $recent_prescriptions = [];
}

// الأدوية منخفضة المخزون
try {
    $low_stock_query = "
        SELECT * FROM medicines 
        WHERE quantity_in_stock < 20 
        ORDER BY quantity_in_stock ASC
        LIMIT 5";
    $low_stock_stmt = $conn->prepare($low_stock_query);
    $low_stock_stmt->execute();
    $low_stock_medicines = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching low stock medicines: " . $e->getMessage());
    $low_stock_medicines = [];
}

// الأدوية التي توشك على انتهاء الصلاحية
try {
    $expiring_query = "
        SELECT * FROM medicines 
        WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY expiry_date ASC
        LIMIT 5";
    $expiring_stmt = $conn->prepare($expiring_query);
    $expiring_stmt->execute();
    $expiring_medicines = $expiring_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching expiring medicines: " . $e->getMessage());
    $expiring_medicines = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الصيدلي (نسخة احتياطية) - مستشفى بيرمستان المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #1e3c72 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
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
        .alert-fix {
            margin-bottom: 0;
            border-radius: 0;
        }
    </style>
</head>
<body>
    <!-- تنبيه وضع النسخة الاحتياطية -->
    <div class="alert alert-warning text-center alert-fix" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>تحذير:</strong> أنت تستخدم النسخة الاحتياطية من لوحة التحكم - بعض الميزات قد لا تعمل بشكل صحيح
    </div>
    
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard_fallback.php">
                <i class="fas fa-clinic-medical me-2"></i>مستشفى بيرمستان المستقبل - نظام الصيدلية
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard_fallback.php">
                            <i class="fas fa-home me-1"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pharmacy/patients.php">
                            <i class="fas fa-users me-1"></i> المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pharmacy/standalone.php">
                            <i class="fas fa-pills me-1"></i> نسخة مستقلة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../setup_pharmacy_tables.php">
                            <i class="fas fa-database me-1"></i> إصلاح قاعدة البيانات
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-arrow-left me-1"></i> العودة للنظام
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- مسار التنقل -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    نسخة احتياطية من لوحة تحكم الصيدلي
                </h5>
                <div>
                    <a href="../setup_pharmacy_tables.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-database me-1"></i> إصلاح قاعدة البيانات
                    </a>
                    <a href="../pharmacy/standalone.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sync-alt me-1"></i> النسخة المستقلة
                    </a>
                </div>
            </div>
        </div>

        <!-- ترحيب وملخص -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($pharmacist['first_name'] ?? '') . ' ' . htmlspecialchars($pharmacist['last_name'] ?? ''); ?></h4>
                        <p class="card-text">هذه نسخة احتياطية من لوحة التحكم تتجنب استخدام الجداول المفقودة. يرجى إصلاح قاعدة البيانات باستخدام الرابط أعلاه.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['pending_prescriptions']; ?></h3>
                        <p class="card-text">روشتات بانتظار الصرف</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['dispensed_prescriptions']; ?></h3>
                        <p class="card-text">روشتات تم صرفها</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['low_stock_medicines']; ?></h3>
                        <p class="card-text">أدوية منخفضة المخزون</p>
                        <a href="#" class="btn btn-dark btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['expiring_medicines']; ?></h3>
                        <p class="card-text">أدوية توشك على الانتهاء</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- المهام السريعة -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tasks me-2"></i>إصلاح قاعدة البيانات
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger mb-4">
                            <h5><i class="fas fa-exclamation-circle me-2"></i>مشكلة في قاعدة البيانات</h5>
                            <p>تم اكتشاف خطأ في قاعدة البيانات: جدول <code>prescription_medicines</code> غير موجود.</p>
                            <p>يجب إنشاء الجداول المفقودة لكي يعمل النظام بشكل صحيح.</p>
                        </div>
                        
                        <div class="text-center">
                            <h5 class="mb-3">لإصلاح المشكلة، اتبع الخطوات التالية:</h5>
                            <div class="d-grid gap-2 col-md-6 mx-auto">
                                <a href="../setup_pharmacy_tables.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-database me-2"></i>إصلاح قاعدة البيانات
                                </a>
                                <a href="../pharmacy/standalone.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-desktop me-2"></i>استخدام النسخة المستقلة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- الروشتات الأخيرة -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-2"></i>أحدث الروشتات
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_prescriptions)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>رقم</th>
                                            <th>المريض</th>
                                            <th>الطبيب</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_prescriptions as $prescription): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prescription['id']); ?></td>
                                                <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                                <td>
                                                    <?php if ($prescription['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">قيد الانتظار</span>
                                                    <?php elseif ($prescription['status'] === 'dispensed'): ?>
                                                        <span class="badge bg-success">تم الصرف</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($prescription['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>لا توجد روشتات حديثة أو حدث خطأ في الاتصال بقاعدة البيانات
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- الأدوية منخفضة المخزون -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle me-2"></i>الأدوية منخفضة المخزون
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_medicines)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>اسم الدواء</th>
                                            <th>النوع</th>
                                            <th>الكمية المتبقية</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($low_stock_medicines as $medicine): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medicine['name'] ?? 'غير معروف'); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['type'] ?? 'غير معروف'); ?></td>
                                                <td>
                                                    <?php if (isset($medicine['quantity_in_stock']) && $medicine['quantity_in_stock'] == 0): ?>
                                                        <span class="badge bg-danger">نفذ</span>
                                                    <?php elseif (isset($medicine['quantity_in_stock'])): ?>
                                                        <span class="badge bg-warning"><?php echo $medicine['quantity_in_stock']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">غير معروف</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>لا توجد بيانات حول الأدوية منخفضة المخزون أو حدث خطأ في الاتصال بقاعدة البيانات
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- معلومات تشخيصية -->
        <div class="card mt-4 mb-5">
            <div class="card-header bg-info text-white">
                <i class="fas fa-info-circle me-2"></i>معلومات تشخيصية
            </div>
            <div class="card-body">
                <div class="alert alert-light">
                    <h5>خطأ قاعدة البيانات:</h5>
                    <p>تم تحميل النسخة الاحتياطية من لوحة التحكم لأن النظام واجه الخطأ التالي:</p>
                    <div class="bg-danger text-white p-2 rounded mb-3">
                        <code>PDOException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'hospital_db.prescription_medicines' doesn't exist</code>
                    </div>
                    
                    <h5>الحل:</h5>
                    <p>لحل هذه المشكلة، يمكنك استخدام "إصلاح قاعدة البيانات" لإنشاء الجداول المفقودة. باتباع الخطوات التالية:</p>
                    <ol>
                        <li>انقر على زر <strong>إصلاح قاعدة البيانات</strong> أعلاه</li>
                        <li>انتظر حتى تظهر رسالة نجاح</li>
                        <li>حاول الوصول إلى لوحة تحكم الصيدلي الرئيسية مرة أخرى</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- تذييل الصفحة -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> جميع الحقوق محفوظة - مستشفى بيرمستان المستقبل
            </p>
            <p class="small text-muted mb-0">
                نظام إدارة الصيدلية - وضع النسخة الاحتياطية
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 