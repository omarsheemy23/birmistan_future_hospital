<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Handle doctor deletion
if (isset($_POST['delete_doctor']) && isset($_POST['doctor_id'])) {
    try {
        $db->beginTransaction();
        
        // Get user_id before deleting doctor
        $stmt = $db->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$_POST['doctor_id']]);
        $user_id = $stmt->fetchColumn();
        
        // Delete doctor
        $stmt = $db->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->execute([$_POST['doctor_id']]);
        
        // Delete user
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $db->commit();
        $_SESSION['success'] = "تم حذف الطبيب بنجاح";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "خطأ في حذف الطبيب: " . $e->getMessage();
    }
    
    header("Location: doctors.php");
    exit();
}

// Get search query if exists
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all doctors with their department and user email
try {
    $query = "
        SELECT d.*, u.email, dept.name as department_name 
        FROM doctors d 
        LEFT JOIN users u ON d.user_id = u.id 
        LEFT JOIN departments dept ON d.department_id = dept.id 
    ";
    
    if (!empty($search)) {
        $query .= " WHERE d.first_name LIKE :search 
                   OR d.last_name LIKE :search 
                   OR d.specialization LIKE :search 
                   OR dept.name LIKE :search 
                   OR u.email LIKE :search";
    }
    
    $query .= " ORDER BY d.first_name, d.last_name";
    
    $stmt = $db->prepare($query);
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
    }
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "خطأ في جلب بيانات الأطباء: " . $e->getMessage();
    $doctors = [];
}

// Get all departments for the dropdown
try {
    $stmt = $db->query("SELECT * FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "خطأ في جلب بيانات الأقسام: " . $e->getMessage();
    $departments = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأطباء</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            transition: transform 0.2s;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .doctor-card {
            border: none;
            border-radius: 15px;
        }
        .doctor-card .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .doctor-card .card-body {
            padding: 20px;
        }
        .doctor-info {
            margin-bottom: 15px;
        }
        .doctor-info i {
            color: #0d6efd;
            margin-left: 10px;
        }
        .search-container {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .search-input {
            border-radius: 25px;
            padding-right: 40px;
        }
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }
        .action-buttons .btn {
            margin-left: 5px;
            border-radius: 20px;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
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

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Main Content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2">إدارة الأطباء</h1>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                            <i class="fas fa-plus"></i> إضافة طبيب جديد
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['info'];
                        unset($_SESSION['info']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search Section -->
                <div class="search-container">
                    <form method="GET" class="position-relative">
                        <input type="text" name="search" class="form-control search-input" 
                               placeholder="ابحث عن طبيب..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </form>
                </div>

                <!-- Doctors Grid -->
                <div class="row">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card doctor-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        د. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="doctor-info">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($doctor['email']); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <i class="fas fa-stethoscope"></i>
                                        <?php echo htmlspecialchars($doctor['specialization']); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <i class="fas fa-hospital"></i>
                                        <?php echo htmlspecialchars($doctor['department_name'] ?? 'غير محدد'); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($doctor['qualification']); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($doctor['experience']); ?> سنوات خبرة
                                    </div>
                                    <div class="doctor-info">
                                        <i class="fas fa-money-bill-wave"></i>
                                        رسوم الاستشارة: <?php echo htmlspecialchars((string)$doctor['consultation_fee']); ?> جنيه مصري
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="status-badge <?php echo $doctor['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $doctor['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                        </span>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editDoctor(<?php echo $doctor['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطبيب؟');">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                                <button type="submit" name="delete_doctor" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة طبيب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_doctor.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم الأول</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم الأخير</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">التخصص</label>
                                <input type="text" name="specialization" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">القسم</label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">اختر القسم</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>">
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">المؤهل العلمي</label>
                                <input type="text" name="qualification" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">سنوات الخبرة</label>
                                <input type="number" name="experience" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رسوم الاستشارة (جنيه مصري)</label>
                                <input type="number" name="consultation_fee" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم البطاقة للدفع</label>
                                <input type="text" name="payment_card" class="form-control" required 
                                       pattern="[0-9]{14,19}" maxlength="19" 
                                       placeholder="أدخل رقم البطاقة (14-19 رقم بدون مسافات)"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <small class="text-muted">يجب إدخال أرقام فقط (14-19 رقم) بدون مسافات أو رموز</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">صورة الملف الشخصي</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDoctor(id) {
            // Implement edit functionality
            window.location.href = `edit_doctor.php?id=${id}`;
        }
        
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
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    // إزالة أي تنبيه سابق
                    const existingWarning = this.parentNode.querySelector('.email-warning');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                    
                    // التحقق من البريد الإلكتروني
                    const email = this.value.trim().toLowerCase();
                    if (email === 'admin@hospital.com' || email.includes('admin@')) {
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