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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Validate required fields
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['department'])) {
            throw new Exception("Required fields are missing.");
        }
        
        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $_POST['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists.");
        }
        
        if (isset($_POST['nurse_id'])) {
            // Update existing nurse
            $query = "UPDATE nurses SET 
                      first_name = :first_name,
                      last_name = :last_name,
                      department = :department,
                      assigned_doctor_id = :assigned_doctor_id
                      WHERE id = :nurse_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nurse_id", $_POST['nurse_id']);
            $success_message = "Nurse updated successfully.";
        } else {
            // Create user account
            $query = "INSERT INTO users (username, password, email, role) 
                      VALUES (:username, :password, :email, 'nurse')";
            $stmt = $db->prepare($query);
            $username = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
            $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $_POST['email']);
            $stmt->execute();
            
            $user_id = $db->lastInsertId();
            
            // Create nurse record
            $query = "INSERT INTO nurses (user_id, first_name, last_name, department, assigned_doctor_id) 
                      VALUES (:user_id, :first_name, :last_name, :department, :assigned_doctor_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $success_message = "Nurse added successfully. Default password is 'password123'";
        }
        
        // Bind common parameters
        $stmt->bindParam(":first_name", $_POST['first_name']);
        $stmt->bindParam(":last_name", $_POST['last_name']);
        $stmt->bindParam(":department", $_POST['department']);
        
        // Handle assigned_doctor_id
        $assigned_doctor_id = !empty($_POST['assigned_doctor_id']) ? $_POST['assigned_doctor_id'] : null;
        $stmt->bindParam(":assigned_doctor_id", $assigned_doctor_id);
        
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success_message'] = $success_message;
        header("Location: nurses.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: nurses.php");
        exit();
    }
}

// If not POST request, redirect to nurses page
header("Location: nurses.php");
exit(); 