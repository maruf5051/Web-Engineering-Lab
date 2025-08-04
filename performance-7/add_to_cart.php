<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "coffee_shor");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user email from DB
$user_id = $_SESSION['user_id'];
$getEmail = $conn->prepare("SELECT email FROM users WHERE id = ?");
$getEmail->bind_param("i", $user_id);
$getEmail->execute();
$getResult = $getEmail->get_result();
$user = $getResult->fetch_assoc();
$email = $user['email'] ?? null;

if (!$email) {
    die("User email not found.");
}

// Get item name
$item_name = $_POST['item_name'] ?? '';
date_default_timezone_set('Asia/Dhaka');
$order_time = date("Y-m-d H:i:s");


// Insert into orders table
$stmt = $conn->prepare("INSERT INTO orders (email, `order`, `time`) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $item_name, $order_time);

if ($stmt->execute()) {
    header("Location: coffee.php?order_success=1");
} else {
    echo "Failed to add order.";
}
