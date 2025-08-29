<?php
$host = "localhost";     // Your database host
$user = "root";          // Your MySQL username  
$pass = "";              // Your MySQL password
$dbname = "organic_trace"; // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 for better compatibility
$conn->set_charset("utf8");
?>