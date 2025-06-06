<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// استعلام لجلب جميع الصيادلة
$query = "SELECT p.*, u.email, 'active' AS status 
          FROM pharmacists p 
          JOIN users u ON p.user_id = u.id
          ORDER BY p.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$pharmacists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// التعامل مع إضافة صيدلي جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pharmacist'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $license_number = trim($_POST['license_number']);
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        try {
            // التحقق من عدم وجود البريد الإلكتروني مسبقاً
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$email]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "البريد الإلكتروني مستخدم بالفعل";
            } else {
                // إضافة مستخدم جديد
                $pdo->beginTransaction();
                
                // إضافة في جدول المستخدمين
                $user_query = "INSERT INTO users (email, password, role, status, created_at) 
                              VALUES (?, ?, 'pharmacist', 'active', NOW())";
                $user_stmt = $pdo->prepare($user_query);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_stmt->execute([$email, $hashed_password]);
                
                $user_id = $pdo->lastInsertId();
                
                // إضافة في جدول الصيادلة
                $pharmacist_query = "INSERT INTO pharmacists (user_id, name, phone, address, license_number, created_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW())";
                $pharmacist_stmt = $pdo->prepare($pharmacist_query);
                $pharmacist_stmt->execute([$user_id, $name, $phone, $address, $license_number]);
                
                $pdo->commit();
                $success = "تمت إضافة الصيدلي بنجاح";
                
                // إعادة تحميل الصفحة لتحديث القائمة
                header("Location: pharmacists.php?success=added");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "حدث خطأ أثناء إضافة الصيدلي: " . $e->getMessage();
        }
    }
}

// التعامل مع حذف صيدلي
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pharmacist_id = $_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // الحصول على معرف المستخدم المرتبط بالصيدلي
        $get_user_query = "SELECT user_id FROM pharmacists WHERE id = ?";
        $get_user_stmt = $pdo->prepare($get_user_query);
        $get_user_stmt->execute([$pharmacist_id]);
        $user_id = $get_user_stmt->fetchColumn();
        
        if ($user_id) {
            // حذف الصيدلي
            $delete_pharmacist = "DELETE FROM pharmacists WHERE id = ?";
            $delete_stmt = $pdo->prepare($delete_pharmacist);
            $delete_stmt->execute([$pharmacist_id]);
            
            // حذف المستخدم المرتبط
            $delete_user = "DELETE FROM users WHERE id = ?";
            $delete_user_stmt = $pdo->prepare($delete_user);
            $delete_user_stmt->execute([$user_id]);
            
            $pdo->commit();
            $success = "تم حذف الصيدلي بنجاح";
            header("Location: pharmacists.php?success=deleted");
            exit;
        } else {
            throw new Exception("لم يتم العثور على الصيدلي");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "حدث خطأ أثناء حذف الصيدلي: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصيادلة - مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-action {
            margin: 0 5px;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
            <div class="alert alert-success">تمت إضافة الصيدلي بنجاح</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
            <div class="alert alert-success">تم حذف الصيدلي بنجاح</div>
        <?php endif; ?>
        
        <div class="row">
            <!-- بطاقة إضافة صيدلي جديد -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus"></i> إضافة صيدلي جديد</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">العنوان</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="license_number" class="form-label">رقم الترخيص</label>
                                <input type="text" class="form-control" id="license_number" name="license_number">
                            </div>
                            <button type="submit" name="add_pharmacist" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle"></i> إضافة صيدلي
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- قائمة الصيادلة -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users"></i> قائمة الصيادلة</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pharmacists)): ?>
                            <div class="alert alert-info">لا يوجد صيادلة مسجلين حالياً</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>رقم الهاتف</th>
                                            <th>رقم الترخيص</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pharmacists as $index => $pharmacist): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['phone'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($pharmacist['license_number'] ?? ''); ?></td>
                                                <td>
                                                    <?php if ($pharmacist['status'] === 'active'): ?>
                                                        <span class="status-active"><i class="fas fa-check-circle"></i> نشط</span>
                                                    <?php else: ?>
                                                        <span class="status-inactive"><i class="fas fa-times-circle"></i> غير نشط</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="pharmacists.php?delete=<?php echo $pharmacist['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('هل أنت متأكد من حذف هذا الصيدلي؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>