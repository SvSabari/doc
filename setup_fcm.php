<?php
include("config/db.php");

$sql = "CREATE TABLE IF NOT EXISTS user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('admin', 'user') NOT NULL,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, token(100))
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'user_tokens' created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
