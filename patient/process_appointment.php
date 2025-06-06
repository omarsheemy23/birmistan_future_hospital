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

$database = new Database();
$db = $database->getConnection();

try {
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['doctor_id']) || !isset($_POST['appointment_date']) || !isset($_POST['appointment_time'])) {
        throw new Exception("جميع الحقول مطلوبة");
    }

    // الحصول على معرف المريض من جدول المرضى
    $patient_query = "SELECT id FROM patients WHERE user_id = :user_id";
    $patient_stmt = $db->prepare($patient_query);
    $patient_stmt->bindParam(":user_id", $_SESSION['user_id']);
    $patient_stmt->execute();
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    $patient_id = $patient['id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = 'pending';

    // التحقق من توفر الموعد (مع استثناء مواعيد المريض نفسه)
    $check_query = "SELECT COUNT(*) FROM appointments 
                    WHERE doctor_id = :doctor_id 
                    AND appointment_date = :appointment_date 
                    AND appointment_time = :appointment_time 
                    AND status != 'cancelled'
                    AND patient_id != :patient_id";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":doctor_id", $doctor_id);
    $check_stmt->bindParam(":appointment_date", $appointment_date);
    $check_stmt->bindParam(":appointment_time", $appointment_time);
    $check_stmt->bindParam(":patient_id", $patient_id);
    $check_stmt->execute();
    
    if ($check_stmt->fetchColumn() > 0) {
        throw new Exception("الموعد محجوز مسبقاً");
    }

    // بدء المعاملة
    $db->beginTransaction();

    // إضافة الموعد
    $appointment_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) 
                         VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :status)";
    
    $appointment_stmt = $db->prepare($appointment_query);
    $appointment_stmt->bindParam(":patient_id", $patient_id);
    $appointment_stmt->bindParam(":doctor_id", $doctor_id);
    $appointment_stmt->bindParam(":appointment_date", $appointment_date);
    $appointment_stmt->bindParam(":appointment_time", $appointment_time);
    $appointment_stmt->bindParam(":status", $status);
    
    if (!$appointment_stmt->execute()) {
        throw new Exception("فشل في حجز الموعد");
    }

    $appointment_id = $db->lastInsertId();

    // جلب رسوم الاستشارة من جدول الأطباء
    $fee_query = "SELECT consultation_fee FROM doctors WHERE id = :doctor_id";
    $fee_stmt = $db->prepare($fee_query);
    $fee_stmt->bindParam(":doctor_id", $doctor_id);
    $fee_stmt->execute();
    $consultation_fee = $fee_stmt->fetchColumn();

    if (!$consultation_fee) {
        throw new Exception("لم يتم العثور على رسوم الاستشارة");
    }

    // إضافة سجل الدفع
    $payment_query = "INSERT INTO payments (appointment_id, amount, payment_status) 
                      VALUES (:appointment_id, :amount, 'pending')";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->bindParam(":appointment_id", $appointment_id);
    $payment_stmt->bindParam(":amount", $consultation_fee);
    
    if (!$payment_stmt->execute()) {
        throw new Exception("فشل في إنشاء سجل الدفع");
    }

    // تحديث حالة الموعد في جدول appointments
    $update_appointment_query = "UPDATE appointments SET payment_status = 'pending' WHERE id = :appointment_id";
    $update_appointment_stmt = $db->prepare($update_appointment_query);
    $update_appointment_stmt->bindParam(":appointment_id", $appointment_id);
    $update_appointment_stmt->execute();
    
    // إتمام المعاملة
    $db->commit();

    // تخزين معرف الموعد ورسوم الاستشارة في الجلسة
    $_SESSION['appointment_id'] = $appointment_id;
    $_SESSION['consultation_fee'] = $consultation_fee;

    $_SESSION['success_message'] = "تم حجز الموعد بنجاح!";
    header("Location: payment.php?appointment_id=" . $appointment_id);
    exit();

} catch (Exception $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    // عرض الخطأ مباشرة على الشاشة بدلاً من إعادة التوجيه
    echo '<div style="direction: rtl; text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">';
    echo '<h2 style="color: #e74c3c;">حدث خطأ</h2>';
    echo '<p style="font-size: 18px;">' . $e->getMessage() . '</p>';
    echo '<p><a href="book_appointment.php" style="background-color: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">العودة إلى صفحة حجز الموعد</a></p>';
    echo '</div>';
    exit();
}
?>