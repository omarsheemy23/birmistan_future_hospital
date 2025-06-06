<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>اختبار عرض الأخطاء</h1>";

// معلومات PHP
echo "<h2>معلومات PHP</h2>";
echo "<p>إصدار PHP: " . phpversion() . "</p>";

// اختبار الاتصال بقاعدة البيانات
echo "<h2>اختبار الاتصال بقاعدة البيانات</h2>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color:green'>تم الاتصال بقاعدة البيانات بنجاح!</p>";
    
    // اختبار استعلام بسيط
    $query = "SELECT COUNT(*) FROM users WHERE role = 'pharmacist'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo "<p>عدد الصيادلة في قاعدة البيانات: " . $count . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>";
}

// التحقق من وجود الملفات
echo "<h2>التحقق من وجود الملفات</h2>";
$files_to_check = [
    'dashboard.php',
    'header.php',
    'footer.php',
    'dispense_medicine.php',
    'inventory.php',
    'reports.php',
    'view_prescriptions.php'
];

echo "<ul>";
foreach ($files_to_check as $file) {
    echo "<li>" . $file . ": " . (file_exists($file) ? '<span style="color:green">موجود</span>' : '<span style="color:red">غير موجود</span>') . "</li>";
}
echo "</ul>";

// التحقق من متغيرات الجلسة
echo "<h2>معلومات الجلسة</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";
?> 