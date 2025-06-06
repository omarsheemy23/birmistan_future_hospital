<?php
// This script creates virtual directory mappings for pharmacy and pharmacist directories

if (!file_exists(__DIR__ . '/pharmacy')) {
    // Create the pharmacy directory if it doesn't exist
    mkdir(__DIR__ . '/pharmacy', 0777, true);
}

// Copy all files from pharmacist to pharmacy
$pharmacist_files = scandir(__DIR__ . '/pharmacist');
foreach ($pharmacist_files as $file) {
    if ($file != '.' && $file != '..') {
        if (!file_exists(__DIR__ . '/pharmacy/' . $file)) {
            copy(__DIR__ . '/pharmacist/' . $file, __DIR__ . '/pharmacy/' . $file);
        }
    }
}

echo "Synchronization completed. All files from the pharmacist directory have been copied to the pharmacy directory.";
echo "<br><br>";
echo "Please try logging in as a pharmacist again.";
echo "<br><br>";
echo "<a href='login.php'>Go to Login</a>";
?> 