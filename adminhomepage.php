<?php
session_start();

// Strict session validation
if (!isset($_SESSION['user_email'])) {
    header("Location: http://localhost/254/login.php");
    exit();
}

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

if (!isset($_SESSION['user_email'])) {
    header("Location: homepage.html");
    exit();
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <title>Organizer Dashboard - UniVENT</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="adminstyle.css" />
</head>
<body>
    <header id="header">
        <nav>
            <h1><a href="organizer-homepage.php">UniVENT Organizer</a></h1>
            <ul class="nav-links">
               
                <li><a href="events.php">Events</a></li>
               
                <a href="/254/logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to log out?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Organizer Dashboard</h2>
        
        <div class="admin-actions">
            <a href="manage-events.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="action-title">Manage Events</div>
                <div class="action-desc">Create, edit or delete events</div>
            </a>

            <a href="view-registrations.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="action-title">View Registrants</div>
                <div class="action-desc">View and manage event attendees</div>
            </a>
        </div>
    </div>
</body>
</html>