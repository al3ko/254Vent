<?php
session_start();
if (!isset($_SESSION['user_email']) || !$_SESSION['is_admin']) {
    header("Location: login.html");
    exit();
}

$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$isEditing = false;
$event = [
    'id' => '',
    'title' => '',
    'event_date' => '',
    'start_time' => '',
    'end_time' => '',
    'venue' => '',
    'content' => ''
];

if (isset($_GET['edit']) && isset($_GET['id'])) {
    $isEditing = true;
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $event = $res->fetch_assoc();
    } else {
        echo "Event not found.";
        exit;
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $title = $_POST['title'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue = $_POST['venue'];
    $content = $_POST['content'];
    
    if ($isEditing) {
        // Update existing event
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE posts SET title=?, event_date=?, start_time=?, end_time=?, venue=?, content=? WHERE id=?");
        $stmt->bind_param("ssssssi", $title, $event_date, $start_time, $end_time, $venue, $content, $id);
    } else {
        // Create new event
        $stmt = $conn->prepare("INSERT INTO posts (title, event_date, start_time, end_time, venue, content) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $title, $event_date, $start_time, $end_time, $venue, $content);
    }
    
    if ($stmt->execute()) {
        // Redirect after successful operation
        header("Location: admin-homepage.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo $isEditing ? "Edit Event" : "Add Event"; ?> - UniVENT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #dcd0c0;
      color: #333;
      padding: 2rem;
    }

    .form-wrapper {
      max-width: 700px;
      background: white;
      margin: 2rem auto;
      padding: 2rem 2.5rem;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 2rem;
      font-size: 2rem;
      color: #2c3e50;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #34495e;
      margin-top: 1.5rem;
    }

    input, textarea, select {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      background-color: #f9f9f9;
    }

    input[type="file"] {
      padding: 0.5rem;
      background-color: white;
    }

    textarea {
      resize: vertical;
    }

    button {
      width: 100%;
      margin-top: 2rem;
      padding: 1rem;
      font-size: 1.1rem;
      font-weight: 600;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #2980b9;
    }

    .back-link {
      text-align: center;
      margin-top: 1rem;
    }

    .back-link a {
      color: #3498db;
      text-decoration: none;
      font-weight: 500;
    }

    .back-link a:hover {
      text-decoration: underline;
    }

    @media screen and (max-width: 768px) {
      .form-wrapper {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

  <div class="form-wrapper">
    <h2><?php echo $isEditing ? "Edit Event" : "Create Event"; ?></h2>

    <form action="" method="POST" enctype="multipart/form-data">
      <?php if ($isEditing): ?>
        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
      <?php endif; ?>

      <label for="title">Event Title</label>
      <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

      <label for="event_date">Date</label>
      <input type="date" name="event_date" id="event_date" value="<?php echo $event['event_date']; ?>" required>

      <label for="start_time">Start Time</label>
      <input type="time" name="start_time" id="start_time" value="<?php echo $event['start_time']; ?>" required>

      <label for="end_time">End Time</label>
      <input type="time" name="end_time" id="end_time" value="<?php echo $event['end_time']; ?>" required>

      <label for="venue">Venue</label>
      <input type="text" name="venue" id="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>

      <label for="content">Description</label>
      <textarea name="content" id="content" rows="5"><?php echo htmlspecialchars($event['content']); ?></textarea>

      <label for="image">Event Image</label>
      <input type="file" name="image" id="image" accept="image/*" <?php echo $isEditing ? '' : 'required'; ?>>

      <button type="submit"><?php echo $isEditing ? "Update Event" : "Create Event"; ?></button>
    </form>

    <div class="back-link">
      <a href="admin-homepage.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
  </div>

</body>
</html>