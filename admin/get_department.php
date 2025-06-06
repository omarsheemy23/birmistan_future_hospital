<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Check if department ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Department ID is required');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get department details
    $query = "SELECT * FROM departments WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        http_response_code(404);
        exit('Department not found');
    }
    
    // Return department data as JSON
    header('Content-Type: application/json');
    echo json_encode($department);
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Error fetching department details');
} 