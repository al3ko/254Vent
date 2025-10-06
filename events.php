<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION["user_id"];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle registration/unregistration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['event_id']) && !isset($_POST['comment_text'])) {
        $event_id = intval($_POST['event_id']);
        
        // Check if already registered
        $check = mysqli_query($conn, "SELECT * FROM event_registrations WHERE user_id = $user_id AND event_id = $event_id");
        
        if (mysqli_num_rows($check) > 0) {
            // Unregister
            mysqli_query($conn, "DELETE FROM event_registrations WHERE user_id = $user_id AND event_id = $event_id");
        } else {
            // Register
            mysqli_query($conn, "INSERT INTO event_registrations (user_id, event_id) VALUES ($user_id, $event_id)");
        }
        
        // Refresh to show updated status
        header("Location: profile.php?registered=true");
        exit();
    }
    
    // Handle comment submission
    if (isset($_POST['comment_text']) && isset($_POST['comment_event_id'])) {
        $comment_text = mysqli_real_escape_string($conn, $_POST['comment_text']);
        $event_id = intval($_POST['comment_event_id']);
        
        $stmt = $conn->prepare("INSERT INTO comments (event_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $event_id, $user_id, $comment_text);
        $stmt->execute();
    }
    
   // Handle comment deletion
if (isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['delete_comment']);
    $user_id = $_SESSION['user_id'];
    
    // Verify the comment exists and user has permission
    $check_query = "SELECT * FROM comments WHERE id = ? AND (user_id = ?";
    if ($isAdmin) {
        $check_query .= " OR 1=1"; // Admins can delete any comment
    }
    $check_query .= ")";
    
    $stmt = $conn->prepare($check_query);
    if ($isAdmin) {
        $stmt->bind_param("i", $comment_id);
    } else {
        $stmt->bind_param("ii", $comment_id, $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User has permission - delete the comment
        $delete_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $delete_stmt->bind_param("i", $comment_id);
        
        if ($delete_stmt->execute()) {
            header("Location: events.php");
            exit();
        } else {
            die("Delete failed: " . $conn->error);
        }
    } else {
        die("You don't have permission to delete this comment");
    }
}
    
    // Handle comment edit
    if (isset($_POST['edit_comment']) && isset($_POST['edited_comment_text'])) {
        $comment_id = intval($_POST['edit_comment']);
        $edited_text = mysqli_real_escape_string($conn, $_POST['edited_comment_text']);
        $user_id = $_SESSION['user_id'];
        
        // Check if user owns the comment
        $check = mysqli_query($conn, "SELECT * FROM comments WHERE id = $comment_id AND user_id = $user_id");
        
        if (mysqli_num_rows($check) > 0) {
            $stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ?");
            $stmt->bind_param("si", $edited_text, $comment_id);
            $stmt->execute();
        }
        
        header("Location: events.php");
        exit();
    }
}

// Get all events
$events = mysqli_query($conn, "SELECT * FROM posts ORDER BY event_date ASC");

// Get user's registered events
$registered_events = [];
$result = mysqli_query($conn, "SELECT event_id FROM event_registrations WHERE user_id = $user_id");
while ($row = mysqli_fetch_assoc($result)) {
    $registered_events[] = $row['event_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Events - UniVENT</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f5f5dc;
      color: #333;
      line-height: 1.6;
    }

    #header {
  background-color: #191970; /* Midnight blue */
  padding: 0.3rem 1.5rem;
  border-radius: 20px;
  margin: 1rem auto;
  max-width: 1200px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

nav h1 {
  font-size: 1.8rem;
  font-weight: bold;
}

nav h1 a {
  color: #fff;
  text-decoration: none;
}

.nav-links {
  display: flex;
  gap: 1rem;
}

.nav-links a {
  color: #f0f0f0;
  text-decoration: none;
  font-weight: 500;
  padding: 0.3rem 0.7rem;
  border-radius: 5px;
  transition: background-color 0.3s;
}

.nav-links a:hover {
  background-color: #4682b4; /* steel blue on hover */
  color: #fff;
}

    .event-section {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .event {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 1.5rem;
      border: 1px solid #ddd;
      display: flex;
      gap: 1rem;
      transition: all 0.3s ease;
      position: relative;
      max-height: 180px;
      overflow: hidden;
    }

    .event.expanded {
      max-height: 75vh;
      overflow-y: auto;
    }

    .event-image {
      flex: 0 0 120px;
      height: 120px;
      overflow: hidden;
      border-radius: 6px;
      border: 1px solid #eee;
    }

    .event-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .event-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .event-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
    }

    .event h2 {
      color: #2d3748;
      margin-bottom: 0.3rem;
      font-size: 1.1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .event-time {
      display: block;
      color: #666;
      font-size: 0.85rem;
      margin-bottom: 0.3rem;
    }

    .event-venue {
      color: #444;
      font-size: 0.9rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

  .event-description {
  color: #444;
  font-size: 0.9rem;
  line-height: 1.4;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2; 
  -webkit-box-orient: vertical;
  overflow: hidden;
  transition: all 0.3s ease;
}

.event.expanded .event-description {
  -webkit-line-clamp: unset; 
  display: block;
  overflow: visible;
}

    .see-more-btn {
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      background: #FFA500;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 4px 8px;
      font-size: 0.8rem;
      cursor: pointer;
      z-index: 2;
    }

    .event-actions {
      position: absolute;
      right: 1rem;
      top: 1rem;
    }

    .register-form {
      display: inline-block;
    }

    .register-btn, .unregister-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      font-size: 0.85rem;
    }

    .register-btn {
      background-color: #FFA500;
      color: white;
    }

    .register-btn:hover {
      background-color: #e69500;
    }

    .unregister-btn {
      background-color: #f44336;
      color: white;
    }

    .unregister-btn:hover {
      background-color: #d32f2f;
    }

    .admin-actions {
      margin-top: 0.5rem;
      display: flex;
      gap: 0.5rem;
    }

    .admin-actions a {
      display: inline-block;
      padding: 4px 8px;
      background-color: #2196F3;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .admin-actions a.delete {
      background-color: #f44336;
    }

    /* Comment Section Styles */
    .comments-section {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #eee;
      width: 100%;
      display: none;
    }

    .event.expanded .comments-section {
      display: block;
    }

    .comments-section h3 {
      margin-bottom: 0.8rem;
      color: #2d3748;
      font-size: 1rem;
    }

    .comment-form {
      margin-bottom: 0.8rem;
    }

    .comment-form textarea {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      min-height: 60px;
      margin-bottom: 0.5rem;
      resize: vertical;
      font-size: 0.85rem;
    }

    .comment-form button {
      padding: 6px 12px;
      background-color: #FFA500;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
    }

    .comment-list {
      margin-top: 0.8rem;
      max-height: 200px;
      overflow-y: auto;
      padding-right: 5px;
    }

    .comment {
      padding: 0.6rem;
      margin-bottom: 0.6rem;
      background-color: #f9f9f9;
      border-radius: 4px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      position: relative;
      font-size: 0.85rem;
    }

    .comment-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.3rem;
      font-size: 0.75rem;
    }

    .comment-author {
      font-weight: 600;
      color: #2d3748;
    }

    .comment-date {
      color: #777;
    }

    .comment-content {
      line-height: 1.4;
    }

    .comment-actions {
      position: absolute;
      top: 0.3rem;
      right: 0.3rem;
      display: flex;
      gap: 0.3rem;
    }

    .comment-edit, .comment-delete {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 0.7rem;
      color: #666;
      padding: 2px;
    }

    .comment-edit:hover {
      color: #2196F3;
    }

    .comment-delete:hover {
      color: #f44336;
    }

    .edit-comment-form {
      display: none;
      margin-top: 0.5rem;
    }

    .edit-comment-form textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 0.3rem;
      font-size: 0.85rem;
    }

    .edit-comment-form button {
      padding: 4px 8px;
      font-size: 0.8rem;
      margin-right: 0.3rem;
    }

    .notification {
      max-width: 800px;
      margin: 1.5rem auto;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .notification.canceled {
      background-color: #fdecea;
      color: #b71c1c;
      border-left: 6px solid #f44336;
    }

    .notification.success {
      background-color: #e0f7e9;
      color: #256029;
      border-left: 6px solid #4caf50;
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 20px;
      border-radius: 8px;
      width: 80%;
      max-width: 400px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      margin-top: 1rem;
      gap: 0.5rem;
    }

    .modal-buttons button {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
    }

    .modal-confirm {
      background-color: #f44336;
      color: white;
    }

    .modal-cancel {
      background-color: #ccc;
    }

    /* Scrollbar styling */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
  </style>
</head>
<body>
  <header id="header">
    <nav>
      <h1><a href="homepage.php">UniVENT</a></h1>
      <div class="nav-links">
        <a href="homepage.php">Home</a>
        <a href="events.php">Events</a>
        <a href="calender.php">Calendar</a>
        <a href="profile.php">Profile</a>
      </div>
    </nav>
  </header>

  <?php if (isset($_GET['canceled'])): ?>
    <div class="notification canceled">
      <p>You have successfully canceled your registration.</p>
    </div>
  <?php elseif (isset($_GET['registered'])): ?>
    <div class="notification success">
      <p>Event registration successful!</p>
    </div>
  <?php endif; ?>

  <section class="event-section">
    <?php if (mysqli_num_rows($events) > 0): ?>
      <?php while ($event = mysqli_fetch_assoc($events)): ?>
        <div class="event" id="event-<?php echo $event['id']; ?>">
          <?php if (!empty($event['image_path'])): ?>
            <div class="event-image">
              <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event image">
            </div>
          <?php endif; ?>

          <div class="event-content">
            <div class="event-header">
              <div>
                <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                <span class="event-time">
                  <?php
                    $eventDate = date("M j, Y", strtotime($event['event_date']));
                    $startTime = date("g:i A", strtotime($event['start_time']));
                    $endTime = date("g:i A", strtotime($event['end_time']));
                    echo "$eventDate | $startTime - $endTime";
                  ?>
                </span>
                <?php if (!empty($event['venue'])): ?>
                  <p class="event-venue"><?php echo htmlspecialchars($event['venue']); ?></p>
                <?php endif; ?>
              </div>
              
              <div class="event-actions">
                <form class="register-form" method="POST">
                  <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                  <?php if (in_array($event['id'], $registered_events)): ?>
                    <button type="submit" class="unregister-btn">Unregister</button>
                  <?php else: ?>
                    <button type="submit" class="register-btn">Register</button>
                  <?php endif; ?>
                </form>
              </div>
            </div>

            <p class="event-description"><?php echo nl2br(htmlspecialchars($event['content'])); ?></p>
<button class="see-more-btn" onclick="toggleEventExpand(<?php echo $event['id']; ?>)">See More</button>

            <?php if ($isAdmin): ?>
              <div class="admin-actions">
                <a href="edit-event.php?id=<?php echo $event['id']; ?>">Edit</a>
                <a href="delete-event.php?id=<?php echo $event['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
              </div>
            <?php endif; ?>

            <!-- Comments Section -->
            <div class="comments-section">
              <h3>Comments</h3>
              
              <form class="comment-form" method="POST">
                <input type="hidden" name="comment_event_id" value="<?php echo $event['id']; ?>">
                <textarea name="comment_text" placeholder="Add your comment..." required></textarea>
                <button type="submit">Post Comment</button>
              </form>
              
              <div class="comment-list">
                <?php
                $event_id = $event['id'];
                $comments_query = mysqli_query($conn, "
                    SELECT c.*, u.firstname, u.lastname 
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.event_id = $event_id
                    ORDER BY c.created_at DESC
                ");
                
                if (mysqli_num_rows($comments_query) > 0): ?>
                  <?php while ($comment = mysqli_fetch_assoc($comments_query)): ?>
                    <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                      <div class="comment-header">
                        <span class="comment-author"><?php echo htmlspecialchars($comment['firstname']) . ' ' . htmlspecialchars($comment['lastname']); ?></span>
                        <span class="comment-date"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></span>
                      </div>
                      <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                      </div>
                      
                      <?php if ($comment['user_id'] == $user_id || $isAdmin): ?>
                        <div class="comment-actions">
                          <button class="comment-edit" onclick="showEditForm(<?php echo $comment['id']; ?>)">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="comment-delete" onclick="confirmDelete(<?php echo $comment['id']; ?>)">
                          <i class="fas fa-trash"></i>
                         </button>
                        </div>
                        
                        <form class="edit-comment-form" id="edit-form-<?php echo $comment['id']; ?>" method="POST">
                          <input type="hidden" name="edit_comment" value="<?php echo $comment['id']; ?>">
                          <textarea name="edited_comment_text"><?php echo htmlspecialchars($comment['comment']); ?></textarea>
                          <button type="submit">Save</button>
                          <button type="button" onclick="hideEditForm(<?php echo $comment['id']; ?>)">Cancel</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endwhile; ?>
                <?php else: ?>
                  <p>No comments yet. Be the first to comment!</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No upcoming events at the moment. Please check back later.</p>
    <?php endif; ?>
  </section>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <p>Are you sure you want to delete this comment?</p>
      <form id="deleteForm" method="POST">
        <input type="hidden" name="delete_comment" id="deleteCommentId">
        <div class="modal-buttons">
          <button type="button" class="modal-cancel" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
          <button type="submit" class="modal-confirm">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> UniVENT. All rights reserved.</p>
  </footer>

  <script>
    // Toggle event expansion
   function toggleEventExpand(eventId) {
  const eventElement = document.getElementById(`event-${eventId}`);
  const seeMoreBtn = eventElement.querySelector('.see-more-btn');
  
  eventElement.classList.toggle('expanded');
  seeMoreBtn.textContent = eventElement.classList.contains('expanded') ? 'See Less' : 'See More';
  
  // Scroll to the event if expanding
  if (eventElement.classList.contains('expanded')) {
    eventElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

    // Comment edit/delete functions
    function showEditForm(commentId) {
      const commentDiv = document.getElementById(`comment-${commentId}`);
      const contentDiv = commentDiv.querySelector('.comment-content');
      const editForm = commentDiv.querySelector('.edit-comment-form');
      
      contentDiv.style.display = 'none';
      editForm.style.display = 'block';
    }

    function hideEditForm(commentId) {
      const commentDiv = document.getElementById(`comment-${commentId}`);
      const contentDiv = commentDiv.querySelector('.comment-content');
      const editForm = commentDiv.querySelector('.edit-comment-form');
      
      contentDiv.style.display = 'block';
      editForm.style.display = 'none';
    }

    function confirmDelete(commentId) {
    if (confirm("Are you sure you want to delete this comment?")) {
        // Create a form dynamically
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'events.php'; // Submit to same page
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_comment';
        input.value = commentId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('deleteModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    // Handle form submission for registration
    document.addEventListener('DOMContentLoaded', function() {
      const registerForms = document.querySelectorAll('.register-form');
      registerForms.forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          
          fetch('events.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (response.redirected) {
              window.location.href = 'profile.php?registered=true';
            }
          })
          .catch(error => console.error('Error:', error));
        });
      });
    });
  </script>
</body>
</html>

<?php mysqli_close($conn); ?>