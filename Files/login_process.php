<?php
ini_set('display_errors', 1);
require_once 'connection.php';
session_start();

// 1. Get input values
$email    = trim($_POST['email'] ?? "");
$password = $_POST['password'] ?? "";

// If form fields are empty → redirect with error
if ($email === "" || $password === "") {
    header("Location: log.php?error=empty");
    exit;
}

// 2. Check if user exists
$stmt = $conn->prepare("SELECT userID, password FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Email not found
    header("Location: log.php?error=invalid");
    exit;
}

$stmt->bind_result($userID, $hashedPassword);
$stmt->fetch();

// 3. Verify password using password_verify()
if (!password_verify($password, $hashedPassword)) {
    // Wrong password
    header("Location: log.php?error=invalid");
    exit;
}

// 4. Login success → store session data
$_SESSION['userID'] = $userID;

// 5. Redirect to home page
header("Location: home.php");
exit;
