<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get doctor data
try {
    $stmt = $db->prepare("
        SELECT d.*, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        $_SESSION['error'] = "الطبيب غير موجود";
        header("Location: doctors.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "خطأ في جلب بيانات الطبيب";
    header("Location: doctors.php");
    exit();
}

// Get all departments
try {
    $stmt = $db->query("SELECT * FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "خطأ في جلب بيانات الأقسام";
    header("Location: doctors.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Check if email already exists and is different from current doctor's email
        $email = $_POST['email'];
        if ($email !== $doctor['email']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $doctor['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("البريد الإلكتروني '{$email}' مستخدم بالفعل. الرجاء استخدام بريد إلكتروني آخر.");
            }
        }

        // Handle profile picture upload
        $profile_picture = $doctor['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/doctors/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $profile_picture = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $profile_picture;
            
            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                throw new Exception('فشل في رفع صورة الملف الشخصي');
            }
        }

        // Update user account
        $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$_POST['email'], $doctor['user_id']]);

        // Update doctor record        
        try {            
            // First check if payment_card column exists            
            $stmt = $db->prepare("SHOW COLUMNS FROM doctors LIKE 'payment_card'");            
            $stmt->execute();            
            $paymentCardExists = $stmt->rowCount() > 0;                        
            
            if ($paymentCardExists) {                
                // Update doctor record with payment_card                
                $stmt = $db->prepare("                    
                    UPDATE doctors SET                         
                        first_name = ?,                        
                        last_name = ?,                        
                        specialization = ?,                        
                        department_id = ?,                        
                        qualification = ?,                        
                        experience = ?,                        
                        consultation_fee = ?,                        
                        profile_picture = ?,                        
                        payment_card = ?                    
                    WHERE id = ?                
                ");                                
                
                $stmt->execute([                    
                    $_POST['first_name'],                    
                    $_POST['last_name'],                    
                    $_POST['specialization'],                    
                    $_POST['department_id'],                    
                    $_POST['qualification'],                    
                    $_POST['experience'],                    
                    $_POST['consultation_fee'],                    
                    $profile_picture,                    
                    $_POST['payment_card'],                    
                    $doctor_id                
                ]);            
            } else {                
                // Update doctor record without payment_card                
                $stmt = $db->prepare("                    
                    UPDATE doctors SET                         
                        first_name = ?,                        
                        last_name = ?,                        
                        specialization = ?,                        
                        department_id = ?,                        
                        qualification = ?,                        
                        experience = ?,                        
                        consultation_fee = ?,                        
                        profile_picture = ?                    
                    WHERE id = ?                
                ");                                
                
                $stmt->execute([                    
                    $_POST['first_name'],                    
                    $_POST['last_name'],                    
                    $_POST['specialization'],                    
                    $_POST['department_id'],                    
                    $_POST['qualification'],                    
                    $_POST['experience'],                    
                    $_POST['consultation_fee'],                    
                    $profile_picture,                    
                    $doctor_id                
                ]);            
            }        
        } catch (Exception $e) {            
            // Handle exception            
            throw new Exception("خطأ في تحديث سجل الطبيب: " . $e->getMessage());        
        }

        $db->commit();
        $_SESSION['success'] = "تم تحديث بيانات الطبيب بنجاح";
        header("Location: doctors.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "خطأ في تحديث بيانات الطبيب: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات الطبيب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-primary {
            border-radius: 25px;
            padding: 8px 25px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">تعديل بيانات الطبيب</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاسم الأول</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاسم الأخير</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">التخصص</label>
                                    <input type="text" name="specialization" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">القسم</label>
                                    <select name="department_id" class="form-select" required>
                                        <option value="">اختر القسم</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo $department['id']; ?>" 
                                                    <?php echo $doctor['department_id'] == $department['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($department['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">المؤهل العلمي</label>
                                    <input type="text" name="qualification" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">سنوات الخبرة</label>
                                    <input type="number" name="experience" class="form-control" 
                                           value="<?php echo htmlspecialchars($doctor['experience']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رسوم الاستشارة (جنيه مصري)</label>
                                    <input type="number" name="consultation_fee" class="form-control" 
                                           value="<?php echo htmlspecialchars((string)$doctor['consultation_fee']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رقم البطاقة للدفع</label>
                                    <input type="text" name="payment_card" class="form-control" required 
                                           pattern="[0-9]{14,19}" maxlength="19" 
                                           placeholder="أدخل رقم البطاقة (14-19 رقم بدون مسافات)"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                           value="<?php echo htmlspecialchars($doctor['payment_card'] ?? ''); ?>">
                                    <small class="text-muted">يجب إدخال أرقام فقط (14-19 رقم) بدون مسافات أو رموز</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">صورة الملف الشخصي</label>
                                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                    <?php if ($doctor['profile_picture']): ?>
                                        <div class="mt-2">
                                            <img src="../uploads/doctors/<?php echo htmlspecialchars($doctor['profile_picture']); ?>" 
                                                 alt="صورة الطبيب" style="max-width: 100px; border-radius: 10px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <a href="doctors.php" class="btn btn-secondary">إلغاء</a>
                                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // تنسيق رقم البطاقة أثناء الكتابة
        document.addEventListener('DOMContentLoaded', function() {
            const paymentCardInput = document.querySelector('input[name="payment_card"]');
            
            if (paymentCardInput) {
                paymentCardInput.addEventListener('input', function(e) {
                    // حذف أي حرف غير رقمي
                    let value = this.value.replace(/\D/g, '');
                    
                    // لا تسمح بأكثر من 19 رقم
                    if (value.length > 19) {
                        value = value.substring(0, 19);
                    }
                    
                    // تعيين القيمة النهائية (أرقام فقط)
                    this.value = value;
                });
                
                // إعادة تنسيق القيمة عند محاولة إرسال النموذج
                const form = paymentCardInput.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        // التأكد من أن القيمة تتكون من أرقام فقط قبل الإرسال
                        paymentCardInput.value = paymentCardInput.value.replace(/\D/g, '');
                    });
                }
            }
            
            // التحقق من البريد الإلكتروني
            const emailInput = document.querySelector('input[name="email"]');
            const originalEmail = <?php echo json_encode($doctor['email']); ?>;
            
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    // إزالة أي تنبيه سابق
                    const existingWarning = this.parentNode.querySelector('.email-warning');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                    
                    // التحقق من البريد الإلكتروني
                    const email = this.value.trim().toLowerCase();
                    if (email !== originalEmail && (email === 'admin@hospital.com' || email.includes('admin@'))) {
                        // إنشاء تنبيه
                        const warning = document.createElement('div');
                        warning.className = 'alert alert-warning mt-2 email-warning';
                        warning.innerHTML = 'هذا البريد الإلكتروني قد يكون مستخدماً بالفعل. الرجاء استخدام بريد إلكتروني آخر.';
                        this.parentNode.appendChild(warning);
                    }
                });
            }
        });
    </script>
</body>
</html> 