<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

$conn = new mysqli("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete registration
$stmt = $conn->prepare("DELETE FROM event_registrations WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);

if ($stmt->execute()) {
    // ✅ Redirect to events page after cancellation
    header("Location: events.php?canceled=1");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
