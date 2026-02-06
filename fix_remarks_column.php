<?php
include("config/db.php");

echo "Attempting to add 'remarks' column to 'works' table...<br>";

// Check if column exists first to avoid errors
$check = mysqli_query($conn, "SHOW COLUMNS FROM works LIKE 'remarks'");

if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE works ADD COLUMN remarks TEXT DEFAULT NULL AFTER status";
    if (mysqli_query($conn, $sql)) {
        echo "<h3 style='color:green'>Success: Column 'remarks' added successfully!</h3>";
        echo "<p>You can now try rejecting a document again.</p>";
    } else {
        echo "<h3 style='color:red'>Error: " . mysqli_error($conn) . "</h3>";
    }
} else {
    echo "<h3 style='color:orange'>Notice: Column 'remarks' already exists.</h3>";
}
?>
