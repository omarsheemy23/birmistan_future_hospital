<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle add shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['shift_id'])) {
    try {
        $nurse_id = $_POST['nurse_id'];
        $shift_date = $_POST['shift_date'];
        $shift_type = $_POST['shift_type'];
        $department_id = $_POST['department_id'];
        $notes = $_POST['notes'] ?? null;
        
        $query = "INSERT INTO nurse_shifts (nurse_id, shift_date, shift_type, department_id, notes, status) 
                 VALUES (:nurse_id, :shift_date, :shift_type, :department_id, :notes, 'scheduled')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nurse_id", $nurse_id);
        $stmt->bindParam(":shift_date", $shift_date);
        $stmt->bindParam(":shift_type", $shift_type);
        $stmt->bindParam(":department_id", $department_id);
        $stmt->bindParam(":notes", $notes);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "تمت إضافة النوبة بنجاح";
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء إضافة النوبة";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "خطأ في إضافة النوبة: " . $e->getMessage();
    }
}

// Handle edit shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shift_id'])) {
    try {
        $shift_id = $_POST['shift_id'];
        $nurse_id = $_POST['nurse_id'];
        $shift_date = $_POST['shift_date'];
        $shift_type = $_POST['shift_type'];
        $department_id = $_POST['department_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? null;
        
        $query = "UPDATE nurse_shifts 
                 SET nurse_id = :nurse_id,
                     shift_date = :shift_date,
                     shift_type = :shift_type,
                     department_id = :department_id,
                     status = :status,
                     notes = :notes
                 WHERE id = :shift_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":shift_id", $shift_id);
        $stmt->bindParam(":nurse_id", $nurse_id);
        $stmt->bindParam(":shift_date", $shift_date);
        $stmt->bindParam(":shift_type", $shift_type);
        $stmt->bindParam(":department_id", $department_id);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":notes", $notes);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "تم تحديث النوبة بنجاح";
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء تحديث النوبة";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "خطأ في تحديث النوبة: " . $e->getMessage();
    }
}

// Handle delete shift
if (isset($_GET['delete_shift'])) {
    try {
        $shift_id = $_GET['delete_shift'];
        
        $query = "DELETE FROM nurse_shifts WHERE id = :shift_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":shift_id", $shift_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "تم حذف النوبة بنجاح";
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء حذف النوبة";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "خطأ في حذف النوبة: " . $e->getMessage();
    }
}

// Redirect back to nurses page
header("Location: nurses.php");
exit();
?> 