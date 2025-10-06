<?php
session_start();

// Strict session validation
if (!isset($_SESSION['user_email'])) {
    header("Location: http://localhost/UniVent-Project/login.html");
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f8ff;
            color: #333;
            line-height: 1.6;
        }

        /* Main Navbar */
        #header {
            background-color: #4682b4;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
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
            color: white;
            font-weight: 600;
        }

        nav h1 a {
            text-decoration: none;
            color: inherit;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #ffd700;
        }

        .nav-links a.active {
            color: #ffd700;
            font-weight: 600;
        }

        .logout-btn {
            background-color: #e53e3e;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-1px);
        }

        .admin-btn {
            background-color: #32cd32;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-btn:hover {
            background-color: #228b22;
            transform: translateY(-1px);
        }

        /* Main Content */
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #4682b4;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 0.5rem;
        }

        .admin-email {
            color: #4a5568;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            background-color: #f0f8ff;
            border-radius: 4px;
            display: inline-block;
            border-left: 4px solid #4682b4;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4682b4;
        }

        .action-icon {
            font-size: 1.5rem;
            color: #4682b4;
            margin-bottom: 0.5rem;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .action-desc {
            font-size: 0.9rem;
            color: #718096;
        }

        @media screen and (max-width: 768px) {
            .admin-actions {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem 0;
            }
            
            nav {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header id="header">
        <nav>
            <h1><a href="organizer-homepage.php">UniVENT Organizer</a></h1>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <?php if ($_SESSION['is_admin']): ?>
                    <li><a href="admin-homepage.php" class="admin-btn"><i class="fas fa-user-shield"></i> Admin Dashboard</a></li>
                <?php endif; ?>
                <a href="/UniVent-Project/auth/logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to log out?');">
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