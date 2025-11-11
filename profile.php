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
$error = '';

// Get user details
$user_query = $conn->prepare("SELECT firstname, lastname, email, student_id, profile_pic, username FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

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

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/profile_pics/";
        $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $imageFileType;
        $target_path = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_path)) {
                // Update database
                $update_pic = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $update_pic->bind_param("si", $target_path, $user_id);
                $update_pic->execute();
                $user['profile_pic'] = $target_path; // Update local data
            }
        }
    }
    
    // Handle username update
    if (isset($_POST['username']) && $_POST['username'] !== $user['username']) {
        $new_username = trim($_POST['username']);
        if (!empty($new_username)) {
            // Check if username is already taken
            $check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_username->bind_param("si", $new_username, $user_id);
            $check_username->execute();
            
            if ($check_username->get_result()->num_rows === 0) {
                $update_username = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $update_username->bind_param("si", $new_username, $user_id);
                $update_username->execute();
                $user['username'] = $new_username; // Update local data
            } else {
                $error = "Username is already taken";
            }
        }
    }
}

// Get comments for all registered events
$comments = [];
if ($registered_events->num_rows > 0) {
    $registered_events->data_seek(0); // Reset pointer
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
    $registered_events->data_seek(0); // Reset pointer again
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <title>Profile - UniVENT</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <style>
        /* Navbar Styles (matches homepage.php exactly) */
        #header {
            background-color:rgb(16, 16, 113);
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin: 1rem auto;
            width: 95%;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        nav h1 {
            font-size: 1.8rem;
            color: #fff;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        nav h1 a {
            text-decoration: none;
            color: inherit;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 400;
            padding: 0.5rem 0;
            position: relative;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #ff5e5e;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        /* Rest of your profile page styles */
        body.profile-page {
            background-color: #f0f0f0;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #3a3838;
        }

        .profile-container {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
        }

        .profile-sidebar,
        .main-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            border: 3px solid #191970;
            position: relative;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile-info h2 {
            margin: 0.5rem 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .username {
            display: block;
            margin-bottom: 0.5rem;
            color: #7f8c8d;
            font-style: italic;
            font-size: 0.9rem;
        }

        .email, .student-id {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin: 0.3rem 0;
        }

        .user-type {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background-color: #191970;
            color: #fff;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .edit-section {
            margin-top: 1rem;
            display: none;
        }

        .edit-section.active {
            display: block;
        }

        .edit-toggle {
            background: none;
            border: none;
            color: #191970;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: underline;
            margin-top: 0.5rem;
        }

        .edit-toggle:hover {
            color: #ff5e5e;
        }

        .edit-form {
            margin-top: 0.5rem;
        }

        .edit-form input[type="text"],
        .edit-form input[type="file"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .edit-form button {
            padding: 0.5rem 1rem;
            background-color: #191970;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .edit-form button.cancel {
            background-color: #777;
        }

        .error-message {
            color: #e53e3e;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .my-clubs h3 {
            margin-bottom: 1rem;
            color: #191970;
            font-size: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .my-clubs ul {
            list-style: none;
            padding: 0;
        }

        .my-clubs li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: #3a3838;
            font-size: 0.85rem;
        }

        .main-content h2 {
            margin-bottom: 1rem;
            color: #191970;
            font-size: 1.2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .event-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.2rem;
            border: 1px solid #ddd;
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

        .cancel-btn {
            display: inline-block;
            background: #e53e3e;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            margin-top: 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.8rem;
        }

        .cancel-btn:hover {
            background: #c53030;
        }

        .no-events {
            text-align: center;
            padding: 2rem 1rem;
            font-size: 1rem;
            color: #555;
        }

        /* Comment Section Styles */
        .comments-section {
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px solid #eee;
        }

        .comments-section h4 {
            margin-bottom: 0.6rem;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .comment-form {
            margin-bottom: 0.6rem;
        }

        .comment-form textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 50px;
            margin-bottom: 0.4rem;
            resize: vertical;
            font-size: 0.8rem;
        }

        .comment-form button {
            padding: 0.4rem 0.8rem;
            background-color: #191970;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.3s;
        }

        .comment-form button:hover {
            background-color: #000080;
        }

        .comment-list {
            margin-top: 0.6rem;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .comment {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background-color: #f9f9f9;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-size: 0.8rem;
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
        }

        .comment-avatar {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            margin-right: 0.5rem;
            object-fit: cover;
        }

        .comment-author {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.8rem;
        }

        .comment-date {
            color: #777;
            font-size: 0.7rem;
            margin-left: auto;
        }

        .comment-content {
            line-height: 1.4;
            padding-left: 30px;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="profile-page">
    <header id="header">
        <nav>
            <h1><a href="homepage.php">UniVENT</a></h1>
            <div class="nav-links">
                <a href="homepage.php">Home</a>
                <a href="events.php">Events</a>
                <a href="profile.php" class="active">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="profile-container">
        <div class="profile-sidebar">
            <div class="profile-info">
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? '../img/profile_avatar.jpg'); ?>" alt="Profile Picture">
                </div>
                <h2><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h2>
                <?php if (!empty($user['username'])): ?>
                    <span class="username">@<?php echo htmlspecialchars($user['username']); ?></span>
                <?php endif; ?>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="student-id">Student ID: <?php echo htmlspecialchars($user['student_id']); ?></p>
                <div class="user-type">Club Member</div>
                
                <button class="edit-toggle" onclick="toggleEditSection()">Edit Profile</button>
                
                <div class="edit-section" id="editSection">
                    <form class="edit-form" method="post" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" accept="image/*">
                        <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        <?php if (!empty($error)): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <div style="margin-top: 0.5rem;">
                            <button type="submit">Save Changes</button>
                            <button type="button" class="cancel" onclick="toggleEditSection()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>

        <div class="main-content">
            <h2>My Registered Events</h2>
            
            <?php if ($registered_events->num_rows > 0): ?>
                <?php while ($event = $registered_events->fetch_assoc()): ?>
                    <div class="event-card">
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
                            
                            <form method="post" action="cancel_registration.php" onsubmit="return confirm('Are you sure you want to cancel your registration?');">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" class="cancel-btn">Cancel Registration</button>
                            </form>
                            
                            <!-- Comments Section -->
                            <div class="comments-section">
                                <h4>Comments</h4>
                                
                                <form class="comment-form" method="POST">
                                    <input type="hidden" name="comment_event_id" value="<?php echo $event['id']; ?>">
                                    <textarea name="comment_text" placeholder="Add your comment..." required></textarea>
                                    <button type="submit">Post Comment</button>
                                </form>
                                
                                <div class="comment-list">
                                    <?php if (isset($comments[$event['id']]) && !empty($comments[$event['id']])): ?>
                                        <?php foreach ($comments[$event['id']] as $comment): ?>
                                            <div class="comment">
                                                <div class="comment-header">
                                                    <img src="<?php echo htmlspecialchars($comment['profile_pic'] ?? '../img/profile_avatar.jpg'); ?>" alt="Profile" class="comment-avatar">
                                                    <span class="comment-author">
                                                        <?php echo !empty($comment['username']) ? '@' . htmlspecialchars($comment['username']) : htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname']); ?>
                                                    </span>
                                                    <span class="comment-date"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                                <div class="comment-content">
                                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No comments yet. Be the first to comment!</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-events">You are not registered for any events.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEditSection() {
            const editSection = document.getElementById('editSection');
            editSection.classList.toggle('active');
        }
    </script>
</body>
</html>

<?php
$user_query->close();
$events_stmt->close();
$conn->close();
?>
