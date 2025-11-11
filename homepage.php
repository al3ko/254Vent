<?php
session_start();
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if(!$conn) die("Connection failed: ".mysqli_connect_error());
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch 6 events
$events_query = "SELECT e.*, 
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as attendee_count,
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as is_registered
                 FROM events e
                 ORDER BY e.start_date DESC
                 LIMIT 6";
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

<div class="animated-title">Upcoming Events</div>

<div class="events-container">
<?php if($events_result->num_rows>0): ?>
    <?php while($event=$events_result->fetch_assoc()): ?>
        <div class="event-card" onclick="openModal(<?php echo $event['event_id']; ?>)">
            <?php if(!empty($event['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            <?php else: ?>
                <div style="height:130px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;">
                    <i class="fas fa-calendar-alt" style="font-size:2rem;"></i>
                </div>
            <?php endif; ?>
            <div class="event-details">
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>

        <!-- Hidden full event for modal -->
        <div id="event-detail-<?php echo $event['event_id']; ?>" style="display:none;">
            <img src="<?php echo htmlspecialchars($event['image_path']); ?>" class="modal-image">
            <h2 class="modal-title"><?php echo htmlspecialchars($event['title']); ?></h2>
            <p class="modal-info"><i class="fas fa-calendar"></i> <?php echo date('F j, Y',strtotime($event['start_date'])); ?></p>
            <p class="modal-info"><i class="fas fa-clock"></i> <?php echo date('g:i A',strtotime($event['start_date'])).' - '.date('g:i A',strtotime($event['end_date'])); ?></p>
            <p class="modal-info"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></p>
            <p class="modal-info"><i class="fas fa-users"></i> <?php echo $event['attendee_count']; ?> attending</p>
            <p class="modal-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            <?php if($event['is_registered']): ?>
                <button class="unregister-btn">Unregister</button>
            <?php else: ?>
                <button class="register-btn">Register</button>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center;padding:2rem;color:#fff;">No events available.</p>
<?php endif; ?>
</div>

<div id="eventModal" class="modal">
    <div class="modal-content" id="modalContent">
        <button class="close-modal" onclick="closeModal()">×</button>
    </div>
</div>

<script>
function openModal(eventId){
    const eventDetail = document.getElementById('event-detail-'+eventId);
    if(eventDetail){
        document.getElementById('modalContent').innerHTML = '<button class="close-modal" onclick="closeModal()">×</button>' + eventDetail.innerHTML;
        document.getElementById('eventModal').style.display='flex';
        document.body.style.overflow='hidden';
    }
}
function closeModal(){
    document.getElementById('eventModal').style.display='none';
    document.body.style.overflow='auto';
}
</script>

</body>
</html>
<?php $conn->close(); ?>
