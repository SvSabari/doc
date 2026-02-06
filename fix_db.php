<?php
// Try multiple paths to find db.php
$paths = [
    "config/db.php",
    "../config/db.php",
    "auth/config/db.php",
    "doc/config/db.php"
];

$conn = null;
foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "Found db.php at: $path<br>";
        include($path);
        break;
    }
}

if (!$conn) {
    die("Error: Could not find config/db.php. Current directory: " . getcwd());
}

echo "Attempting to add 'remarks' column to 'works' table...<br>";

// Check if column exists
try {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM works LIKE 'remarks'");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE works ADD COLUMN remarks TEXT DEFAULT NULL AFTER status";
        if (mysqli_query($conn, $sql)) {
            echo "<h2 style='color:green'>Success: Column 'remarks' added successfully!</h2>";
        } else {
            echo "<h2 style='color:red'>Error: " . mysqli_error($conn) . "</h2>";
        }
    } else {
        echo "<h2 style='color:blue'>Notice: Column 'remarks' already exists.</h2>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
