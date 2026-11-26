<?php
// Database configuration
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "playlist";
$port = 3307; // Configured for local XAMPP MySQL port 3307

mysqli_report(MYSQLI_REPORT_OFF);

// Attempt connection on port 3307 first, with fallback to 3306
$conn = @new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $conn = @new mysqli($host, $user, $password, $database, 3306);
    if (!$conn->connect_error) {
        $port = 3306;
    }
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
