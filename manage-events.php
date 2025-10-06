<?php
session_start();

// Restrict access to admins only
if (!isset($_SESSION["user_email"]) || !$_SESSION["is_admin"]) {
    header("Location: homepage.html");
    exit();
}

// DB Connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$events = mysqli_query($conn, "SELECT * FROM posts ORDER BY event_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Events - Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 0;
    }

    header {
      background: #dcd0c0;
      padding: 1.5rem 2rem;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    header h1 {
      margin: 0;
      color: #222;
      font-size: 2rem;
    }

    .container {
      max-width: 1000px;
      margin: 2rem auto;
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .top-actions {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1.5rem;
    }

    .top-actions a {
      background: #28a745;
      color: white;
      text-decoration: none;
      padding: 0.7rem 1.2rem;
      font-weight: bold;
      border-radius: 8px;
      transition: background 0.3s ease;
    }

    .top-actions a:hover {
      background: #218838;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      text-align: left;
      padding: 12px 16px;
      border-bottom: 1px solid #ddd;
    }

    th {
      background: #f4f4f4;
    }

    .action-buttons a {
      margin-right: 10px;
      padding: 6px 12px;
      border-radius: 5px;
      color: white;
      text-decoration: none;
      font-weight: 600;
    }

    .edit-btn {
      background: #3498db;
    }

    .edit-btn:hover {
      background: #2980b9;
    }

    .delete-btn {
      background: #e74c3c;
    }

    .delete-btn:hover {
      background: #c0392b;
    }

    @media screen and (max-width: 768px) {
      .top-actions {
        flex-direction: column;
        align-items: flex-start;
      }

      .action-buttons a {
        display: block;
        margin-bottom: 8px;
      }
    }
  </style>
</head>
<body>
  <header>
    <h1>Manage Events</h1>
  </header>

  <div class="container">
    <div class="top-actions">
      <a href="add-event.php"><i class="fas fa-plus-circle"></i> Create New Event</a>
    </div>

    <?php if (mysqli_num_rows($events) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($event = mysqli_fetch_assoc($events)): ?>
            <tr>
              <td><?php echo htmlspecialchars($event['title']); ?></td>
              <td>
                <?php
                  echo date("F j, Y", strtotime($event['event_date'])) .
                       " (" . date("g:i A", strtotime($event['start_time'])) . " - " .
                       date("g:i A", strtotime($event['end_time'])) . ")";
                ?>
              </td>
              <td><?php echo htmlspecialchars($event['venue']); ?></td>
              <td class="action-buttons">
                <a href="add-event.php?edit=1&id=<?php echo $event['id']; ?>" class="edit-btn">Edit</a>
                <a href="delete-event.php?id=<?php echo $event['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No events found.</p>
    <?php endif; ?>
  </div>
</body>
</html>

<?php mysqli_close($conn); ?>
