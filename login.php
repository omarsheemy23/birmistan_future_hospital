<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: " . SITE_URL . "/admin/dashboard.php");
            break;
        case 'doctor':
            header("Location: " . SITE_URL . "/doctor/dashboard.php");
            break;
        case 'nurse':
            header("Location: " . SITE_URL . "/nurse/dashboard.php");
            break;
        case 'pharmacist':
            header("Location: " . SITE_URL . "/pharmacist/dashboard.php");
            break;
        case 'patient':
            header("Location: " . SITE_URL . "/patient/dashboard.php");
            break;
        default:
            header("Location: " . SITE_URL . "/index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = "الرجاء إدخال اسم المستخدم وكلمة المرور واختيار نوع المستخدم";
    } else {
        try {
            // تسجيل المحاولة في ملف السجل للتصحيح
            error_log("Login attempt - Username: $username, Role: $role");
            
            // التحقق ما إذا كان المدخل هو بريد إلكتروني
            $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
            
            if ($is_email) {
                // البحث باستخدام البريد الإلكتروني
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
            } else {
                // البحث باستخدام اسم المستخدم
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
            }
            
            $stmt->execute([$username, $role]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // تسجيل معلومات المستخدم للتصحيح
                error_log("User found - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}");
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: " . SITE_URL . "/admin/dashboard.php");
                            break;
                        case 'doctor':
                            header("Location: " . SITE_URL . "/doctor/dashboard.php");
                            break;
                        case 'nurse':
                            header("Location: " . SITE_URL . "/nurse/dashboard.php");
                            break;
                        case 'pharmacist':
                            header("Location: " . SITE_URL . "/pharmacist/dashboard.php");
                            break;
                        case 'patient':
                            header("Location: " . SITE_URL . "/patient/dashboard.php");
                            break;
                        default:
                            header("Location: " . SITE_URL . "/index.php");
                    }
                    exit();
                } else {
                    $error = "كلمة المرور غير صحيحة";
                    error_log("Password verification failed");
                }
            } else {
                $error = "بيانات المستخدم غير صحيحة";
                // استعلام لمعرفة أي مستخدمين لديهم اسم المستخدم المدخل (بغض النظر عن الدور)
                $debug_stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE username = ? OR email = ?");
                $debug_stmt->execute([$username, $username]);
                $debug_users = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("No user found with username/email: $username and role: $role");
                
                if (count($debug_users) > 0) {
                    error_log("Similar users found (different role): " . json_encode($debug_users));
                } else {
                    error_log("No similar users found with username/email: $username");
                }
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ في قاعدة البيانات";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام مستشفى بيرمستان - تسجيل الدخول</title>
    <base href="<?php echo SITE_URL; ?>/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
        }
        .login-container {
            max-width: 480px;
            width: 100%;
            background-color: #2c3e50;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            overflow: hidden;
            margin: 0 auto;
        }
        .login-header {
            text-align: center;
            padding: 2rem 0 1rem;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%);
            border-radius: 50%;
            position: relative;
            z-index: 1;
            animation: flicker-bg 5s linear infinite;
        }
        
        @keyframes flicker-bg {
            0% { background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%); }
            25% { background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0) 50%); }
            50% { background: radial-gradient(circle, rgba(255,255,255,0.07) 0%, rgba(255,255,255,0) 55%); }
            75% { background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0) 45%); }
            100% { background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%); }
        }
        .login-title {
            font-size: 2.5rem;
            color: #cccccc;
            margin-bottom: 1rem;
            text-shadow: 0 0 3px #ffffff, 0 0 5px #ffffff;
            animation: flickering 4s linear infinite;
            font-weight: bold;
            position: relative;
        }
        
        .login-title::before {
            content: "مستشفى بيرمستان";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            animation: buzz 0.1s infinite alternate;
            opacity: 0.5;
            color: rgba(255,255,255,0.7);
            filter: blur(1px);
            display: block;
        }
        
        @keyframes buzz {
            0% { transform: translateX(-1px); }
            100% { transform: translateX(1px); }
        }
        
        @keyframes flickering {
            0% { opacity: 1; text-shadow: 0 0 2px #ffffff; }
            3% { opacity: 0.4; }
            5% { opacity: 0.8; }
            8% { opacity: 0.1; }
            10% { opacity: 1; text-shadow: 0 0 4px #ffffff; }
            15% { opacity: 0.3; text-shadow: 0 0 1px #ffffff; }
            16% { opacity: 1; }
            17% { opacity: 0.2; }
            19% { opacity: 1; }
            20% { opacity: 1; text-shadow: 0 0 6px #ffffff; }
            25% { opacity: 0.6; text-shadow: 0 0 2px #ffffff; }
            30% { opacity: 1; }
            45% { opacity: 0.7; }
            48% { opacity: 0.1; }
            50% { opacity: 1; text-shadow: 0 0 5px #ffffff; }
            52% { opacity: 0.2; }
            55% { opacity: 0.9; }
            70% { opacity: 0.8; text-shadow: 0 0 4px #ffffff; }
            72% { opacity: 0.2; }
            73% { opacity: 0.9; }
            75% { opacity: 0.1; }
            77% { opacity: 0.9; text-shadow: 0 0 5px #ffffff; }
            85% { opacity: 1; }
            92% { opacity: 0.2; }
            95% { opacity: 0.4; }
            100% { opacity: 1; text-shadow: 0 0 3px #ffffff; }
        }
        .login-form {
            padding: 1.5rem 2rem 2rem;
            background-color: #fff;
        }
        .form-control {
            height: 50px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            text-align: center;
            color: #555;
        }
        .form-control:focus {
            border-color: #4dc0b5;
            box-shadow: 0 0 0 0.2rem rgba(77, 192, 181, 0.25);
        }
        .role-options {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            gap: 15px;
        }
        .role-option {
            display: flex;
            align-items: center;
        }
        .role-option input {
            margin-left: 5px;
        }
        .btn-login {
            display: block;
            width: 100%;
            background-color: #4dc0b5;
            border: none;
            color: white;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #3daea3;
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            color: #3daea3;
            text-decoration: none;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            border-top: 1px solid #f2f2f2;
            padding: 1rem 0;
            color: #777;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        .footer-links a {
            color: #777;
            text-decoration: none;
            margin: 0 10px;
        }
        .hospital-info {
            text-align: center;
            color: #777;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="login-container">
            <div class="login-header">
                <h1 class="login-title">مستشفى بيرمستان</h1>
            </div>

            <div class="login-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center mb-3"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="text" class="form-control" name="username" placeholder="اسم المستخدم" required>
                    <input type="password" class="form-control" name="password" placeholder="كلمة المرور" required>
                    
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="admin" name="role" value="admin">
                            <label for="admin">مسؤول النظام</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="doctor" name="role" value="doctor">
                            <label for="doctor">طبيب</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="patient" name="role" value="patient">
                            <label for="patient">مريض</label>
                        </div>
                    </div>
                    
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="pharmacist" name="role" value="pharmacist">
                            <label for="pharmacist">صيدلي</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="nurse" name="role" value="nurse">
                            <label for="nurse">ممرض</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">تسجيل الدخول</button>
                </form>
                
                <div class="forgot-password">
                    <a href="#">نسيت كلمة المرور؟</a>
                </div>
            </div>
            
            <div class="footer-links">
                <a href="#">سياسة الخصوصية</a>
                <a href="#">الدليل الإرشادي</a>
            </div>
            
            <div class="hospital-info">
                بواسطة تقنية مستشفى بيرمستان - جميع الحقوق محفوظة 2024
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 