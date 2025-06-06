<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// Check if appointment_id is provided
if (!isset($_POST['appointment_id'])) {
    $_SESSION['error'] = "لم يتم تحديد الموعد";
    header('Location: appointments.php');
    exit();
}

try {
    // Get patient ID first
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch();

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // Get appointment details and verify ownership
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.id = ? AND a.patient_id = ?
    ");
    $stmt->execute([$_POST['appointment_id'], $patient['id']]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        throw new Exception("لم يتم العثور على الموعد");
    }

    // Check if appointment is already cancelled
    if ($appointment['status'] === 'cancelled') {
        throw new Exception("هذا الموعد ملغي بالفعل");
    }

    // Update appointment status to cancelled
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'cancelled'
        WHERE id = ? AND patient_id = ?
    ");
    $stmt->execute([$_POST['appointment_id'], $patient['id']]);

    $_SESSION['success'] = "تم إلغاء الموعد بنجاح";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: appointments.php');
exit(); 