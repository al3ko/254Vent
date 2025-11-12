<?php
session_start();



// Use the database connection file
include('db_connect.php');

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    
    // Delete from events table using event_id
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // CORRECTED: Redirect back to manage-events.php instead of admin-homepage.php
        header("Location: manage-events.php?deleted=1");
        exit;
    } else {
        echo "Error deleting event: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "No event ID specified.";
}

$conn->close();
?>