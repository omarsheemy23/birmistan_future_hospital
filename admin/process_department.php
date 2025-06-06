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
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            throw new Exception("Department name is required.");
        }

        // Start transaction
        $db->beginTransaction();

        if (isset($_POST['department_id'])) {
            // Update existing department
            $query = "UPDATE departments SET 
                      name = :name,
                      description = :description
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['department_id']);
            $success_message = "Department updated successfully.";
        } else {
            // Check if department name already exists
            $query = "SELECT id FROM departments WHERE name = :name";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":name", $_POST['name']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Department name already exists.");
            }

            // Create new department
            $query = "INSERT INTO departments (name, description) 
                      VALUES (:name, :description)";
            $stmt = $db->prepare($query);
            $success_message = "Department added successfully.";
        }

        // Bind parameters
        $stmt->bindParam(":name", $_POST['name']);
        $stmt->bindParam(":description", $_POST['description']);
        
        $stmt->execute();

        // Commit transaction
        $db->commit();

        $_SESSION['success_message'] = $success_message;
        header("Location: departments.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: departments.php");
        exit();
    }
}

// If not POST request, redirect to departments page
header("Location: departments.php");
exit(); 