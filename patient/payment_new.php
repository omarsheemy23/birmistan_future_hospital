<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// الحصول على معرف الموعد من الرابط أو من الجلسة
$appointment_id = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : 
                 (isset($_SESSION['appointment_id']) ? $_SESSION['appointment_id'] : null);

// تحديد رسوم الاستشارة من الجلسة أو تعيين قيمة افتراضية
$consultation_fee = isset($_SESSION['consultation_fee']) ? $_SESSION['consultation_fee'] : 300;

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// معلومات الطبيب والتاريخ والوقت
$doctor_name = "الطبيب المختار";
$appointment_date = date('Y-m-d');
$appointment_time = date('H:i');
$department_name = "القسم المختار";

// محاولة الحصول على معلومات الموعد إذا كان متوفراً
if ($appointment_id) {
    try {
        $query = "SELECT a.appointment_date, a.appointment_time, a.doctor_id, 
                 d.first_name, d.last_name, d.department_id, 
                 dep.name as department_name 
                 FROM appointments a 
                 LEFT JOIN doctors d ON a.doctor_id = d.id 
                 LEFT JOIN departments dep ON d.department_id = dep.id 
                 WHERE a.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appointment) {
            $doctor_name = "د. " . ($appointment['first_name'] ?? '') . " " . ($appointment['last_name'] ?? '');
            $appointment_date = $appointment['appointment_date'] ?? date('Y-m-d');
            $appointment_time = $appointment['appointment_time'] ?? date('H:i');
            $department_name = $appointment['department_name'] ?? "القسم المختار";
        }
    } catch (Exception $e) {
        // استمر بدون اي أخطاء
    }
}

// معالجة عملية الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['card_number']) || empty($_POST['expiry_date']) || empty($_POST['cvv'])) {
            throw new Exception("جميع حقول الدفع مطلوبة");
        }

        if (!$appointment_id) {
            throw new Exception("معرف الموعد غير متوفر");
        }

        // بدء المعاملة
        $db->beginTransaction();
        
        // تحديث حالة الدفع في جدول المواعيد
        $update_query = "UPDATE appointments 
                       SET payment_status = 'paid', status = 'confirmed' 
                       WHERE id = ?";
                       
        $update_stmt = $db->prepare($update_query);
        if (!$update_stmt->execute([$appointment_id])) {
            throw new Exception("فشل في تحديث حالة الدفع");
        }
        
        // محاولة إضافة سجل في جدول المدفوعات (إذا كان موجوداً)
        try {
            $payment_query = "INSERT INTO payments (appointment_id, amount, payment_status, payment_method, transaction_id) 
                            VALUES (?, ?, 'completed', 'credit_card', ?)";
            
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            $payment_stmt = $db->prepare($payment_query);
            $payment_stmt->execute([$appointment_id, $consultation_fee, $transaction_id]);
        } catch (Exception $e) {
            // تجاهل الأخطاء المتعلقة بجدول المدفوعات
        }
        
        // إتمام المعاملة
        $db->commit();
        
        // تخزين رسالة النجاح وإعادة التوجيه
        $_SESSION['success_message'] = "تم الدفع بنجاح وتأكيد الموعد!";
        unset($_SESSION['appointment_id']); // حذف متغيرات الجلسة المؤقتة
        
        header("Location: appointments.php");
        exit();
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدفع - مستشفى بيرمستان المستقبلية</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-hospital me-2"></i>مستشفى بيرمستان المستقبلية</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="appointments.php"><i class="fas fa-calendar-check me-1"></i>مواعيدي</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>تسجيل الخروج</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">إتمام عملية الدفع</h2>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info mb-4">
                            <h5 class="mb-3">تفاصيل الموعد:</h5>
                            <p class="mb-2">الطبيب: <?php echo htmlspecialchars($doctor_name); ?></p>
                            <p class="mb-2">القسم: <?php echo htmlspecialchars($department_name); ?></p>
                            <p class="mb-2">التاريخ: <?php echo htmlspecialchars(date('Y-m-d', strtotime($appointment_date))); ?></p>
                            <p class="mb-2">الوقت: <?php echo htmlspecialchars(date('H:i', strtotime($appointment_time))); ?></p>
                            <p class="mb-2">رسوم الكشف: <?php echo number_format($consultation_fee, 2); ?> جنيه مصري</p>
                            <small class="text-muted">* يتم الدفع مرة واحدة فقط عند تأكيد الموعد</small>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="card_number" class="form-label fw-bold">رقم البطاقة</label>
                                <input type="text" class="form-control form-control-lg" id="card_number" 
                                       name="card_number" placeholder="1234 5678 9012 3456" required>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="expiry_date" class="form-label fw-bold">تاريخ الانتهاء</label>
                                    <input type="text" class="form-control form-control-lg" id="expiry_date" 
                                           name="expiry_date" placeholder="MM/YY" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="cvv" class="form-label fw-bold">رمز الأمان CVV</label>
                                    <input type="text" class="form-control form-control-lg" id="cvv" 
                                           name="cvv" placeholder="123" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">إتمام الدفع</button>
                                <a href="appointments.php" class="btn btn-outline-secondary btn-lg">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // تنسيق رقم البطاقة
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue.substring(0, 19);
    });

    // تنسيق تاريخ الانتهاء
    document.getElementById('expiry_date').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        e.target.value = value.substring(0, 5);
    });

    // تنسيق CVV
    document.getElementById('cvv').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        e.target.value = value.substring(0, 3);
    });
    </script>
</body>
</html> 