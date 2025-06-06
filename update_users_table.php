<?php
// تفعيل عرض الأخطاء بشكل كامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// استيراد ملف قاعدة البيانات
require_once 'includes/config.php';

echo "<h1>تحديث جدول المستخدمين</h1>";

try {
    // التحقق من وجود حقل username في جدول users
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    
    if ($check_column->rowCount() == 0) {
        // إضافة حقل username إذا لم يكن موجوداً
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) AFTER email");
        echo "<div style='color: green;'>✓ تم إضافة حقل username إلى جدول users بنجاح</div>";
        
        // تحديث قيم username بناءً على قيم البريد الإلكتروني
        $pdo->exec("UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL");
        echo "<div style='color: green;'>✓ تم تحديث قيم username بناءً على البريد الإلكتروني</div>";
        
        // إضافة قيد فريد على حقل username
        $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX idx_username (username)");
        echo "<div style='color: green;'>✓ تم إضافة قيد فريد على حقل username</div>";
    } else {
        echo "<div style='color: blue;'>✓ حقل username موجود بالفعل في جدول users</div>";
    }
    
    // عرض رسالة نجاح
    echo "<h2 style='color: green;'>تم تحديث جدول المستخدمين بنجاح!</h2>";
    echo "<p>يمكنك الآن <a href='login.php'>الانتقال إلى صفحة تسجيل الدخول</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
    error_log("Database Error: " . $e->getMessage());
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        direction: rtl;
        text-align: right;
        line-height: 1.6;
        margin: 20px;
        background-color: #f8f9fa;
    }
    
    h1, h2 {
        color: #1e3c72;
    }
    
    div {
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    a {
        color: #0d6efd;
        text-decoration: none;
    }
    
    a:hover {
        text-decoration: underline;
    }
</style> 