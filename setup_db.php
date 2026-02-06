<?php
$host = "localhost";
$user = "root";
$pass = "";

// Connect to MySQL
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database
$sql = "CREATE DATABASE IF NOT EXISTS doc_verification";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

$conn->select_db("doc_verification");

// Create Admins Table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
$conn->query($sql);

// Create Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create Works Table
$sql = "CREATE TABLE IF NOT EXISTS works (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    assigned_datetime DATETIME NOT NULL,
    due_datetime DATETIME NOT NULL,
    sample_doc VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'submitted', 'completed', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) !== TRUE) echo "Error creating works table: " . $conn->error . "\n";

// Create Submissions Table
$sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_id INT(11) UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE CASCADE
)";
if ($conn->query($sql) !== TRUE) echo "Error creating submissions table: " . $conn->error . "\n";

// Create Password Resets Table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry DATETIME NOT NULL
)";
$conn->query($sql);

// Insert Default Admin
$username = "admin";
$password = "admin123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->query("SELECT * FROM admins WHERE username='$username'");
if ($check->num_rows == 0) {
    $sql = "INSERT INTO admins (username, password) VALUES ('$username', '$hashed_password')";
    if ($conn->query($sql) === TRUE) {
        echo "Default admin created (User: admin, Pass: admin123)\n";
    } else {
        echo "Error creating admin: " . $conn->error . "\n";
    }
} else {
    echo "Admin user already exists.\n";
}

$conn->close();
?>
