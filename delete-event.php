<?php
session_start();

if (!isset($_SESSION["user_email"]) || !$_SESSION["is_admin"]) {
    header("Location: login.html");
    exit;
}

$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: admin-homepage.php?deleted=1");
        exit;
    } else {
        echo "Error deleting event: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
