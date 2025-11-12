<?php
session_start();
include('db_connect.php');

// Use a default user_id for testing if session not set
$user_id = $_SESSION['user_id'] ?? 1; // Replace 1 with a default test user id

// Fetch user info
$user_query = $conn->prepare("SELECT id, email, created_at FROM users WHERE id = ?");
if (!$user_query) {
    die("Prepare failed: " . $conn->error);
}
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();
$user_query->close();

// Fetch registered events
$events_stmt = $conn->prepare("
    SELECT e.*, er.registered_at 
    FROM events e 
    INNER JOIN event_registrations er ON e.event_id = er.event_id 
    WHERE er.user_id = ? 
    ORDER BY e.start_date ASC
");
$events_stmt->bind_param("i", $user_id);
$events_stmt->execute();
$registered_events = $events_stmt->get_result();
$events_stmt->close();

// Fetch event stats
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_events,
        COUNT(CASE WHEN e.start_date > NOW() THEN 1 END) as upcoming_events,
        COUNT(CASE WHEN e.start_date <= NOW() THEN 1 END) as past_events
    FROM event_registrations er 
    INNER JOIN events e ON er.event_id = e.event_id 
    WHERE er.user_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - UniVENT</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background: url('https://i.pinimg.com/1200x/a3/ea/e1/a3eae107f7c8342eb4ac81a9111e9f16.jpg') no-repeat center center fixed; background-size: cover; ; color: #333; line-height:1.6; padding:1rem; }
/* Header */
header { background:#90ee90; padding:0.4rem 1.5rem; border-radius:40px; margin:1rem auto; width:90%; box-shadow:0 4px 12px rgba(0,0,0,0.2);}
nav { display:flex; justify-content:space-between; align-items:center;}
nav .logo { font-size:1.8rem; font-weight:bold; color:#000;}
nav .nav-links { display:flex; gap:1rem; align-items:center;}
nav .nav-links a { color:#000; text-decoration:none; font-weight:600; padding:0.3rem 0.8rem; border-radius:12px; transition:all 0.3s;}
nav .nav-links a:hover { background:rgba(255,255,255,0.3);}
.logout-btn { background:#e74c3c; color:white; padding:0.4rem 1rem; border:none; border-radius:10px; cursor:pointer; font-weight:500; transition:0.3s;}
.logout-btn:hover { background:#c0392b;}
.container { max-width:1100px; margin:2rem auto; padding:0 1rem;}
.profile-header { background:#fff8e7; border-radius:14px; padding:1.8rem; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-bottom:2rem; border:2px solid #90ee90;}
.profile-info { display:grid; grid-template-columns:auto 1fr; gap:1.5rem; align-items:center;}
.profile-avatar { width:90px; height:90px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.3rem; color:#2d5016; background:#90ee90;}
.profile-details h1 { color:#2d5016; margin-bottom:0.4rem;}
.profile-details p { color:#444; margin-bottom:0.3rem; font-size:0.95rem;}
.stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:1.2rem;}
.stat-card { background:#f8f9fa; padding:1.3rem; border-radius:10px; text-align:center; border-left:5px solid #90ee90;}
.stat-number { font-size:1.7rem; font-weight:bold; color:#2d5016; display:block;}
.stat-label { color:#666; font-size:0.85rem;}
.events-section { background:#fff8e7; border-radius:14px; padding:1.8rem; box-shadow:0 4px 15px rgba(0,0,0,0.1); border:2px solid #90ee90;}
.section-title { color:#2d5016; margin-bottom:1.5rem; font-size:1.4rem;}
.events-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.3rem;}
.event-card { background:#fff; border-radius:10px; padding:1.2rem; border:2px solid #e9ecef; transition:all 0.3s;}
.event-card:hover { border-color:#90ee90; transform:scale(1.02); box-shadow:0 5px 15px rgba(0,0,0,0.15);}
.event-card.upcoming { border-left:5px solid #32cd32;}
.event-card.past { border-left:5px solid #95a5a6; opacity:0.9;}
.event-title { font-size:1.1rem; font-weight:600; color:#2d5016; margin-bottom:0.4rem;}
.event-details { color:#555; margin-bottom:0.8rem; font-size:0.9rem;}
.event-details div { margin-bottom:0.4rem; display:flex; align-items:center; gap:0.5rem;}
.event-details i { color:#90ee90;}
.unregister-btn { background:#e74c3c; color:white; border:none; padding:0.4rem 0.8rem; border-radius:6px; cursor:pointer; font-size:0.85rem; transition:0.3s;}
.unregister-btn:hover { background:#c0392b;}
.registered-date { color:#999; font-size:0.85rem;}
.no-events { text-align:center; padding:2rem; color:#555;}
.no-events i { font-size:3rem; color:#bdc3c7; margin-bottom:1rem;}
@media(max-width:768px){.profile-info{grid-template-columns:1fr;text-align:center;}.stats-grid{grid-template-columns:1fr;}.events-grid{grid-template-columns:1fr;}header{width:95%;padding:0.5rem 1rem;}}
</style>
</head>
<body>
<header>
<nav>
<div class="logo">UniVENT</div>
<div class="nav-links">
<a href="homepage.php">Events</a>
<a href="profile.php" class="active">My Profile</a>
<a href="logout.php" class="logout-btn">Logout</a>
</div>
</nav>
</header>

<div class="container">
<div class="profile-header">
<div class="profile-info">
<div class="profile-avatar">
<i class="fas fa-user"></i>
</div>
<div class="profile-details">
<h1>My Profile</h1>
<p><strong>Email:</strong> <?= htmlspecialchars($user_data['email'] ?? 'N/A') ?></p>
<p><strong>User ID:</strong> <?= htmlspecialchars($user_data['user_id'] ?? 'N/A') ?></p>
<p><strong>Member since:</strong> <?= date('F Y', strtotime($user_data['created_at'] ?? 'now')) ?></p>
</div>
</div>

<div class="stats-grid">
<div class="stat-card">
<span class="stat-number"><?= $stats['total_events'] ?? 0 ?></span>
<span class="stat-label">Total Events</span>
</div>
<div class="stat-card">
<span class="stat-number"><?= $stats['upcoming_events'] ?? 0 ?></span>
<span class="stat-label">Upcoming</span>
</div>
<div class="stat-card">
<span class="stat-number"><?= $stats['past_events'] ?? 0 ?></span>
<span class="stat-label">Past Events</span>
</div>
</div>
</div>

<div class="events-section">
<h2 class="section-title">My Registered Events</h2>
<?php if($registered_events->num_rows > 0): ?>
<div class="events-grid">
<?php while($event = $registered_events->fetch_assoc()):
$is_upcoming = strtotime($event['start_date']) > time();
$event_class = $is_upcoming ? 'upcoming':'past';
?>
<div class="event-card <?= $event_class ?>">
<h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
<div class="event-details">
<div><i class="fas fa-calendar"></i><span><?= date('M j, Y', strtotime($event['start_date'])) ?></span></div>
<div><i class="fas fa-clock"></i><span><?= date('g:i A', strtotime($event['start_date'])) ?> - <?= date('g:i A', strtotime($event['end_date'])) ?></span></div>
<div><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($event['venue']) ?></span></div>
</div>
<?php if(!empty($event['description'])): ?>
<p style="color:#666;font-size:0.9rem;"><?= substr(htmlspecialchars($event['description']),0,100) ?>...</p>
<?php endif; ?>
<div class="event-footer">
<div class="registered-date">Registered: <?= date('M j, Y', strtotime($event['registered_at'])) ?></div>
<?php if($is_upcoming): ?>
<form method="POST" action="homepage.php" style="display:inline;">
<input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
<button type="submit" name="action" value="unregister" class="unregister-btn">Unregister</button>
</form>
<?php else: ?>
<span style="color:#999;font-size:0.9rem;">Event ended</span>
<?php endif; ?>
</div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="no-events">
<i class="fas fa-calendar-times"></i>
<h3>No Registered Events</h3>
<p>You haven't registered for any events yet.</p>
<p><a href="homepage.php" style="color:#32cd32;text-decoration:none;">Browse events →</a></p>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>

<?php
$conn->close();
?>
