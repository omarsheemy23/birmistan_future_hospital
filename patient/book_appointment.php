<?php
session_start();
require_once '../config/database.php';
require_once 'header.php';

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // جلب الأقسام
    $departments_query = "SELECT * FROM departments";
    $departments_stmt = $db->prepare($departments_query);
    $departments_stmt->execute();
    $departments = $departments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الأطباء إذا تم اختيار قسم
    $doctors = [];
    if (isset($_GET['department_id'])) {
        $doctors_query = "SELECT d.* 
                         FROM doctors d 
                         WHERE d.department_id = :department_id 
                         AND d.status = 'active'";
        $doctors_stmt = $db->prepare($doctors_query);
        $doctors_stmt->bindParam(":department_id", $_GET['department_id']);
        $doctors_stmt->execute();
        $doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // معالجة حجز الموعد
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['doctor_id']) || !isset($_POST['appointment_date']) || !isset($_POST['appointment_time'])) {
            throw new Exception("جميع الحقول مطلوبة");
        }

        // الحصول على معرف المريض
        $patient_query = "SELECT id FROM patients WHERE user_id = :user_id";
        $patient_stmt = $db->prepare($patient_query);
        $patient_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $patient_stmt->execute();
        $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            // إذا لم يتم العثور على سجل المريض، قم بإنشاء واحد
            $create_patient_query = "INSERT INTO patients (user_id, first_name, last_name) 
                                    VALUES (:user_id, :first_name, :last_name)";
            $create_patient_stmt = $db->prepare($create_patient_query);
            $create_patient_stmt->bindParam(":user_id", $_SESSION['user_id']);
            $create_patient_stmt->bindParam(":first_name", $_SESSION['first_name'] ?? 'مريض');
            $create_patient_stmt->bindParam(":last_name", $_SESSION['last_name'] ?? '');
            
            if ($create_patient_stmt->execute()) {
                $patient_id = $db->lastInsertId();
            } else {
                throw new Exception("فشل في إنشاء سجل المريض");
            }
        } else {
            $patient_id = $patient['id'];
        }

        $doctor_id = $_POST['doctor_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;

        // التحقق من توفر الموعد
        $check_query = "SELECT COUNT(*) FROM appointments 
                       WHERE doctor_id = :doctor_id 
                       AND appointment_date = :appointment_date 
                       AND appointment_time = :appointment_time 
                       AND status != 'cancelled'";
        
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":doctor_id", $doctor_id);
        $check_stmt->bindParam(":appointment_date", $appointment_date);
        $check_stmt->bindParam(":appointment_time", $appointment_time);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("الموعد محجوز مسبقاً");
        }

        // إضافة الموعد
        $appointment_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, payment_status) 
                            VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, 'pending', 'pending')";
        
        $appointment_stmt = $db->prepare($appointment_query);
        $appointment_stmt->bindParam(":patient_id", $patient_id);
        $appointment_stmt->bindParam(":doctor_id", $doctor_id);
        $appointment_stmt->bindParam(":appointment_date", $appointment_date);
        $appointment_stmt->bindParam(":appointment_time", $appointment_time);
        $appointment_stmt->bindParam(":reason", $reason);
        
        if ($appointment_stmt->execute()) {
            $appointment_id = $db->lastInsertId();
            
            // جلب رسوم الكشف
            $fee_query = "SELECT COALESCE(consultation_fee, 300) as consultation_fee FROM doctors WHERE id = :doctor_id";
            $fee_stmt = $db->prepare($fee_query);
            $fee_stmt->bindParam(":doctor_id", $doctor_id);
            $fee_stmt->execute();
            $consultation_fee = $fee_stmt->fetchColumn();

            $_SESSION['appointment_id'] = $appointment_id;
            $_SESSION['consultation_fee'] = $consultation_fee;
            
            // تعيين رسالة نجاح
            $_SESSION['success_message'] = "تم حجز الموعد وبانتظار عملية الدفع";
            
            // توجيه مباشر إلى صفحة الدفع
            header("Location: payment_new.php?appointment_id=" . $appointment_id);
            exit();
        } else {
            throw new Exception("فشل في حجز الموعد");
        }
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز موعد جديد - مستشفى بيرمستان المستقبلية</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">حجز موعد جديد</h2>
                        
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

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="department_id" class="form-label fw-bold">القسم</label>
                                <select class="form-select form-select-lg" id="department_id" name="department_id" required>
                                    <option value="">اختر القسم</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>" 
                                                <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $department['id']) ? 'selected' : ''; ?>>
                                            <?php echo $department['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="doctor_id" class="form-label fw-bold">الطبيب</label>
                                <select class="form-select form-select-lg" id="doctor_id" name="doctor_id" required>
                                    <option value="">اختر الطبيب</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            د. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="appointment_date" class="form-label fw-bold">تاريخ الموعد</label>
                                <input type="date" class="form-control form-control-lg" id="appointment_date" 
                                       name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="appointment_time" class="form-label fw-bold">وقت الموعد</label>
                                <input type="time" class="form-control form-control-lg" id="appointment_time" 
                                       name="appointment_time" required>
                            </div>

                            <div class="mb-4">
                                <label for="reason" class="form-label fw-bold">سبب الزيارة</label>
                                <textarea class="form-control form-control-lg" id="reason" name="reason" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">حجز الموعد</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('department_id').addEventListener('change', function() {
        window.location.href = 'book_appointment.php?department_id=' + this.value;
    });
    </script>
</body>
</html>