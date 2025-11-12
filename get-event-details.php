<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch events for the page (limit 6 per page)
$events_query = "SELECT e.*, 
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as attendee_count,
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = ?) as is_registered
                 FROM events e
                 ORDER BY e.start_date DESC
                 LIMIT 6";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniVENT - Events</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="event.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

<h1 style="text-align:center; margin-top:1rem; color:#2d5016;">Upcoming Events</h1>

<div class="events-container">
<?php if($events_result->num_rows > 0): ?>
    <?php while($event = $events_result->fetch_assoc()): ?>
        <div class="event-card" onclick="expandCard(this)">
            <?php if(!empty($event['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            <?php else: ?>
                <div style="height:240px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999;">
                    <i class="fas fa-calendar-alt" style="font-size:3rem;"></i>
                </div>
            <?php endif; ?>

            <div class="event-details">
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <div class="event-footer">
                    <span><i class="fas fa-users"></i> <?php echo $event['attendee_count']; ?></span>
                    <?php if($event['is_registered']): ?>
                        <button class="unregister-btn">Unregister</button>
                    <?php else: ?>
                        <button class="register-btn">Register</button>
                    <?php endif; ?>
                </div>
            </div>

            <button class="close-card" onclick="collapseCard(event,this.parentElement)">×</button>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; padding:2rem; color:#666;">No events available. Check back later!</p>
<?php endif; ?>
</div>

<script>
function expandCard(card) {
    card.classList.add('expanded');
}
function collapseCard(e, card) {
    e.stopPropagation();
    card.classList.remove('expanded');
}
</script>

</body>
</html>

<?php $conn->close(); ?>
