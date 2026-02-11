<?php
include("config/db.php");

$tables = ['admins', 'users'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $q = mysqli_query($conn, "SELECT COUNT(*) as count, MAX(id) as max_id FROM $table");
    $data = mysqli_fetch_assoc($q);
    echo "Count: " . $data['count'] . "\n";
    echo "Max ID: " . $data['max_id'] . "\n";
    
    $res = mysqli_query($conn, "SHOW CREATE TABLE $table");
    $row = mysqli_fetch_row($res);
    echo "Schema: " . $row[1] . "\n\n";
}
?>
