<?php
// Database connection settings
$servername = "localhost";
$username   = "root";
$password   = "QWE123!@#qwe";
$database   = "univent";
$port       = 3307;

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database, $port);

// Check connection
if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// Optional: Uncomment for debugging
// echo "✅ Connected successfully to database '$database'";
?>
