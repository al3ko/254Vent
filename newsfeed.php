<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

// Connect to database
$conn = new mysqli("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle unregistration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister_event_id'])) {
    $event_id = intval($_POST['unregister_event_id']);
    $conn->query("DELETE FROM event_registrations WHERE user_id = $user_id AND event_id = $event_id");
    header("Location: newsfeed.php?canceled=true");
    exit();
}

// Get registered events
$events_sql = "
    SELECT p.id, p.title, p.content, p.image_path, p.event_date, p.start_time, p.end_time, p.venue
    FROM event_registrations er
    JOIN posts p ON er.event_id = p.id
    WHERE er.user_id = ?
    ORDER BY p.event_date ASC
";
$events_stmt = $conn->prepare($events_sql);
$events_stmt->bind_param("i", $user_id);
$events_stmt->execute();
$registered_events = $events_stmt->get_result();

// Get comments for all registered events
$comments = [];
if ($registered_events->num_rows > 0) {
    $registered_events->data_seek(0);
    while ($event = $registered_events->fetch_assoc()) {
        $event_id = $event['id'];
        $comments_query = $conn->query("
            SELECT c.*, u.firstname, u.lastname, u.profile_pic, u.username 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.event_id = $event_id
            ORDER BY c.created_at DESC
        ");
        $comments[$event_id] = $comments_query->fetch_all(MYSQLI_ASSOC);
    }
    $registered_events->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsfeed - Registered Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 1.5rem;
            color: #3a3838;
        }

        header {
            background-color: #191970;
            padding: 0.3rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            color: white;
        }

        header nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header nav a {
            color: #fff;
            margin-left: 1rem;
            text-decoration: none;
            font-weight: bold;
        }

        .notification {
            background-color: #fdecea;
            color: #b71c1c;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 6px solid #f44336;
            border-radius: 5px;
            max-width: 800px;
            margin: 1rem auto;
        }

        .event-card {
            width: 75%;
            margin: 0 auto 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: 1px solid #ddd;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .event-card.collapsed .event-expand {
            display: none;
        }

        .event-header {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }

        .event-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.8rem;
        }

        .event-image img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .event-details {
            padding: 0.8rem;
        }

        .event-details h3 {
            margin: 0 0 0.3rem;
            color: #191970;
            font-size: 1rem;
        }

        .event-details p {
            margin: 0.3rem 0;
            font-size: 0.85rem;
        }

        .event-meta {
            display: flex;
            gap: 0.8rem;
            margin: 0.5rem 0;
            font-size: 0.8rem;
            color: #555;
            flex-wrap: wrap;
        }

        .event-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .comment {
            margin-top: 1rem;
            padding: 0.5rem;
            background-color: #f9f9f9;
            border-left: 3px solid #191970;
            border-radius: 5px;
        }

        .comment strong {
            display: block;
            margin-bottom: 0.2rem;
            color: #2d3748;
        }

        .comment small {
            color: #777;
            font-size: 0.75rem;
        }

        .toggle-btn {
            background-color: #FFA500;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin: 0.5rem;
        }

        .unregister-form {
            text-align: right;
            padding: 0.5rem 1rem 1rem;
        }

        .unregister-form button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
    </style>
    <script>
        function toggleExpand(id) {
            const card = document.getElementById("event-" + id);
            card.classList.toggle("collapsed");
            const btn = card.querySelector(".toggle-btn");
            btn.textContent = card.classList.contains("collapsed") ? "See More" : "See Less";
        }
    </script>
</head>
<body>
    <header>
        <nav>
            <div><strong>UniVENT</strong></div>
            <div>
                <a href="homepage.php">Home</a>
                <a href="events.php">Events</a>
                <a href="calender.php">Calendar</a>
                <a href="profile.php">Profile</a>
            </div>
        </nav>
    </header>

    <?php if (isset($_GET['canceled'])): ?>
        <div class="notification">You have successfully canceled your registration.</div>
    <?php endif; ?>

    <h1 style="color:#191970; text-align:center">My Registered Events</h1>

    <?php if ($registered_events->num_rows > 0): ?>
        <?php while ($event = $registered_events->fetch_assoc()): ?>
            <div class="event-card collapsed" id="event-<?php echo $event['id']; ?>">
                <div class="event-header">
                    <img src="<?php echo htmlspecialchars($event['image_path'] ?? 'img/default.jpg'); ?>" alt="Event image">
                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                </div>

                <div class="event-image">
                    <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event">
                </div>

                <div class="event-details">
                    <div class="event-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($event['event_date']); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($event['start_time'] . ' - ' . $event['end_time']); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($event['content'])); ?></p>
                </div>

                <div class="event-expand">
                    <div class="event-details">
                        <h4 style="margin-bottom: 0.5rem; color: #2d3748;">Comments</h4>
                        <?php if (isset($comments[$event['id']]) && !empty($comments[$event['id']])): ?>
                            <?php foreach ($comments[$event['id']] as $comment): ?>
                                <div class="comment">
                                    <strong>
                                        <?php echo !empty($comment['username']) ? '@' . htmlspecialchars($comment['username']) : htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname']); ?>
                                    </strong>
                                    <small><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></small>
                                    <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="unregister-form">
                        <input type="hidden" name="unregister_event_id" value="<?php echo $event['id']; ?>">
                        <button type="submit">Unregister</button>
                    </form>
                </div>

                <button class="toggle-btn" onclick="toggleExpand(<?php echo $event['id']; ?>)">See More</button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center">You are not registered for any events.</p>
    <?php endif; ?>

</body>
</html>

<?php
$events_stmt->close();
$conn->close();
?>
