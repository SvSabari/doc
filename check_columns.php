<?php
include("config/db.php");
$res = mysqli_query($conn, "SHOW COLUMNS FROM users");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
