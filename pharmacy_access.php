<?php
// تفعيل عرض الأخطاء للتشخيص
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل معلومات التشخيص
error_log("Pharmacy Access Redirect - " . date('Y-m-d H:i:s'));

// توجيه إلى صفحة الوصول المباشر
header("Location: pharmacy/direct_access.php");
exit();
?> 