<?php
session_start(); 
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sign.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$selectedPic = $_POST['selected_pic'] ?? ''; 

if ($selectedPic === '') {
    header("Location: sign.php?error=noPic");
    exit;
}

if ($username === '' || $email === '' || $password === '') {
    header("Location: sign.php?error=missing");
    exit;
}

// 1) check if email exists
$stmt = $conn->prepare("SELECT userID FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    header("Location: sign.php?error=emailExists");
    exit;
}

// 2) hash password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Validate password strength
$uppercase = preg_match('@[A-Z]@', $password);
$lowercase = preg_match('@[a-z]@', $password);
$number    = preg_match('@[0-9]@', $password);

if (!$uppercase || !$lowercase || !$number || strlen($password) < 8) {
    header("Location: sign.php?error=weakPassword");
    exit;
}

// 3) insert user with chosen profile picture
$stmt = $conn->prepare("
    INSERT INTO `user` (username, email, password, profilePicture, createdAt)
    VALUES (?, ?, ?, ?, NOW())
");

if (!$stmt) {
    die("PREPARE ERROR: " . $conn->error);
}

$stmt->bind_param("ssss", $username, $email, $hashed, $selectedPic);

if (!$stmt->execute()) {
    die("<h1 style='color:red;'>INSERT ERROR:</h1>" . $stmt->error);
}

$newUserId = $stmt->insert_id;
$stmt->close();

// Auto login - FIXED SESSION VARIABLE NAME
$_SESSION['userID'] = $newUserId;  
$_SESSION['username'] = $username;

// 4) create shelf row for this user
$stmt = $conn->prepare("INSERT INTO shelf (userID) VALUES (?)");
$stmt->bind_param("i", $newUserId);
$stmt->execute();
$stmt->close();

header("Location: home.php");
exit;
