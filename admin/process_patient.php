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
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'date_of_birth', 'gender'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("All required fields must be filled.");
            }
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Start transaction
        $db->beginTransaction();

        if (isset($_POST['patient_id'])) {
            // Update existing patient
            $query = "UPDATE patients SET 
                      first_name = :first_name,
                      last_name = :last_name,
                      date_of_birth = :date_of_birth,
                      gender = :gender,
                      phone = :phone,
                      address = :address
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['patient_id']);
            $success_message = "Patient updated successfully.";
        } else {
            // Check if email already exists
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $_POST['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists.");
            }

            // Create user account
            $query = "INSERT INTO users (username, password, email, role) 
                      VALUES (:username, :password, :email, 'patient')";
            $stmt = $db->prepare($query);
            $username = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
            $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $_POST['email']);
            $stmt->execute();
            
            $user_id = $db->lastInsertId();

            // Create patient record
            $query = "INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone, address) 
                      VALUES (:user_id, :first_name, :last_name, :date_of_birth, :gender, :phone, :address)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $success_message = "Patient added successfully. Default password is 'password123'";
        }

        // Bind common parameters
        $stmt->bindParam(":first_name", $_POST['first_name']);
        $stmt->bindParam(":last_name", $_POST['last_name']);
        $stmt->bindParam(":date_of_birth", $_POST['date_of_birth']);
        $stmt->bindParam(":gender", $_POST['gender']);
        $stmt->bindParam(":phone", $_POST['phone']);
        $stmt->bindParam(":address", $_POST['address']);
        
        $stmt->execute();

        // Commit transaction
        $db->commit();

        $_SESSION['success_message'] = $success_message;
        header("Location: patients.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: patients.php");
        exit();
    }
}

// If not POST request, redirect to patients page
header("Location: patients.php");
exit(); 