<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>فحص إعداد نظام مستشفى بيرمستان المستقبل</h1>';

// التحقق من وجود مجلدات النظام
$directories = [
    'admin',
    'doctor',
    'nurse',
    'pharmacist',
    'pharmacy',
    'patient',
    'config',
    'includes',
    'assets'
];

echo '<h2>فحص المجلدات الأساسية</h2>';
echo '<ul>';
foreach ($directories as $dir) {
    echo '<li>' . $dir . ': ';
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo '<span style="color:green">موجود ✓</span>';
    } else {
        echo '<span style="color:red">غير موجود ✗</span>';
    }
    echo '</li>';
}
echo '</ul>';

// التحقق من وجود قاعدة البيانات وإمكانية الاتصال بها
echo '<h2>فحص الاتصال بقاعدة البيانات</h2>';
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo '<p style="color:green">تم الاتصال بقاعدة البيانات بنجاح ✓</p>';
    
    // فحص الجداول الأساسية
    $tables = ['users', 'doctors', 'nurses', 'pharmacists', 'patients', 'prescriptions', 'medicines'];
    
    echo '<h3>فحص جداول قاعدة البيانات</h3>';
    echo '<ul>';
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        echo '<li>' . $table . ': ';
        if ($stmt->rowCount() > 0) {
            echo '<span style="color:green">موجود ✓</span>';
        } else {
            echo '<span style="color:red">غير موجود ✗</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
    
    // التحقق من وجود مستخدمين من نوع صيدلي
    $query = "SELECT COUNT(*) FROM users WHERE role = 'pharmacist'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pharmacist_count = $stmt->fetchColumn();
    
    echo '<h3>فحص بيانات المستخدمين</h3>';
    echo '<ul>';
    echo '<li>عدد الصيادلة: ' . $pharmacist_count . '</li>';
    echo '</ul>';
    
} catch (Exception $e) {
    echo '<p style="color:red">خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage() . ' ✗</p>';
}

// فحص ملفات PHP الأساسية
echo '<h2>فحص ملفات PHP الأساسية</h2>';
$files = [
    'config/database.php',
    'includes/functions.php',
    'login.php',
    'logout.php',
    'pharmacist/dashboard.php',
    'pharmacy/dashboard.php',
    'pharmacist/header.php',
    'pharmacy/header.php'
];

echo '<ul>';
foreach ($files as $file) {
    echo '<li>' . $file . ': ';
    if (file_exists(__DIR__ . '/' . $file)) {
        echo '<span style="color:green">موجود ✓</span>';
    } else {
        echo '<span style="color:red">غير موجود ✗</span>';
    }
    echo '</li>';
}
echo '</ul>';

// فحص إعدادات PHP
echo '<h2>فحص إعدادات PHP</h2>';
echo '<ul>';
echo '<li>إصدار PHP: ' . phpversion() . '</li>';
echo '<li>تمكين عرض الأخطاء: ' . (ini_get('display_errors') ? '<span style="color:green">نعم ✓</span>' : '<span style="color:red">لا ✗</span>') . '</li>';
echo '<li>الحد الأقصى لمدة التنفيذ: ' . ini_get('max_execution_time') . ' ثانية</li>';
echo '<li>الحد الأقصى لحجم الذاكرة: ' . ini_get('memory_limit') . '</li>';
echo '</ul>';

// روابط مفيدة
echo '<h2>روابط مفيدة</h2>';
echo '<ul>';
echo '<li><a href="login.php">صفحة تسجيل الدخول</a></li>';
echo '<li><a href="pharmacist_login.php">تسجيل الدخول التلقائي كصيدلي</a></li>';
echo '<li><a href="pharmacy/dashboard.php">لوحة تحكم الصيدلية (pharmacy)</a></li>';
echo '<li><a href="pharmacist/dashboard.php">لوحة تحكم الصيدلية (pharmacist)</a></li>';
echo '<li><a href="test_pharmacy.php">اختبار صفحات الصيدلية</a></li>';
echo '<li><a href="debug.php">عرض معلومات تشخيصية</a></li>';
echo '<li><a href="pharmacy/error.php">صفحة فحص الأخطاء</a></li>';
echo '<li><a href="symlink.php">إعادة مزامنة المجلدات</a></li>';
echo '</ul>';
?> 