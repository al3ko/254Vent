<?php
session_start();
$conn = mysqli_connect("localhost","root","QWE123!@#qwe","univent",3307);
if(!$conn) die("Connection failed: ".mysqli_connect_error());
$user_id = $_SESSION['user_id'] ?? 0;

$success_message = '';
$error_message = '';

// Handle registration/unregistration
if(isset($_POST['action'], $_POST['event_id'])){
    $event_id = (int)$_POST['event_id'];
    if($_POST['action']==='register'){
        $stmt = $conn->prepare("INSERT INTO event_registrations (event_id,user_id) VALUES (?,?)");
        $stmt->bind_param("ii",$event_id,$user_id);
        $success_message = $stmt->execute() ? "Successfully registered!" : "Error registering!";
        $stmt->close();
    } elseif($_POST['action']==='unregister'){
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id=? AND user_id=?");
        $stmt->bind_param("ii",$event_id,$user_id);
        $success_message = $stmt->execute() ? "Successfully unregistered!" : "Error unregistering!";
        $stmt->close();
    }
}

$events_query = "SELECT e.*, 
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as attendee_count,
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as is_registered
                 FROM events e ORDER BY e.start_date DESC";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$events_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UniVENT - Events</title>
<link rel="stylesheet" href="homestyle.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

<header>
    <nav>
        <div class="logo">UniVENT</div>
        <div class="nav-links">
            <a href="homepage.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
</header>

<?php if($success_message): ?>
    <div class="success-message" id="successMessage"><?php echo $success_message; ?></div>
<?php endif; ?>

<div class="animated-title">Upcoming Events</div>

<div class="events-container">
<?php if($events_result->num_rows>0): ?>
    <?php while($event=$events_result->fetch_assoc()): ?>
        <div class="event-card" onclick="showEventDetails(<?php echo htmlspecialchars(json_encode($event)); ?>)">
            <?php if(!empty($event['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            <?php endif; ?>
            <div class="event-details">
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <p class="event-info"><i class="fas fa-clock"></i> <?php echo date('g:i A',strtotime($event['start_date'])); ?></p>
                <p class="event-info"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></p>
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                    <?php if($event['is_registered']): ?>
                        <button type="submit" name="action" value="unregister" class="unregister-btn">Unregister</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="register" class="register-btn">Register</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center;padding:2rem;color:#fff;">No events available.</p>
<?php endif; ?>
</div>

<!-- Popup Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal()">×</button>
        <img id="modalImage" src="" alt="">
        <h2 id="modalTitle"></h2>
        <p id="modalDesc"></p>
        <p><strong>Venue:</strong> <span id="modalVenue"></span></p>
        <p><strong>Time:</strong> <span id="modalTime"></span></p>
    </div>
</div>

<script>
// Auto-hide success message after 3s
setTimeout(()=>{ const msg=document.getElementById('successMessage'); if(msg) msg.style.display='none'; },3000);

// Modal logic
function showEventDetails(event) {
    const modal = document.getElementById('eventModal');
    document.getElementById('modalTitle').innerText = event.title;
    document.getElementById('modalDesc').innerText = event.description;
    document.getElementById('modalVenue').innerText = event.venue;
    document.getElementById('modalTime').innerText = new Date(event.start_date).toLocaleString();
    document.getElementById('modalImage').src = event.image_path || '';
    modal.style.display = 'flex';
}
function closeModal(){ document.getElementById('eventModal').style.display='none'; }
window.onclick = function(e){ const m=document.getElementById('eventModal'); if(e.target==m){m.style.display='none';} }
</script>

</body>
</html>
<?php $conn->close(); ?>
