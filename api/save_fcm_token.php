<?php
session_start();
include("../config/db.php");

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

$token = mysqli_real_escape_string($conn, $data['token']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];
$role = isset($_SESSION['user_id']) ? 'user' : 'admin';

// Save or update token
$sql = "INSERT INTO user_tokens (user_id, user_role, token) 
        VALUES ('$user_id', '$role', '$token') 
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["status" => "success", "message" => "Token saved"]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
}
?>
