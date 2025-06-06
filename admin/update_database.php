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

try {
    // Check if payment_card column already exists
    $stmt = $db->prepare("SHOW COLUMNS FROM doctors LIKE 'payment_card'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add payment_card column to doctors table
        $sql = "ALTER TABLE doctors ADD COLUMN payment_card VARCHAR(19) NULL AFTER consultation_fee";
        $db->exec($sql);
        $_SESSION['success'] = "تم تحديث قاعدة البيانات بنجاح. تمت إضافة عمود رقم البطاقة للدفع.";
    } else {
        $_SESSION['info'] = "عمود رقم البطاقة للدفع موجود بالفعل في قاعدة البيانات.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "خطأ في تحديث قاعدة البيانات: " . $e->getMessage();
}

// Redirect back to doctors page
header("Location: doctors.php");
exit();
?> 