<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if nurse ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nurse ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get nurse details with user email
    $query = "SELECT n.*, u.email 
              FROM nurses n 
              LEFT JOIN users u ON n.user_id = u.id 
              WHERE n.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $nurse = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($nurse);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Nurse not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 