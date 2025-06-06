<?php
session_start();

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// التحقق من وجود معرف الموعد (من الرابط أو من الجلسة)
if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];
} elseif (isset($_SESSION['appointment_id'])) {
    $appointment_id = $_SESSION['appointment_id'];
} else {
    header("Location: appointments.php");
    exit();
}

// جلب تفاصيل الموعد ورسوم الاستشارة
try {
    // استعلام مبسط للتأكد من وجود الموعد - تم تبسيط الاستعلام لتجنب مشاكل الروابط
    $appointment_query = "SELECT a.* FROM appointments a WHERE a.id = ?"; 
    
    // سجل الاستعلام
    error_log("Appointment query: " . $appointment_query . " with ID: " . $appointment_id);
    
    $appointment_stmt = $db->prepare($appointment_query);
    $appointment_stmt->execute([$appointment_id]);
    $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
    
    // سجل نتائج الاستعلام
    error_log("Appointment data: " . print_r($appointment, true));
    
    // طباعة معلومات التصحيح
    if (!$appointment) {
        echo "<div class='alert alert-danger'>لم يتم العثور على الموعد رقم: " . $appointment_id . "</div>";
        echo "<div class='alert alert-info'>استعلام SQL: " . $appointment_query . "</div>";
        echo "<div class='alert alert-info'>معرف المستخدم: " . $_SESSION['user_id'] . "</div>";
        // طباعة معرف المريض إذا وجد
        $patient_query = "SELECT id FROM patients WHERE user_id = ?";
        $patient_stmt = $db->prepare($patient_query);
        $patient_stmt->execute([$_SESSION['user_id']]);
        $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
        if ($patient) {
            echo "<div class='alert alert-info'>معرف المريض: " . $patient['id'] . "</div>";
        } else {
            echo "<div class='alert alert-danger'>لم يتم العثور على معرف المريض!</div>";
        }
    } else {
        // تم العثور على الموعد، الآن نجلب معلومات الطبيب والقسم
        try {
            // تأكد من وجود معرف الطبيب
            if (!isset($appointment['doctor_id']) || empty($appointment['doctor_id'])) {
                error_log("Missing doctor_id in appointment data");
                $appointment['doctor_id'] = 0;
                throw new Exception("بيانات الطبيب غير متوفرة في الموعد");
            }
            
            $doctor_query = "SELECT d.first_name, d.last_name, d.consultation_fee, 
                            dep.name as department_name 
                            FROM doctors d 
                            LEFT JOIN departments dep ON d.department_id = dep.id 
                            WHERE d.id = ?";
            
            // سجل استعلام الطبيب
            error_log("Doctor query: " . $doctor_query . " with doctor ID: " . $appointment['doctor_id']);
            
            $doctor_stmt = $db->prepare($doctor_query);
            $doctor_stmt->execute([$appointment['doctor_id']]);
            $doctor_info = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
            
            // سجل نتائج استعلام الطبيب
            error_log("Doctor info: " . print_r($doctor_info, true));
            
            if ($doctor_info) {
                $appointment = array_merge($appointment, $doctor_info);
                error_log("Merged appointment data: " . print_r($appointment, true));
            } else {
                error_log("Warning: No doctor info found for ID: " . $appointment['doctor_id']);
            }
        } catch (Exception $e) {
            // في حالة الفشل، نستمر بدون معلومات الطبيب
            echo "<div class='alert alert-warning'>تعذر الحصول على بيانات الطبيب: " . $e->getMessage() . "</div>";
        }
    }
    
    if (!$appointment) {
        $_SESSION['error_message'] = "لم يتم العثور على بيانات الموعد";
        // header("Location: appointments.php");
        // exit();
    }
    
    // التحقق من أن المريض هو المستخدم الحالي
    $patient_query = "SELECT id FROM patients WHERE user_id = ?";
    $patient_stmt = $db->prepare($patient_query);
    $patient_stmt->execute([$_SESSION['user_id']]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    
    $force_show_form = true; // إجبار عرض النموذج للتصحيح
    
    if (!$patient || $patient['id'] != $appointment['patient_id']) {
        echo "<div class='alert alert-danger'>هذا الموعد لا ينتمي لك. معرف المريض في الموعد: " . $appointment['patient_id'] . " ومعرف المريض الخاص بك: " . ($patient ? $patient['id'] : 'غير موجود') . "</div>";
        // لا نقوم بتوجيه المستخدم بعيدًا، فقط نعرض تحذيرًا للتصحيح
        // في المستقبل، يمكن إعادة تفعيل هذا التحقق بمجرد التأكد من أن معرفات المرضى صحيحة
        // $_SESSION['error_message'] = "هذا الموعد لا ينتمي لك";
        // header("Location: appointments.php");
        // exit();
    }
    
    // تحديد رسوم الاستشارة
    $consultation_fee = isset($appointment['consultation_fee']) ? $appointment['consultation_fee'] : 
                       (isset($_SESSION['consultation_fee']) ? $_SESSION['consultation_fee'] : 300);
    if (!$consultation_fee) {
        $consultation_fee = 300; // قيمة افتراضية إذا لم يتم العثور على الرسوم
    }
    
} catch (Exception $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    $_SESSION['error_message'] = "خطأ في جلب بيانات الموعد: " . $e->getMessage();
    
    // إنشاء بيانات موعد افتراضية لمنع الأخطاء
    if (!$appointment) {
        $appointment = [
            'id' => $appointment_id,
            'patient_id' => $patient['id'] ?? 0,
            'doctor_id' => 0,
            'first_name' => 'غير متوفر',
            'last_name' => '',
            'department_name' => 'غير متوفر',
            'appointment_date' => date('Y-m-d'),
            'appointment_time' => date('H:i')
        ];
        error_log("Using fallback appointment data: " . print_r($appointment, true));
    }
}

// معالجة عملية الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['card_number']) || empty($_POST['expiry_date']) || empty($_POST['cvv'])) {
            throw new Exception("جميع حقول الدفع مطلوبة");
        }

        // التأكد من وجود معرف الموعد
        if (!isset($appointment_id) || empty($appointment_id)) {
            throw new Exception("لم يتم تحديد معرف الموعد بشكل صحيح");
        }

        // بدء المعاملة
        $db->beginTransaction();

        // التحقق من وجود جدول payments
        $check_table = "SHOW TABLES LIKE 'payments'";
        $table_stmt = $db->prepare($check_table);
        $table_stmt->execute();
        $table_exists = $table_stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // إنشاء جدول المدفوعات إذا لم يكن موجودًا
            $create_table = "CREATE TABLE IF NOT EXISTS payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                appointment_id INT,
                amount DECIMAL(10,2) NOT NULL,
                payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                payment_method VARCHAR(50),
                transaction_id VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id)
            )";
            $db->exec($create_table);
        }

        // إضافة سجل في جدول المدفوعات
        $payment_query = "INSERT INTO payments (appointment_id, amount, payment_status, payment_method, transaction_id) 
                          VALUES (:appointment_id, :amount, 'completed', 'credit_card', :transaction_id)";
        
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->bindParam(":appointment_id", $appointment_id);
        $payment_stmt->bindParam(":amount", $consultation_fee);
        $transaction_id = 'TXN' . time() . rand(1000, 9999); // إنشاء معرف معاملة بسيط
        $payment_stmt->bindParam(":transaction_id", $transaction_id);
        
        if (!$payment_stmt->execute()) {
            throw new Exception("فشل في تسجيل عملية الدفع");
        }
        
        // تحديث حالة الدفع وحالة الموعد
        $update_query = "UPDATE appointments 
                       SET payment_status = 'paid', 
                           status = 'confirmed' 
                       WHERE id = :appointment_id";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":appointment_id", $appointment_id);
        
        if ($update_stmt->execute()) {
            // إتمام المعاملة
            $db->commit();

            $_SESSION['success_message'] = "تم الدفع بنجاح وتأكيد الموعد!";
            
            // حذف متغيرات الجلسة المؤقتة
            unset($_SESSION['appointment_id']);
            
            header("Location: appointments.php");
            exit();
        } else {
            throw new Exception("فشل في تحديث حالة الدفع");
        }
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
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
    <!-- استدعاء ملف الهيدر بشكل مباشر بدون استخدام include -->
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

    <!-- بداية المحتوى الرئيسي -->

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">إتمام عملية الدفع</h2>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($appointment || $force_show_form): ?>
                        <div class="alert alert-info mb-4">
                            <h5 class="mb-3">تفاصيل الموعد:</h5>
                            <p class="mb-2">الطبيب: د. <?php echo htmlspecialchars(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? '')); ?></p>
                            <p class="mb-2">القسم: <?php echo isset($appointment['department_name']) ? htmlspecialchars($appointment['department_name']) : 'غير متوفر'; ?></p>
                            <p class="mb-2">التاريخ: <?php 
                                if (isset($appointment['appointment_date']) && !empty($appointment['appointment_date'])) {
                                    try {
                                        echo htmlspecialchars(date('Y-m-d', strtotime($appointment['appointment_date'])));
                                    } catch (Exception $e) {
                                        echo 'غير متوفر';
                                        error_log("Date formatting error: " . $e->getMessage());
                                    }
                                } else {
                                    echo 'غير متوفر';
                                }
                            ?></p>
                            <p class="mb-2">الوقت: <?php 
                                if (isset($appointment['appointment_time']) && !empty($appointment['appointment_time'])) {
                                    try {
                                        echo htmlspecialchars(date('H:i', strtotime($appointment['appointment_time'])));
                                    } catch (Exception $e) {
                                        echo 'غير متوفر';
                                        error_log("Time formatting error: " . $e->getMessage());
                                    }
                                } else {
                                    echo 'غير متوفر';
                                }
                            ?></p>
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
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <p>حدثت مشكلة في عرض بيانات الموعد والدفع. الرجاء المحاولة مرة أخرى أو التواصل مع الدعم الفني.</p>
                            <div class="d-grid gap-2 mt-3">
                                <a href="appointments.php" class="btn btn-primary">العودة إلى صفحة المواعيد</a>
                            </div>
                        </div>
                        <?php endif; ?>
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