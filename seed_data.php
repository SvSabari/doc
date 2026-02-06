<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "doc_verification";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Create Sample User
$email = "john@example.com";
$check = $conn->query("SELECT id FROM users WHERE email='$email'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO users (name, email, status) VALUES ('John Doe', '$email', 'active')");
    echo "Created user: John Doe\n";
}
$user_id = $conn->query("SELECT id FROM users WHERE email='$email'")->fetch_assoc()['id'];

// 2. Create Sample Work
$title = "Identify Verification Level 1";
$check = $conn->query("SELECT id FROM works WHERE title='$title' AND user_id=$user_id");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO works (user_id, title, assigned_datetime, due_datetime, status) 
                  VALUES ($user_id, '$title', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'submitted')");
    echo "Created work: $title\n";
}
$work_id = $conn->query("SELECT id FROM works WHERE title='$title' AND user_id=$user_id")->fetch_assoc()['id'];

// 3. Create Sample Submission
$file = "sample_id_card.png"; // Placeholder file name
$check = $conn->query("SELECT id FROM submissions WHERE work_id=$work_id");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO submissions (work_id, file_path) VALUES ($work_id, '$file')");
    echo "Created submission for work ID: $work_id\n";
} else {
    echo "Sample data already exists.\n";
}

echo "\nDone! Refresh the page to see the data.";
$conn->close();
?>
