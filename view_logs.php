<?php
   // REMOVE THIS FILE AFTER DEBUGGING!
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   
   echo "<h2>Debug Information</h2>";
   
   // Show current directory
   echo "<h3>Current Directory:</h3>";
   echo "<pre>" . __DIR__ . "</pre>";
   
   // Show directory contents
   echo "<h3>Files in Root Directory:</h3>";
   echo "<pre>";
   print_r(scandir(__DIR__));
   echo "</pre>";
   
   // Show includes directory if exists
   if (is_dir(__DIR__ . '/includes')) {
       echo "<h3>Files in /includes Directory:</h3>";
       echo "<pre>";
       print_r(scandir(__DIR__ . '/includes'));
       echo "</pre>";
   }
   
   // Show PHP error log if exists
   $error_log = __DIR__ . '/php_errors.log';
   if (file_exists($error_log)) {
       echo "<h3>PHP Error Log:</h3>";
       echo "<pre>" . htmlspecialchars(file_get_contents($error_log)) . "</pre>";
   } else {
       echo "<h3>PHP Error Log: NOT FOUND</h3>";
   }
   
   // Show Apache error log location
   echo "<h3>PHP Error Log Setting:</h3>";
   echo "<pre>" . ini_get('error_log') . "</pre>";
   
   // Test file includes
   echo "<h3>Testing File Includes:</h3>";
   $files_to_test = [
       'db.php',
       'includes/security.php',
       'includes/email_helper.php',
       'anglican_province.php',
       'includes/scope_helpers.php'
   ];
   
   foreach ($files_to_test as $file) {
       $full_path = __DIR__ . '/' . $file;
       if (file_exists($full_path)) {
           echo "✅ $file EXISTS<br>";
       } else {
           echo "❌ $file MISSING<br>";
       }
   }
   ?>
