<?php
// EMERGENCY FIX SCRIPT
// Try to connect and fix the database immediately

$paths = [
    __DIR__ . "/config/db.php",
    __DIR__ . "/../config/db.php",
    __DIR__ . "/auth/config/db.php",
    __DIR__ . "/../auth/config/db.php",
    $_SERVER['DOCUMENT_ROOT'] . "/doc_verification/doc/config/db.php"
];

$conn = null;
foreach ($paths as $p) {
    if (file_exists($p)) {
        include($p);
        if ($conn) break;
    }
}

if (!$conn) {
    // manual connection attempt if config fails
    $conn = mysqli_connect("localhost", "root", "", "doc_verification"); // assumption
    if (!$conn) {
        die("Could not connect to database. Please check your settings.");
    }
}

echo "Connected. Fixing table...<br>";
$sql = "ALTER TABLE works ADD COLUMN remarks TEXT DEFAULT NULL AFTER status";
try {
    if (mysqli_query($conn, $sql)) {
        echo "<h1>FIX SUCCESSFUL: 'remarks' column added.</h1>";
    } else {
        $err = mysqli_error($conn);
        if (strpos($err, "Duplicate") !== false) {
             echo "<h1>ALREADY FIXED: Column exists.</h1>";
        } else {
             echo "<h1>Error: $err</h1>";
        }
    }
} catch (Exception $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
echo "<br><a href='http://localhost/doc_verification/doc/admin/verify.php?id=1'>Go back to Verify</a>"; // guess path
?>
