<?php
$host = "localhost";
$user = "root";
$pass = "root"; // MAMP default
$dbname = "storylense";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
