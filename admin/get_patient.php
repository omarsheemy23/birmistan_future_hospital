<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Patient ID is required');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get patient details
    $query = "SELECT p.*, u.email 
              FROM patients p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        http_response_code(404);
        exit('Patient not found');
    }
    
    // Return patient data as JSON
    header('Content-Type: application/json');
    echo json_encode($patient);
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Error fetching patient details');
} 