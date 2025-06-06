<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', 'logs/pharmacy_setup_error.log');

// استيراد ملف قاعدة البيانات
require_once 'config/database.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

echo "<h1>إعداد جداول نظام الصيدلية</h1>";
echo "<p>جاري إنشاء الجداول المفقودة...</p>";

try {
    // إنشاء جدول prescription_items إذا لم يكن موجوداً
    $prescription_items_sql = "
    CREATE TABLE IF NOT EXISTS `prescription_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `prescription_id` int(11) NOT NULL,
      `medicine_id` int(11) NOT NULL,
      `quantity` int(11) NOT NULL,
      `dosage` varchar(100) DEFAULT NULL,
      `frequency` varchar(100) DEFAULT NULL,
      `duration` varchar(100) DEFAULT NULL,
      `instructions` text DEFAULT NULL,
      `dispensed` tinyint(1) DEFAULT 0,
      `dispensed_quantity` int(11) DEFAULT 0,
      `dispensed_date` timestamp NULL DEFAULT NULL,
      `dispensed_by` int(11) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `prescription_id` (`prescription_id`),
      KEY `medicine_id` (`medicine_id`),
      KEY `dispensed_by` (`dispensed_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->exec($prescription_items_sql);
    echo "<div style='color: green;'>✓ تم إنشاء جدول prescription_items بنجاح</div>";
    
    // إنشاء جدول prescription_medicines إذا لم يكن موجوداً
    $prescription_medicines_sql = "
    CREATE TABLE IF NOT EXISTS `prescription_medicines` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `prescription_id` int(11) NOT NULL,
      `medicine_id` int(11) NOT NULL,
      `quantity` int(11) NOT NULL,
      `dosage_instructions` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `prescription_id` (`prescription_id`),
      KEY `medicine_id` (`medicine_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->exec($prescription_medicines_sql);
    echo "<div style='color: green;'>✓ تم إنشاء جدول prescription_medicines بنجاح</div>";
    
    // إضافة عمود dosage_form لجدول medicines إذا لم يكن موجودًا
    try {
        $check_column = $conn->query("SHOW COLUMNS FROM medicines LIKE 'dosage_form'");
        if ($check_column->rowCount() == 0) {
            $conn->exec("ALTER TABLE medicines ADD COLUMN dosage_form VARCHAR(50) DEFAULT NULL AFTER type");
            echo "<div style='color: green;'>✓ تم إضافة عمود dosage_form إلى جدول medicines بنجاح</div>";
        } else {
            echo "<div style='color: blue;'>✓ عمود dosage_form موجود بالفعل في جدول medicines</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color: orange;'>⚠ لم يتم التحقق من عمود dosage_form: " . $e->getMessage() . "</div>";
    }
    
    // إنشاء جدول dispensed_medicines إذا لم يكن موجوداً
    $dispensed_medicines_sql = "
    CREATE TABLE IF NOT EXISTS `dispensed_medicines` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `prescription_id` int(11) NOT NULL,
      `pharmacist_id` int(11) NOT NULL,
      `dispense_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `status` enum('complete','partial') DEFAULT 'complete',
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `prescription_id` (`prescription_id`),
      KEY `pharmacist_id` (`pharmacist_id`),
      KEY `idx_dispense_date` (`dispense_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->exec($dispensed_medicines_sql);
    echo "<div style='color: green;'>✓ تم إنشاء جدول dispensed_medicines بنجاح</div>";
    
    // التحقق من وجود الجداول وعرض رسالة نجاح
    $tables = ['prescription_items', 'prescription_medicines', 'dispensed_medicines'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->rowCount() == 0) {
            echo "<div style='color: red;'>❌ لم يتم إنشاء جدول $table</div>";
            $all_tables_exist = false;
        }
    }
    
    if ($all_tables_exist) {
        echo "<h2 style='color: green;'>تم إنشاء جميع الجداول المطلوبة بنجاح! ✓</h2>";
        echo "<p>يمكنك الآن <a href='pharmacist/dashboard.php'>الانتقال إلى لوحة التحكم</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
    error_log("Database Error: " . $e->getMessage());
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        direction: rtl;
        text-align: right;
        line-height: 1.6;
        margin: 20px;
        background-color: #f8f9fa;
    }
    
    h1, h2 {
        color: #1e3c72;
    }
    
    div {
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style> 