<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing doctor ID');
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT d.*, u.email, dept.name as department_name 
                          FROM doctors d 
                          JOIN users u ON d.user_id = u.id 
                          JOIN departments dept ON d.department_id = dept.id 
                          WHERE d.id = :id");
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($doctor);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Doctor not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error: ' . $e->getMessage();
} 