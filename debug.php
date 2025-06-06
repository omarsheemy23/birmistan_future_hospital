<?php
// Show all server variables and request information for debugging
echo "<h1>Debug Information</h1>";
echo "<h2>Server Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Session Information</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";

echo "<h2>File System Check</h2>";
echo "<ul>";
echo "<li>pharmacist directory exists: " . (is_dir(__DIR__ . '/pharmacist') ? 'Yes' : 'No') . "</li>";
echo "<li>pharmacist/dashboard.php exists: " . (file_exists(__DIR__ . '/pharmacist/dashboard.php') ? 'Yes' : 'No') . "</li>";
echo "<li>pharmacy directory exists: " . (is_dir(__DIR__ . '/pharmacy') ? 'Yes' : 'No') . "</li>";
echo "<li>pharmacy/dashboard.php exists: " . (file_exists(__DIR__ . '/pharmacy/dashboard.php') ? 'Yes' : 'No') . "</li>";
echo "</ul>";

echo "<h2>PHP Info</h2>";
phpinfo();
?> 