<?php
// تفعيل عرض الأخطاء بشكل كامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// استيراد ملف قاعدة البيانات
require_once 'includes/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        try {
            // التحقق من وجود مستخدم بنفس البريد الإلكتروني أو اسم المستخدم
            $check_stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $check_stmt->execute([$email, $username]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "البريد الإلكتروني أو اسم المستخدم مستخدم بالفعل";
            } else {
                // تشفير كلمة المرور
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // إضافة المستخدم الجديد
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                
                $success = "تم إنشاء المستخدم بنجاح!";
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
            error_log("Database Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء مستخدم تجريبي - مستشفى بيرمستان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            text-align: right;
            line-height: 1.6;
            margin: 20px;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #1e3c72;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        
        .btn-primary {
            background-color: #4dc0b5;
            border-color: #4dc0b5;
            padding: 10px 20px;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background-color: #3daea3;
            border-color: #3daea3;
        }
        
        .links {
            margin-top: 20px;
            text-align: center;
        }
        
        .links a {
            margin: 0 10px;
            color: #4dc0b5;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إنشاء مستخدم تجريبي</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
            <div class="links">
                <a href="login.php">الذهاب إلى صفحة تسجيل الدخول</a>
                <a href="create_test_user.php">إنشاء مستخدم آخر</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">نوع المستخدم</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="">-- اختر نوع المستخدم --</option>
                        <option value="admin">مسؤول النظام</option>
                        <option value="doctor">طبيب</option>
                        <option value="nurse">ممرض</option>
                        <option value="pharmacist">صيدلي</option>
                        <option value="patient">مريض</option>
                    </select>
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary">إنشاء المستخدم</button>
                </div>
            </form>
            
            <div class="links">
                <a href="login.php">العودة لصفحة تسجيل الدخول</a>
                <a href="check_users.php">عرض المستخدمين الحاليين</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 