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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue = $_POST['venue'];

    $image_path = $_POST['existing_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $image_path = $uploadDir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    $stmt = $conn->prepare("UPDATE posts SET title=?, content=?, event_date=?, start_time=?, end_time=?, image_path=?, venue=? WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $content, $event_date, $start_time, $end_time, $image_path, $venue, $id);

    if ($stmt->execute()) {
        header("Location: adminhomepage.php?updated=1");
        exit;
    } else {
        echo "Error updating event: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
