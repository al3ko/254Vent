<?php
session_start(); // Start the session

// DB Connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $delete_stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $delete_id);
        if ($delete_stmt->execute()) {
            $success_message = "Event deleted successfully!";
        } else {
            $error_message = "Error deleting event: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
}

// Fetch events
$events_result = mysqli_query($conn, "SELECT * FROM events ORDER BY start_date DESC");
$has_events = $events_result !== false && mysqli_num_rows($events_result) > 0;

// Use a dummy email if session email is not set
$session_email = $_SESSION['user_email'] ?? "admin@univent.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Events - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
<style>
/* Add your CSS here (same as before) */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background:#f5f5dc; color:#333; line-height:1.6; padding:20px; }
header { background:#90ee90; padding:0.8rem 1.5rem; text-align:center; border-radius:40px; width:80%; margin:20px auto; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
header h1 { color:#2d5016; font-size:1.8rem; }
.container { max-width:1000px; margin:2rem auto; background:#fff; padding:2rem; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); border:2px solid #90ee90; }
.top-actions { display:flex; justify-content:flex-end; margin-bottom:1.5rem; }
.top-actions a { background:#32cd32; color:white; text-decoration:none; padding:0.7rem 1.2rem; font-weight:bold; border-radius:8px; transition:all 0.3s ease; display:flex; align-items:center; gap:0.5rem; }
.top-actions a:hover { background:#228b22; transform:translateY(-2px); }
.welcome-message { background:#f0f8ff; padding:10px 15px; border-radius:6px; margin-bottom:1rem; border-left:4px solid #90ee90; color:#2d5016; }
.error-message { background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:1rem; border-left:4px solid #dc3545; }
.success-message { background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:1rem; border-left:4px solid #28a745; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { text-align:left; padding:12px 16px; border-bottom:1px solid #ddd; vertical-align:middle; }
th { background:#f0fff0; color:#2d5016; font-weight:600; }
tr:hover { background:#f8fff8; }
.event-image { max-width:80px; max-height:60px; border-radius:4px; object-fit:cover; }
td.action-buttons { display:flex; align-items:center; gap:10px; }
.action-buttons a { padding:6px 12px; border-radius:5px; color:white; text-decoration:none; font-weight:600; transition:all 0.3s ease; border:2px solid transparent; }
.edit-btn { background:#3498db; }
.edit-btn:hover { background:white; color:#3498db; border-color:#3498db; }
.delete-btn { background:#e74c3c; }
.delete-btn:hover { background:white; color:#e74c3c; border-color:#e74c3c; }
.view-btn, .comments-btn { display:none !important; }
.no-events { text-align:center; padding:40px; color:#666; background:#f8f9fa; border-radius:8px; border:2px dashed #ddd; }
@media screen and (max-width:768px) { header { width:90%; border-radius:25px; } .container { margin:1rem; padding:1.5rem; } .action-buttons { flex-direction:column; } table { display:block; overflow-x:auto; } }
@media screen and (max-width:480px) { header h1 { font-size:1.4rem; } body{padding:10px;} }
</style>
</head>
<body>
<header><h1>Manage Events</h1></header>

<div class="container">
  <div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($session_email); ?> 
    | <a href="logout.php" style="color:#32cd32;text-decoration:none;">Logout</a>
  </div>

  <?php if(isset($success_message)): ?><div class="success-message"><?php echo $success_message;?></div><?php endif; ?>
  <?php if(isset($error_message)): ?><div class="error-message"><?php echo $error_message;?></div><?php endif; ?>

  <div class="top-actions">
    <a href="add-event.php"><i class="fas fa-plus-circle"></i> Create New Event</a>
  </div>

  <?php if($has_events): ?>
  <table>
    <thead>
      <tr>
        <th>Image</th>
        <th>Title & Description</th>
        <th>Date & Time</th>
        <th>Venue</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($event=mysqli_fetch_assoc($events_result)): ?>
      <tr>
        <td>
          <?php if(!empty($event['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($event['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                 class="event-image" onerror="this.style.display='none'">
          <?php else: ?>
            <div style="width:80px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;"><i class="fas fa-image"></i></div>
          <?php endif; ?>
        </td>
        <td>
          <strong><?php echo htmlspecialchars($event['title']); ?></strong>
          <?php if(!empty($event['description'])): ?>
            <br><small style="color:#666;"><?php echo substr(htmlspecialchars($event['description']),0,50); ?>...</small>
          <?php endif; ?>
        </td>
        <td>
          <?php echo date("M j, Y", strtotime($event['start_date'])) . "<br><small>" . date("g:i A", strtotime($event['start_date'])) . " - " . date("g:i A", strtotime($event['end_date'])) . "</small>"; ?>
        </td>
        <td><?php echo htmlspecialchars($event['venue']); ?></td>
        <td class="action-buttons">
          <a href="edit-event.php?id=<?php echo $event['event_id']; ?>" class="edit-btn">✏️ Edit</a>
          <a href="?delete_id=<?php echo $event['event_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event?');">🗑️ Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="no-events">
      <i class="fas fa-calendar-times" style="font-size:3rem;color:#ccc;margin-bottom:1rem;"></i>
      <h3>No Events Found</h3>
      <p>Get started by creating your first event!</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>

<?php
if(isset($events_result) && $events_result !== false){ mysqli_free_result($events_result); }
mysqli_close($conn);
?>
