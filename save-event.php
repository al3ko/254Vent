<?php
session_start();



// DB Connection
$servername = "localhost";
$username   = "root";
$password   = "QWE123!@#qwe";
$dbname     = "univent";
$port       = 3307;

$conn = mysqli_connect($servername, $username, $password, $dbname, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Form Data
$title        = trim($_POST['title']);
$content      = trim($_POST['content']);
$event_date   = $_POST['event_date'];
$start_time   = $_POST['start_time'];
$end_time     = $_POST['end_time'];
$venue        = trim($_POST['venue']);
$registration = isset($_POST['registration_link']) ? trim($_POST['registration_link']) : "";

// Handle Image Upload
$image_path = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileTmp  = $_FILES['image']['tmp_name'];
    $fileName = basename($_FILES['image']['name']);
    $targetFile = $targetDir . time() . "_" . $fileName;

    if (move_uploaded_file($fileTmp, $targetFile)) {
        $image_path = $targetFile;
    }
}

// Insert into Database
$sql = "INSERT INTO posts (title, content, event_date, start_time, end_time, venue, image_path, registration_link, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssssss", $title, $content, $event_date, $start_time, $end_time, $venue, $image_path, $registration);

if (mysqli_stmt_execute($stmt)) {
    echo "<script>alert('Event created successfully!'); window.location.href='admin.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
