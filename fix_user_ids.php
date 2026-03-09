<?php
include("config/db.php");

echo "<h3>ID Range Separation Fix</h3>";

$sql = "ALTER TABLE users AUTO_INCREMENT = 5001";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'><b>SUCCESS:</b> The User ID range has been shifted to start from 5001.</p>";
    echo "<p>New users created from now on will have IDs like 5001, 5002, etc.</p>";
    echo "<p>Existing users (IDs 1, 2, 3...) remain unaffected.</p>";
} else {
    echo "<p style='color: red;'><b>ERROR:</b> " . mysqli_error($conn) . "</p>";
}

echo "<br><a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
?>
