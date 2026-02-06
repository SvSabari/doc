<?php
include("../config/db.php");
$res = mysqli_query($conn, "SELECT name, email, status, must_reset_password FROM users");
echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Status</th><th>Must Reset</th></tr>";
while($row = mysqli_fetch_assoc($res)){
    echo "<tr><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['status']}</td><td>{$row['must_reset_password']}</td></tr>";
}
echo "</table>";
?>
