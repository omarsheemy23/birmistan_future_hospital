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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    
    $errors = [];
    
    // Validate username
    if (strlen($username) < 3) {
        $errors[] = "يجب أن يكون اسم المستخدم 3 أحرف على الأقل";
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }
    
    // Validate password
    if (strlen($password) < 6) {
        $errors[] = "يجب أن تكون كلمة المرور 6 أحرف على الأقل";
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "كلمات المرور غير متطابقة";
    }
    
    // Validate role
    $valid_roles = ['patient', 'doctor', 'nurse', 'pharmacist'];
    if (!in_array($role, $valid_roles)) {
        $errors[] = "نوع الحساب غير صالح";
    }
    
    if (empty($errors)) {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "اسم المستخدم موجود مسبقاً";
            } else {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = "البريد الإلكتروني موجود مسبقاً";
                } else {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Create user
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt->execute([$username, $email, $hashed_password, $role]);
                        
                        $user_id = $pdo->lastInsertId();
                        
                        // Create role-specific profile
                        if ($role === 'doctor') {
                            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialization) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$user_id, $first_name, $last_name, $_POST['specialization'] ?? '']);
                        } else if ($role === 'nurse') {
                            $stmt = $pdo->prepare("INSERT INTO nurses (user_id, first_name, last_name, department) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$user_id, $first_name, $last_name, $_POST['department'] ?? '']);
                        } else if ($role === 'pharmacist') {
                            $stmt = $pdo->prepare("INSERT INTO pharmacists (user_id, first_name, last_name, qualification) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$user_id, $first_name, $last_name, $_POST['qualification'] ?? '']);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $user_id,
                                $first_name,
                                $last_name,
                                $_POST['date_of_birth'] ?? null,
                                $_POST['gender'] ?? 'male'
                            ]);
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        $success = "تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.";
                        header("Location: " . SITE_URL . "/login.php?registered=1");
                        exit();
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = "حدث خطأ أثناء التسجيل. الرجاء المحاولة مرة أخرى.";
                        error_log($e->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ في قاعدة البيانات";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل جديد - مستشفى بارمستان المستقبل</title>
    <base href="<?php echo SITE_URL; ?>/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem 0;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 1.5rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        .role-section {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="mb-0">تسجيل حساب جديد</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">اسم المستخدم</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">كلمة المرور</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">نوع الحساب</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">اختر نوع الحساب</option>
                                    <option value="patient" <?php echo ($role ?? '') === 'patient' ? 'selected' : ''; ?>>مريض</option>
                                    <option value="doctor" <?php echo ($role ?? '') === 'doctor' ? 'selected' : ''; ?>>طبيب</option>
                                    <option value="nurse" <?php echo ($role ?? '') === 'nurse' ? 'selected' : ''; ?>>ممرض</option>
                                    <option value="pharmacist" <?php echo ($role ?? '') === 'pharmacist' ? 'selected' : ''; ?>>صيدلي</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">الاسم الأول</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">الاسم الأخير</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Patient Fields -->
                            <div id="patient-fields" class="role-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">تاريخ الميلاد</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">الجنس</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="male">ذكر</option>
                                                <option value="female">أنثى</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Doctor Fields -->
                            <div id="doctor-fields" class="role-section">
                                <div class="mb-3">
                                    <label for="specialization" class="form-label">التخصص</label>
                                    <input type="text" class="form-control" id="specialization" name="specialization">
                                </div>
                            </div>
                            
                            <!-- Nurse Fields -->
                            <div id="nurse-fields" class="role-section">
                                <div class="mb-3">
                                    <label for="department" class="form-label">القسم</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                            </div>
                            
                            <!-- Pharmacist Fields -->
                            <div id="pharmacist-fields" class="role-section">
                                <div class="mb-3">
                                    <label for="qualification" class="form-label">المؤهل</label>
                                    <input type="text" class="form-control" id="qualification" name="qualification">
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">تسجيل</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>لديك حساب بالفعل؟ <a href="<?php echo SITE_URL; ?>/login.php">تسجيل الدخول</a></p>
                            <p><a href="<?php echo SITE_URL; ?>/index.php">العودة للصفحة الرئيسية</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('role').addEventListener('change', function() {
            // Hide all role sections
            document.querySelectorAll('.role-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected role section
            const selectedRole = this.value;
            if (selectedRole) {
                document.getElementById(selectedRole + '-fields').style.display = 'block';
            }
        });

        // Show initial role section if role is pre-selected
        const initialRole = document.getElementById('role').value;
        if (initialRole) {
            document.getElementById(initialRole + '-fields').style.display = 'block';
        }
    </script>
</body>
</html> 