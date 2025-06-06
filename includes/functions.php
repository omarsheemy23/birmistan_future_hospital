<?php
/**
 * دالة لتنظيف وتأمين البيانات المدخلة
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * دالة للتحقق من صحة البريد الإلكتروني
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * دالة للتحقق من صحة رقم الهاتف
 */
function is_valid_phone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

/**
 * دالة لتحويل التاريخ من تنسيق MySQL إلى تنسيق عربي
 */
function format_date($date) {
    if (empty($date)) return '';
    $date = new DateTime($date);
    return $date->format('Y/m/d H:i');
}

/**
 * دالة لتحويل حالة الطلب إلى نص عربي
 */
function get_request_status($status) {
    $statuses = [
        'pending' => 'قيد الانتظار',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'completed' => 'مكتمل'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * دالة لتحويل حالة سيارة الإسعاف إلى نص عربي
 */
function get_ambulance_status($status) {
    $statuses = [
        'available' => 'متاحة',
        'busy' => 'مشغولة',
        'maintenance' => 'صيانة'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * دالة لتحويل مستوى الطوارئ إلى نص عربي
 */
function get_emergency_level($level) {
    $levels = [
        'low' => 'منخفض',
        'medium' => 'متوسط',
        'high' => 'عالي'
    ];
    return $levels[$level] ?? $level;
}

/**
 * دالة للتحقق من صلاحيات المستخدم
 */
function check_permission($required_role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $required_role) {
        header('Location: ../login.php');
        exit();
    }
}

/**
 * دالة لعرض رسالة خطأ
 */
function show_error($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

/**
 * دالة لعرض رسالة نجاح
 */
function show_success($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
} 