<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );

    // Test the connection
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    // Log the error
    error_log("Database Error: " . $e->getMessage());
    
    // Show a user-friendly message
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        die("خطأ: قاعدة البيانات غير موجودة. يرجى التأكد من تثبيت قاعدة البيانات بشكل صحيح.");
    } else if (strpos($e->getMessage(), "Access denied") !== false) {
        die("خطأ: تم رفض الوصول إلى قاعدة البيانات. يرجى التحقق من اسم المستخدم وكلمة المرور.");
    } else if (strpos($e->getMessage(), "Connection refused") !== false) {
        die("خطأ: تعذر الاتصال بخادم قاعدة البيانات. يرجى التأكد من تشغيل خدمة MySQL.");
    } else {
        die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقاً أو الاتصال بمسؤول النظام.");
    }
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constants
define('SITE_URL', 'http://localhost/birmistan_future_hospital');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// Helper functions
function redirect($path) {
    header("Location: " . SITE_URL . "/" . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isDoctor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'doctor';
}

function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

// Database helper functions
function getDatabaseError() {
    global $pdo;
    if ($pdo) {
        $errorInfo = $pdo->errorInfo();
        return $errorInfo[2];
    }
    return "خطأ غير معروف في قاعدة البيانات";
}

// Set default language
$_SESSION['lang'] = 'ar'; 