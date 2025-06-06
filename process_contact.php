<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Here you would typically:
    // 1. Validate the input
    // 2. Send an email
    // 3. Store in database
    // 4. Redirect with success message
    
    $_SESSION['success_message'] = "Thank you for your message. We will get back to you soon!";
    header("Location: contact.php");
    exit();
} else {
    header("Location: contact.php");
    exit();
}
?> 