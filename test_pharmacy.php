<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>اختبار صفحات الصيدلية</h1>";

// اختبار الوصول إلى pharmacy/dashboard.php
echo "<h2>اختبار الوصول إلى pharmacy/dashboard.php</h2>";

// التحقق من وجود الملفات
echo "<h3>فحص وجود الملفات</h3>";
$directories = [
    'pharmacy',
    'pharmacist'
];

$files = [
    'dashboard.php',
    'header.php',
    'footer.php',
    'dispense_medicine.php',
    'inventory.php',
    'view_prescriptions.php',
    'reports.php'
];

foreach ($directories as $dir) {
    echo "<h4>المجلد: $dir</h4>";
    echo "<ul>";
    
    if (!is_dir($dir)) {
        echo "<li style='color:red'>المجلد غير موجود!</li>";
    } else {
        foreach ($files as $file) {
            $full_path = $dir . '/' . $file;
            echo "<li>$file: " . (file_exists($full_path) ? "<span style='color:green'>موجود</span>" : "<span style='color:red'>غير موجود</span>") . "</li>";
        }
    }
    
    echo "</ul>";
}

// عرض روابط الاختبار
echo "<h3>روابط الاختبار</h3>";
echo "<ul>";
echo "<li><a href='pharmacy/dashboard.php' target='_blank'>فتح لوحة تحكم الصيدلية (pharmacy/dashboard.php)</a></li>";
echo "<li><a href='pharmacy/error.php' target='_blank'>اختبار الأخطاء (pharmacy/error.php)</a></li>";
echo "<li><a href='pharmacist/dashboard.php' target='_blank'>فتح لوحة تحكم الصيدلية (pharmacist/dashboard.php)</a></li>";
echo "<li><a href='pharmacist/test.php' target='_blank'>اختبار ملف test.php (pharmacist/test.php)</a></li>";
echo "<li><a href='direct_access.php' target='_blank'>الوصول المباشر (direct_access.php)</a></li>";
echo "<li><a href='symlink.php' target='_blank'>إعادة مزامنة المجلدات (symlink.php)</a></li>";
echo "</ul>";

// عرض عنوان URL الحالي
echo "<h3>معلومات الصفحة الحالية</h3>";
echo "<p>عنوان URL الكامل: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]</p>";
echo "<p>مسار المجلد الحالي: " . __DIR__ . "</p>";
echo "<p>Server Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// عرض معلومات متغيرات الجلسة
echo "<h3>معلومات الجلسة</h3>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";
?> 