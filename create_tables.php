<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
require_once 'includes/config.php'; // Using the existing config file

echo "<h1>Creating Missing Tables</h1>";

try {
    // Create prescription_medicines table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `prescription_medicines` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `prescription_id` int(11) NOT NULL,
      `medicine_id` int(11) NOT NULL,
      `quantity` int(11) NOT NULL,
      `dosage_instructions` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `prescription_id` (`prescription_id`),
      KEY `medicine_id` (`medicine_id`),
      CONSTRAINT `prescription_medicines_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
      CONSTRAINT `prescription_medicines_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    // Execute the SQL
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "<p style='color:green'>Successfully created prescription_medicines table!</p>";
    } else {
        echo "<p style='color:red'>Failed to create prescription_medicines table.</p>";
    }
    
    // Check if the table was actually created
    $checkSql = "SHOW TABLES LIKE 'prescription_medicines'";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo "<p style='color:green'>Verified: prescription_medicines table exists!</p>";
    } else {
        echo "<p style='color:red'>Verification failed: prescription_medicines table does not exist.</p>";
        
        // Try to identify if the prescriptions table exists (required for foreign key)
        $checkPrescriptionsSql = "SHOW TABLES LIKE 'prescriptions'";
        $checkPrescriptionsStmt = $pdo->prepare($checkPrescriptionsSql);
        $checkPrescriptionsStmt->execute();
        
        if ($checkPrescriptionsStmt->rowCount() == 0) {
            echo "<p style='color:red'>Error: prescriptions table does not exist. This is required for the foreign key constraint.</p>";
        }
        
        // Try to identify if the medicines table exists (required for foreign key)
        $checkMedicinesSql = "SHOW TABLES LIKE 'medicines'";
        $checkMedicinesStmt = $pdo->prepare($checkMedicinesSql);
        $checkMedicinesStmt->execute();
        
        if ($checkMedicinesStmt->rowCount() == 0) {
            echo "<p style='color:red'>Error: medicines table does not exist. This is required for the foreign key constraint.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}
?> 