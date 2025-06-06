<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Doctor ID is required';
    header('Location: doctors.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Get user_id from doctors table
        $query = "SELECT user_id FROM doctors WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            // Delete from doctors table
            $query = "DELETE FROM doctors WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_GET['id']]);

            // Delete from users table
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);

            $db->commit();
            $_SESSION['success'] = 'Doctor deleted successfully';
        } else {
            throw new Exception('Doctor not found');
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header('Location: doctors.php');
exit(); 