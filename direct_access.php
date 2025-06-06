<?php
// Direct access link to test pharmacist dashboard

// Check if the pharmacist dashboard exists
if (file_exists(__DIR__ . '/pharmacist/dashboard.php')) {
    // Redirect to the pharmacist dashboard
    header("Location: pharmacist/dashboard.php");
    exit();
} else {
    echo "Error: pharmacist/dashboard.php does not exist.<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "Files in pharmacist directory:<br>";
    if (is_dir(__DIR__ . '/pharmacist')) {
        $files = scandir(__DIR__ . '/pharmacist');
        echo "<pre>";
        print_r($files);
        echo "</pre>";
    } else {
        echo "pharmacist directory does not exist.";
    }
}
?> 