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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'status'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        // Check if appointment already exists for the doctor at the same time (excluding current appointment if updating)
        $query = "SELECT id FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND appointment_date = :appointment_date 
                  AND appointment_time = :appointment_time 
                  AND status != 'cancelled'";
        
        if (isset($_POST['appointment_id'])) {
            $query .= " AND id != :appointment_id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":doctor_id", $_POST['doctor_id']);
        $stmt->bindParam(":appointment_date", $_POST['appointment_date']);
        $stmt->bindParam(":appointment_time", $_POST['appointment_time']);
        
        if (isset($_POST['appointment_id'])) {
            $stmt->bindParam(":appointment_id", $_POST['appointment_id']);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("An appointment already exists for this doctor at the specified time.");
        }

        if (isset($_POST['appointment_id'])) {
            // Update existing appointment
            $query = "UPDATE appointments 
                      SET patient_id = :patient_id,
                          doctor_id = :doctor_id,
                          appointment_date = :appointment_date,
                          appointment_time = :appointment_time,
                          status = :status
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['appointment_id']);
            $success_message = "Appointment updated successfully.";
        } else {
            // Insert new appointment
            $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) 
                      VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :status)";
            $stmt = $db->prepare($query);
            $success_message = "Appointment added successfully.";
        }

        $stmt->bindParam(":patient_id", $_POST['patient_id']);
        $stmt->bindParam(":doctor_id", $_POST['doctor_id']);
        $stmt->bindParam(":appointment_date", $_POST['appointment_date']);
        $stmt->bindParam(":appointment_time", $_POST['appointment_time']);
        $stmt->bindParam(":status", $_POST['status']);
        $stmt->execute();

        $_SESSION['success_message'] = $success_message;
        header("Location: appointments.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        if (isset($_POST['appointment_id'])) {
            header("Location: edit_appointment.php?id=" . $_POST['appointment_id']);
        } else {
            header("Location: appointments.php");
        }
        exit();
    }
}

// If not POST request, redirect to appointments page
header("Location: appointments.php");
exit(); 