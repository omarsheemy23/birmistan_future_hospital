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
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $status = $_POST['status'] ?? 'active';

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($specialization)) {
        $_SESSION['error'] = "Required fields are missing.";
        header("Location: doctors.php");
        exit();
    }

    try {
        // Check if this is an update or new doctor
        if (isset($_POST['doctor_id'])) {
            // Update existing doctor
            $query = "UPDATE doctors SET 
                      first_name = :first_name,
                      last_name = :last_name,
                      email = :email,
                      phone = :phone,
                      specialization = :specialization,
                      status = :status";
            
            // Add department_id to the update if it exists
            if (!empty($department_id)) {
                $query .= ", department_id = :department_id";
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['doctor_id']);
        } else {
            // Insert new doctor
            $query = "INSERT INTO doctors (first_name, last_name, email, phone, specialization, status";
            
            // Add department_id to the insert if it exists
            if (!empty($department_id)) {
                $query .= ", department_id";
            }
            
            $query .= ") VALUES (:first_name, :last_name, :email, :phone, :specialization, :status";
            
            // Add department_id to the values if it exists
            if (!empty($department_id)) {
                $query .= ", :department_id";
            }
            
            $query .= ")";
            
            $stmt = $db->prepare($query);
        }

        // Bind parameters
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":specialization", $specialization);
        $stmt->bindParam(":status", $status);
        
        // Bind department_id only if it exists
        if (!empty($department_id)) {
            $stmt->bindParam(":department_id", $department_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = isset($_POST['doctor_id']) ? "Doctor updated successfully!" : "Doctor added successfully!";
        } else {
            $_SESSION['error'] = "Error saving doctor information.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: doctors.php");
    exit();
} 