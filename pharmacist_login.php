<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/database.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// رسالة للمستخدم
$message = '';

// جلب حساب صيدلي من قاعدة البيانات للاختبار
$query = "SELECT u.id, u.email, u.role, p.first_name, p.last_name 
          FROM users u 
          JOIN pharmacists p ON u.id = p.user_id 
          WHERE u.role = 'pharmacist' 
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // تعيين متغيرات الجلسة
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    
    $message = "تم تسجيل الدخول تلقائيًا كصيدلي: " . $user['first_name'] . ' ' . $user['last_name'];
    
    // إعادة توجيه إلى لوحة تحكم الصيدلية
    header("Refresh: 3; URL=pharmacy/dashboard.php");
} else {
    $message = "لم يتم العثور على أي حساب صيدلي في قاعدة البيانات.";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الصيدلي - اختبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 50px;
            background-color: #f8f9fa;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            text-align: center;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>تسجيل دخول الصيدلي - اختبار</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <?php echo $message; ?>
                </div>
                
                <h4>خيارات الاختبار</h4>
                <ul class="mt-3">
                    <li><a href="pharmacy/dashboard.php">الذهاب إلى لوحة تحكم الصيدلية (pharmacy/dashboard.php)</a></li>
                    <li><a href="pharmacist/dashboard.php">الذهاب إلى لوحة تحكم الصيدلية (pharmacist/dashboard.php)</a></li>
                    <li><a href="debug.php">عرض معلومات تشخيصية (debug.php)</a></li>
                    <li><a href="pharmacy/error.php">اختبار الأخطاء (pharmacy/error.php)</a></li>
                    <li><a href="test_pharmacy.php">اختبار صفحات الصيدلية (test_pharmacy.php)</a></li>
                </ul>
                
                <div class="mt-4">
                    <h5>معلومات الجلسة الحالية:</h5>
                    <pre class="bg-light p-3">
<?php print_r($_SESSION); ?>
                    </pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 