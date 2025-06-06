<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تأكد من وجود مجلد للسجلات
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', 'logs/db_fix_error.log');

// وظيفة تسجيل الأخطاء
function logMessage($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
    echo $message . "<br>";
}

// استيراد ملف قاعدة البيانات
require_once 'config/database.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

echo "<h1>إصلاح مشاكل قاعدة البيانات الصيدلية</h1>";

try {
    // 1. إضافة عمود dosage_form إلى جدول medicines
    logMessage("محاولة إضافة عمود dosage_form إلى جدول medicines...");
    
    try {
        // التحقق أولاً من وجود العمود
        $check = $conn->query("SHOW COLUMNS FROM medicines LIKE 'dosage_form'");
        if ($check->rowCount() == 0) {
            // إضافة العمود إذا لم يكن موجوداً
            $conn->exec("ALTER TABLE medicines ADD COLUMN dosage_form VARCHAR(50) DEFAULT 'أقراص' AFTER type");
            logMessage("✅ تم إضافة عمود dosage_form بنجاح!");
        } else {
            logMessage("✅ عمود dosage_form موجود بالفعل في جدول medicines");
        }
    } catch (PDOException $e) {
        logMessage("❌ خطأ في إضافة عمود dosage_form: " . $e->getMessage());
    }
    
    // 2. إضافة عمود price إلى جدول medicines
    logMessage("محاولة إضافة عمود price إلى جدول medicines...");
    
    try {
        // التحقق أولاً من وجود العمود
        $check = $conn->query("SHOW COLUMNS FROM medicines LIKE 'price'");
        if ($check->rowCount() == 0) {
            // إضافة العمود إذا لم يكن موجوداً
            $conn->exec("ALTER TABLE medicines ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00 AFTER dosage_form");
            logMessage("✅ تم إضافة عمود price بنجاح!");
        } else {
            logMessage("✅ عمود price موجود بالفعل في جدول medicines");
        }
    } catch (PDOException $e) {
        logMessage("❌ خطأ في إضافة عمود price: " . $e->getMessage());
    }
    
    // 3. التحقق من وجود جدول prescription_medicines وإنشائه إذا لم يكن موجوداً
    logMessage("التحقق من وجود جدول prescription_medicines...");
    
    try {
        $check = $conn->query("SHOW TABLES LIKE 'prescription_medicines'");
        if ($check->rowCount() == 0) {
            // جدول غير موجود، قم بإنشائه
            $sql = "CREATE TABLE prescription_medicines (
                id int(11) NOT NULL AUTO_INCREMENT,
                prescription_id int(11) NOT NULL,
                medicine_id int(11) NOT NULL,
                quantity int(11) NOT NULL DEFAULT 1,
                dosage_instructions text DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id),
                KEY prescription_id (prescription_id),
                KEY medicine_id (medicine_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $conn->exec($sql);
            logMessage("✅ تم إنشاء جدول prescription_medicines بنجاح!");
        } else {
            logMessage("✅ جدول prescription_medicines موجود بالفعل");
        }
    } catch (PDOException $e) {
        logMessage("❌ خطأ في إنشاء جدول prescription_medicines: " . $e->getMessage());
    }
    
    // 4. التحقق من وجود بيانات في جدول prescription_medicines
    logMessage("التحقق من وجود بيانات في جدول prescription_medicines...");
    
    try {
        $check = $conn->query("SELECT COUNT(*) as count FROM prescription_medicines");
        $result = $check->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
        
        if ($count > 0) {
            logMessage("✅ يوجد $count سجل في جدول prescription_medicines");
        } else {
            logMessage("⚠️ لا توجد بيانات في جدول prescription_medicines");
            
            // إضافة بيانات تجريبية إذا كان الجدول فارغاً ويوجد روشتات
            $check_prescriptions = $conn->query("SELECT COUNT(*) as count FROM prescriptions");
            $prescriptions_result = $check_prescriptions->fetch(PDO::FETCH_ASSOC);
            $prescriptions_count = $prescriptions_result['count'];
            
            if ($prescriptions_count > 0) {
                logMessage("محاولة إضافة بيانات تجريبية للأدوية في الروشتات...");
                
                // جلب جميع الروشتات التي ليس لها أدوية
                $stmt = $conn->prepare("
                    SELECT p.id FROM prescriptions p
                    LEFT JOIN prescription_medicines pm ON p.id = pm.prescription_id
                    WHERE pm.id IS NULL
                ");
                $stmt->execute();
                $empty_prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($empty_prescriptions) > 0) {
                    // جلب بعض الأدوية المتاحة
                    $medicines_stmt = $conn->query("SELECT id FROM medicines LIMIT 3");
                    $medicines = $medicines_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($medicines) > 0) {
                        foreach ($empty_prescriptions as $prescription) {
                            foreach ($medicines as $index => $medicine) {
                                $stmt = $conn->prepare("
                                    INSERT INTO prescription_medicines 
                                    (prescription_id, medicine_id, quantity, dosage_instructions) 
                                    VALUES (?, ?, ?, ?)
                                ");
                                $quantity = $index + 1;
                                $instructions = "مرة واحدة يوميًا لمدة " . ($index + 3) . " أيام";
                                $stmt->execute([$prescription['id'], $medicine['id'], $quantity, $instructions]);
                            }
                            logMessage("✅ تمت إضافة أدوية للروشتة رقم " . $prescription['id']);
                        }
                    } else {
                        logMessage("⚠️ لا توجد أدوية لإضافتها");
                    }
                } else {
                    logMessage("✅ جميع الروشتات لها أدوية مسجلة");
                }
            }
        }
    } catch (PDOException $e) {
        logMessage("❌ خطأ في التحقق من بيانات جدول prescription_medicines: " . $e->getMessage());
    }
    
    // 5. إصلاح جدول medicines وتحديث القيم
    logMessage("تحديث قيم افتراضية لجدول medicines...");
    
    try {
        $conn->exec("UPDATE medicines SET dosage_form = 'أقراص' WHERE dosage_form IS NULL");
        $conn->exec("UPDATE medicines SET price = 10.00 WHERE price IS NULL OR price = 0");
        logMessage("✅ تم تحديث القيم في جدول medicines");
    } catch (PDOException $e) {
        logMessage("❌ خطأ في تحديث قيم جدول medicines: " . $e->getMessage());
    }
    
    logMessage("تم الانتهاء من إصلاح قاعدة البيانات!");
    
} catch (Exception $e) {
    logMessage("❌ خطأ عام: " . $e->getMessage());
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        direction: rtl;
        text-align: right;
        margin: 20px;
        padding: 20px;
        line-height: 1.6;
        background-color: #f8f9fa;
    }
    h1 {
        color: #2c3e50;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }
    a {
        color: #3498db;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .btn {
        display: inline-block;
        padding: 8px 15px;
        margin: 10px 0;
        background-color: #3498db;
        color: white;
        border-radius: 4px;
        text-decoration: none;
    }
    .btn:hover {
        background-color: #2980b9;
    }
</style>

<p>
    <a href="pharmacist/dispense_medicine.php?prescription_id=9" class="btn">العودة إلى صفحة صرف الأدوية</a>
    <a href="index.php" class="btn">العودة إلى الصفحة الرئيسية</a>
</p> 