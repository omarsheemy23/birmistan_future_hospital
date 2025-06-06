<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if request is POST and has JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['appointment_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Start transaction
    $db->beginTransaction();

    // Update video call status
    $query = "UPDATE video_calls SET 
              status = :status,
              end_time = NOW()
              WHERE appointment_id = :appointment_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":status", $data['status']);
    $stmt->bindParam(":appointment_id", $data['appointment_id']);
    $stmt->execute();

    // Update appointment status if video call is completed
    if ($data['status'] === 'completed') {
        $query = "UPDATE appointments SET 
                  status = 'completed'
                  WHERE id = :appointment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":appointment_id", $data['appointment_id']);
        $stmt->execute();
    }

    // Commit transaction
    $db->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update video call status']);
} 