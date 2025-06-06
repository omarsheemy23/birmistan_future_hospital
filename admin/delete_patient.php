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
    $_SESSION['error'] = 'Patient ID is required';
    header('Location: patients.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        $patient_id = $_GET['id'];

        // Get information about the patient before deleting
        $query = "SELECT user_id FROM patients WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$patient_id]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            throw new Exception('لم يتم العثور على المريض');
        }

        // First, get all appointment IDs for the patient
        $query = "SELECT id FROM appointments WHERE patient_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$patient_id]);
        $appointment_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($appointment_ids)) {
            $placeholders = str_repeat('?,', count($appointment_ids) - 1) . '?';
            
            // First, let's check and delete any medication_dispensing records 
            // that are linked to prescriptions from these appointments
            $query = "SELECT p.id FROM prescriptions p WHERE p.appointment_id IN ($placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($appointment_ids);
            $prescription_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($prescription_ids)) {
                $prescription_placeholders = str_repeat('?,', count($prescription_ids) - 1) . '?';
                
                // Delete medication dispensing records for these prescriptions
                $query = "DELETE FROM medication_dispensing WHERE prescription_id IN ($prescription_placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($prescription_ids);
            }
            
            // Now delete all other related records
            
            // Delete medical_reports for these appointments
            $query = "DELETE FROM medical_reports WHERE appointment_id IN ($placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($appointment_ids);
            
            // Delete prescriptions (now that medication_dispensing records are deleted)
            $query = "DELETE FROM prescriptions WHERE appointment_id IN ($placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($appointment_ids);
            
            // Delete any other tables that might reference appointments
            
            // Now delete the appointments themselves
            $query = "DELETE FROM appointments WHERE patient_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$patient_id]);
        }

        // Delete patient's medical history
        $query = "DELETE FROM medical_history WHERE patient_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$patient_id]);
        
        // Delete any other records that reference the patient directly
        
        // Now we can safely delete the patient
        $query = "DELETE FROM patients WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$patient_id]);

        // Finally, delete the user account
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);

        $db->commit();
        $_SESSION['success'] = 'تم حذف المريض وجميع السجلات المرتبطة به بنجاح';
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "خطأ في حذف المريض: " . $e->getMessage();
}

header('Location: patients.php');
exit(); 