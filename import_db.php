<?php
require_once 'includes/config.php';

try {
    // قراءة ملف SQL
    $sql = file_get_contents('hospital_db.sql');
    
    // تقسيم الاستعلامات
    $queries = explode(';', $sql);
    
    // تنفيذ كل استعلام
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "تم استيراد هيكل قاعدة البيانات بنجاح!";
} catch (PDOException $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?> 