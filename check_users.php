<?php
// تفعيل عرض الأخطاء بشكل كامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// استيراد ملف قاعدة البيانات
require_once 'includes/config.php';

echo "<h1>قائمة المستخدمين في النظام</h1>";

try {
    // الاستعلام عن بيانات المستخدمين
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<div class='users-table'>";
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<thead><tr><th>#</th><th>اسم المستخدم</th><th>البريد الإلكتروني</th><th>نوع المستخدم</th><th>تاريخ الإنشاء</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username'] ?? 'غير محدد') . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div style='color: orange;'>⚠ لا يوجد مستخدمين في النظام</div>";
    }
    
    echo "<h2>تصحيح بيانات المستخدمين</h2>";
    echo "<p>اضغط على الزر التالي لتحديث أسماء المستخدمين في النظام</p>";
    
    if (isset($_GET['fix']) && $_GET['fix'] === 'usernames') {
        // تحديث قيم username بناءً على قيم البريد الإلكتروني
        $pdo->exec("UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL OR username = ''");
        echo "<div style='color: green;'>✓ تم تحديث أسماء المستخدمين بنجاح!</div>";
        echo "<p><a href='check_users.php'>تحديث الصفحة</a></p>";
    } else {
        echo "<p><a href='check_users.php?fix=usernames' class='btn'>تحديث أسماء المستخدمين</a></p>";
    }
    
    echo "<p><a href='login.php' class='btn'>العودة لصفحة تسجيل الدخول</a></p>";
    
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
    
    .users-table {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th {
        background-color: #4dc0b5;
        color: white;
    }
    
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    
    .btn {
        display: inline-block;
        background-color: #4dc0b5;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 0;
    }
    
    .btn:hover {
        background-color: #3daea3;
    }
    
    a {
        color: #0d6efd;
        text-decoration: none;
    }
    
    a:hover {
        text-decoration: underline;
    }
</style> 