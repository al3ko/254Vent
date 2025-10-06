<?php
session_start();
if (!isset($_SESSION['user_email']) || !$_SESSION['is_admin']) {
    header("Location: homepage.html");
    exit();
}

require 'connect.php';

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($event_id <= 0) {
    echo "Invalid Event ID.";
    exit;
}

$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Event not found.";
    exit;
}

$event = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            padding: 2rem;
        }
        form {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label, input, textarea, select {
            display: block;
            width: 100%;
            margin-bottom: 1rem;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Edit Event</h2>
<form action="update-event.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">

    <label>Title:</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

    <label>Date:</label>
    <input type="date" name="event_date" value="<?php echo htmlspecialchars($event['event_date']); ?>" required>

    <label>Start Time:</label>
    <input type="time" name="start_time" value="<?php echo htmlspecialchars($event['start_time']); ?>" required>

    <label>End Time:</label>
    <input type="time" name="end_time" value="<?php echo htmlspecialchars($event['end_time']); ?>" required>

    <label>Venue:</label>
    <input type="text" name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>

    <label>Description:</label>
    <textarea name="content" rows="5" required><?php echo htmlspecialchars($event['content']); ?></textarea>

    <label>Update Image (optional):</label>
    <input type="file" name="image">

    <input type="submit" value="Update Event">
</form>

</body>
</html>