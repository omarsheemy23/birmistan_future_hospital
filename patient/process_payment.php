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

if (!$appointment_id) {
    $_SESSION['error_message'] = "لم يتم تحديد معرف الموعد";
    header("Location: appointments.php");
    exit();
}

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

try {
    // بدء المعاملة
    $db->beginTransaction();
    
    // قم بتسجيل عملية الدفع للتصحيح
    error_log("Processing payment for appointment ID: " . $appointment_id);
    
    // التحقق من حالة الموعد قبل التحديث
    $check_query = "SELECT status, payment_status FROM appointments WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$appointment_id]);
    $appointment_before = $check_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Appointment before update: Status=" . ($appointment_before['status'] ?? 'unknown') . ", Payment=" . ($appointment_before['payment_status'] ?? 'unknown'));
    
    // تحديث حالة الدفع في جدول المواعيد
    $update_query = "UPDATE appointments 
                   SET payment_status = 'paid', status = 'confirmed' 
                   WHERE id = ?";
                   
    $update_stmt = $db->prepare($update_query);
    if (!$update_stmt->execute([$appointment_id])) {
        throw new Exception("فشل في تحديث حالة الدفع");
    }
    
    // التحقق من أن التحديث تم بنجاح
    $rows_affected = $update_stmt->rowCount();
    error_log("Update affected " . $rows_affected . " rows");
    
    // التحقق من حالة الموعد بعد التحديث
    $check_query = "SELECT status, payment_status FROM appointments WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$appointment_id]);
    $appointment_after = $check_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Appointment after update: Status=" . ($appointment_after['status'] ?? 'unknown') . ", Payment=" . ($appointment_after['payment_status'] ?? 'unknown'));
    
    // محاولة إضافة سجل في جدول المدفوعات (إذا كان موجوداً)
    try {
        $payment_query = "INSERT INTO payments (appointment_id, amount, payment_status, payment_method, transaction_id) 
                        VALUES (?, ?, 'completed', 'direct_payment', ?)";
        
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->execute([$appointment_id, 300, $transaction_id]);
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
    $_SESSION['error_message'] = "حدث خطأ: " . $e->getMessage();
    header("Location: appointments.php");
    exit();
}
?> 