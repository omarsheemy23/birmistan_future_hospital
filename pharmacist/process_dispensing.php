<?php
// تفعيل عرض الأخطاء بشكل كامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_dispense_error.log');

// وظيفة تسجيل الأخطاء
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

// نسجل دخول الصفحة للتشخيص
logError("Accessing process_dispensing.php - " . (isset($_POST['prescription_id']) ? "Prescription ID: " . $_POST['prescription_id'] : "No prescription ID"));

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من أن الطلب تم إرساله عبر POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['dispense'])) {
    logError("Invalid request method or no dispense action");
    header('Location: dispense_medicine.php');
    exit();
}

try {
    // التحقق من تسجيل دخول الصيدلي
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
        logError("User not logged in or not pharmacist");
        header("Location: ../login.php");
        exit();
    }

    // التحقق من وجود معرف الروشتة
    if (!isset($_POST['prescription_id']) || !is_numeric($_POST['prescription_id'])) {
        throw new Exception("معرف الروشتة غير صالح.");
    }

    $prescription_id = $_POST['prescription_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // إنشاء اتصال بقاعدة البيانات
    $database = new Database();
    $conn = $database->getConnection();

    // تحقق من وجود الروشتة
    $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = :prescription_id");
    $stmt->bindParam(':prescription_id', $prescription_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("الروشتة غير موجودة.");
    }
    
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // التحقق من أن الروشتة لم يتم صرفها بالفعل
    if ($prescription['status'] !== 'pending') {
        throw new Exception("لا يمكن صرف الروشتة - الحالة الحالية: " . $prescription['status']);
    }
    
    // تحقق إذا كانت هناك جداول مفقودة
    $check_table = $conn->query("SHOW TABLES LIKE 'prescription_medicines'");
    if ($check_table->rowCount() == 0) {
        logError("Table 'prescription_medicines' not found");
        throw new Exception("جدول 'prescription_medicines' غير موجود في قاعدة البيانات. يرجى استخدام 'إصلاح قاعدة البيانات'.");
    }
    
    // جلب الأدوية المرتبطة بالروشتة
    $stmt = $conn->prepare("
        SELECT pm.id, pm.medicine_id, pm.quantity,
            m.name, m.quantity_in_stock
        FROM prescription_medicines pm
        JOIN medicines m ON pm.medicine_id = m.id
        WHERE pm.prescription_id = :prescription_id
    ");
    $stmt->bindParam(':prescription_id', $prescription_id);
    $stmt->execute();
    $prescription_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($prescription_medicines)) {
        throw new Exception("لا توجد أدوية مرتبطة بهذه الروشتة.");
    }
    
    // التحقق من توفر جميع الأدوية في المخزون
    $unavailable_medicines = [];
    
    foreach ($prescription_medicines as $medicine) {
        if ($medicine['quantity_in_stock'] < $medicine['quantity']) {
            $unavailable_medicines[] = $medicine['name'] . " (المطلوب: " . $medicine['quantity'] . " - المتوفر: " . $medicine['quantity_in_stock'] . ")";
        }
    }
    
    if (!empty($unavailable_medicines)) {
        throw new Exception("لا يمكن صرف الروشتة - بعض الأدوية غير متوفرة بالكمية المطلوبة: " . implode(", ", $unavailable_medicines));
    }
    
    // جلب معرف الصيدلي
    $stmt = $conn->prepare("SELECT id FROM pharmacists WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("لم يتم العثور على بيانات الصيدلي.");
    }
    
    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);
    $pharmacist_id = $pharmacist['id'];
    
    // بدء المعاملة
    $conn->beginTransaction();
    
    try {
        // تحديث حالة الروشتة
        $stmt = $conn->prepare("UPDATE prescriptions SET status = 'dispensed', updated_at = NOW() WHERE id = :prescription_id");
        $stmt->bindParam(':prescription_id', $prescription_id);
        $stmt->execute();
        
        // إنشاء سجل صرف
        $stmt = $conn->prepare("
            INSERT INTO dispensed_medicines (prescription_id, pharmacist_id, status, notes, created_at, updated_at)
            VALUES (:prescription_id, :pharmacist_id, 'complete', :notes, NOW(), NOW())
        ");
        $stmt->bindParam(':prescription_id', $prescription_id);
        $stmt->bindParam(':pharmacist_id', $pharmacist_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
        
        // تحديث المخزون
        foreach ($prescription_medicines as $medicine) {
            $stmt = $conn->prepare("
                UPDATE medicines 
                SET quantity_in_stock = quantity_in_stock - :quantity,
                    updated_at = NOW()
                WHERE id = :medicine_id
            ");
            $stmt->bindParam(':quantity', $medicine['quantity']);
            $stmt->bindParam(':medicine_id', $medicine['medicine_id']);
            $stmt->execute();
        }
        
        // تنفيذ المعاملة
        $conn->commit();
        
        // تسجيل النجاح
        logError("Successfully dispensed prescription ID: " . $prescription_id);
        
        // إعادة التوجيه إلى صفحة النجاح
        $_SESSION['success_message'] = "تم صرف الأدوية بنجاح للروشتة رقم " . $prescription_id;
        header("Location: dispense_medicine.php");
        exit();
        
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $conn->rollBack();
        throw new Exception("حدث خطأ أثناء صرف الأدوية: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // تسجيل الخطأ
    logError("Error in process_dispensing.php: " . $e->getMessage());
    
    // حفظ رسالة الخطأ في الجلسة
    $_SESSION['error_message'] = $e->getMessage();
    
    // إعادة التوجيه إلى صفحة صرف الأدوية
    if (isset($prescription_id)) {
        header("Location: dispense_medicine.php?prescription_id=" . $prescription_id);
    } else {
        header("Location: dispense_medicine.php");
    }
    exit();
}
?> 