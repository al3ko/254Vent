<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch events (limit 6 per page)
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UniVENT - Events</title>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background:#f0f0f0; color:#333; line-height:1.6; }

    h1 { text-align:center; margin:1.5rem 0; color:#2d5016; }

    /* Grid container */
    .events-container { 
        display:grid; 
        grid-template-columns:repeat(3,1fr); 
        grid-template-rows:repeat(2,auto); 
        gap:2rem; 
        max-width:1200px; 
        margin:0 auto 2rem auto; 
        padding:0 1rem; 
    }

    /* Event Card */
    .event-card {
        background:#fff8e1; /* Creamy background */
        border-radius:0; /* Sharp edges */
        overflow:hidden;
        cursor:pointer;
        transition:all 0.5s ease;
        display:flex;
        flex-direction:column;
        height:400px;
        position:relative;
    }
    .event-card:hover { transform:translateY(-5px); box-shadow:0 10px 25px rgba(0,0,0,0.2); }

    .event-card img { width:100%; height:60%; object-fit:cover; }

    .event-details { padding:1rem; display:flex; flex-direction:column; flex:1; overflow:hidden; }
    .event-title { font-size:1.4rem; font-weight:700; color:#2d5016; margin-bottom:0.5rem; }
    .event-description { font-size:0.95rem; color:#555; margin-bottom:0.5rem; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; transition:all 0.3s ease; }

    .event-footer { display:flex; justify-content:space-between; align-items:center; }
    .register-btn, .unregister-btn { padding:0.5rem 1rem; border:none; border-radius:4px; font-weight:600; cursor:pointer; transition:all 0.3s ease; }
    .register-btn { background:#28a745; color:#fff; }
    .register-btn:hover { background:#1c7430; }
    .unregister-btn { background:#e74c3c; color:#fff; }
    .unregister-btn:hover { background:#c0392b; }

    /* Expanded Card */
    .event-card.expanded {
        position:fixed;
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
        width:80%;
        height:auto;
        max-height:90vh;
        background:#fff8e1;
        z-index:1000;
        overflow-y:auto;
        box-shadow:0 15px 40px rgba(0,0,0,0.4);
        border-radius:8px;
        padding-bottom:1rem;
    }
    .event-card.expanded .event-description { display:block; -webkit-line-clamp:unset; overflow:visible; margin-top:0.5rem; }

    /* Close Button */
    .close-card {
        position:absolute;
        top:10px;
        right:10px;
        background:#e74c3c;
        color:#fff;
        border:none;
        width:35px;
        height:35px;
        border-radius:50%;
        font-size:1.2rem;
        cursor:pointer;
        z-index:1001;
    }

    /* Responsive */
    @media(max-width:1024px) { .events-container { grid-template-columns:repeat(2,1fr); grid-template-rows:repeat(3,auto); } }
    @media(max-width:768px) { .events-container { grid-template-columns:1fr; grid-template-rows:auto; } }

</style>
</head>
<body>

<h1>Upcoming Events</h1>

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
function expandCard(card) { card.classList.add('expanded'); }
function collapseCard(e, card) { e.stopPropagation(); card.classList.remove('expanded'); }
</script>

</body>
</html>

<?php $conn->close(); ?>
