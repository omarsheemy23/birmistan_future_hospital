<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Check if email already exists
        $email = $_POST['email'];
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("البريد الإلكتروني '{$email}' مستخدم بالفعل. الرجاء استخدام بريد إلكتروني آخر.");
        }

        // Handle profile picture upload
        $profile_picture = 'default-profile.png';
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/doctors/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $profile_picture = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $profile_picture;
            
            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                throw new Exception('فشل في رفع صورة الملف الشخصي');
            }
        }

        // Create user account
        $stmt = $db->prepare("
            INSERT INTO users (username, password, email, role) 
            VALUES (?, ?, ?, 'doctor')
        ");
        
        $username = strtolower(str_replace(' ', '.', $_POST['first_name'] . '.' . $_POST['last_name']));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([$username, $password, $_POST['email']]);
        $user_id = $db->lastInsertId();

        // Create doctor record
        try {
            // First check if payment_card column exists
            $stmt = $db->prepare("SHOW COLUMNS FROM doctors LIKE 'payment_card'");
            $stmt->execute();
            $paymentCardExists = $stmt->rowCount() > 0;
            
            if ($paymentCardExists) {
                // Create doctor record with payment_card
                $stmt = $db->prepare("
                    INSERT INTO doctors (
                        user_id, first_name, last_name, specialization, department_id,
                        qualification, experience, consultation_fee, profile_picture, payment_card
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user_id,
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['specialization'],
                    $_POST['department_id'],
                    $_POST['qualification'],
                    $_POST['experience'],
                    $_POST['consultation_fee'],
                    $profile_picture,
                    $_POST['payment_card']
                ]);
            } else {
                // Create doctor record without payment_card
                $stmt = $db->prepare("
                    INSERT INTO doctors (
                        user_id, first_name, last_name, specialization, department_id,
                        qualification, experience, consultation_fee, profile_picture
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user_id,
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['specialization'],
                    $_POST['department_id'],
                    $_POST['qualification'],
                    $_POST['experience'],
                    $_POST['consultation_fee'],
                    $profile_picture
                ]);
            }
        } catch (Exception $e) {
            // Handle exception
            throw new Exception("خطأ في إنشاء سجل الطبيب: " . $e->getMessage());
        }

        $db->commit();
        $_SESSION['success'] = "تم إضافة الطبيب بنجاح";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "خطأ في إضافة الطبيب: " . $e->getMessage();
    }
    
    header("Location: doctors.php");
    exit();
}
?> 