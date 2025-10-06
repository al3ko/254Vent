<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

// Database connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get upcoming events (limit to 6 for homepage)
$events = mysqli_query($conn, "SELECT id, title, image_path FROM posts ORDER BY event_date ASC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>UniVENT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Righteous', cursive;
        }
body {
    /* Modern layered background with fallbacks */
    background: 
        linear-gradient(rgba(255, 227, 170, 0.8), rgba(255, 227, 170, 0.8)), /* Cream overlay */
        url('https://images.unsplash.com/photo-1464983953574-0892a716854b?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed;
    background-size: cover;
    background-attachment: fixed;
    background-blend-mode: overlay;
    color: #000;
    line-height: 1.6;
    overflow-x: hidden;
    min-height: 100vh;
}

        /* Navbar - Midnight Blue */
        #header {
            background-color: #191970;
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)),
                        url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 5rem 1rem;
            margin-bottom: 3rem;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .welcome-message {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            min-height: 60px;
            font-weight: 300;
        }

        .typing-text {
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            border-right: 2px solid white;
            animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: white; }
        }

        /* Events Section */
        .events-section {
            padding: 2rem;
            background-color: #fff;
            border-radius: 15px;
            width: 95%;
            margin: 2rem auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .events-section h2 {
            text-align: center;
            margin-bottom: 3rem;
            color: #2d3748;
            font-size: 2rem;
        }

        .events-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            background-color: transparent; /* ONLY CHANGE MADE */
        }

        .events-scroller {
            display: flex;
            gap: 2rem;
            transition: transform 1s ease;
            padding-bottom: 1rem;
        }

        .event-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            min-width: 300px;
            flex: 0 0 auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .event-title {
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* About Us Section */
        .about-section {
            padding: 3rem 2rem;
            background-color: #fff;
            border-radius: 15px;
            width: 95%;
            margin: 2rem auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .about-section h2 {
            color: #191970;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            background-color: #2d3748;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .welcome-message {
                font-size: 1.2rem;
            }

            .events-scroller {
                gap: 1rem;
            }

            .event-card {
                min-width: 250px;
            }
        }
    </style>
</head>
<body>
    <header id="header">
        <nav>
            <h1><a href="homepage.php">UniVENT</a></h1>
            <div class="nav-links">
            
                <a href="newsfeed.php">Newsfeed</a>
                <a href="events.php">Events</a>
                <a href="calender.php">Calendar</a>
                <a href="profile.php">Profile</a>
            </div>
        </nav>
    </header>

    <div class="homepage">
        <header class="hero">
            <div class="hero-content">
                <h1>Welcome to UniVENT</h1>
                <div class="welcome-message" id="welcomeMessage">
                    <div class="typing-text">Discover campus events that inspire</div>
                </div>
            </div>
        </header>

        <section class="about-section">
            <h2>About UniVENT</h2>
            <div class="about-content">
                <p>UniVENT is your premier platform for discovering and connecting with exciting campus events. 
                We bring together students and organizations to create memorable university experiences. 
                From academic workshops to social gatherings, find everything happening on campus in one place.</p>
            </div>
        </section>

        <section class="events-section">
            <h2>Upcoming Events</h2>
            <div class="events-container">
                <div class="events-scroller" id="eventsScroller">
                    <?php if (mysqli_num_rows($events) > 0): ?>
                        <?php while ($event = mysqli_fetch_assoc($events)): ?>
                            <a href="events.php#event-<?php echo $event['id']; ?>" class="event-card">
                                <?php if (!empty($event['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Event placeholder" class="event-image">
                                <?php endif; ?>
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="event-card">
                            <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="No events" class="event-image">
                            <h3 class="event-title">No upcoming events</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <footer class="homepage-footer">
            <p>&copy; <?php echo date("Y"); ?> UniVENT. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Welcome message animation
        document.addEventListener('DOMContentLoaded', function() {
            const messages = [
                "Discover campus events that inspire",
                "Connect with your university community",
                "Find your next campus adventure"
            ];
            let currentMessage = 0;
            
            function showNextMessage() {
                document.getElementById('welcomeMessage').innerHTML = 
                    `<div class="typing-text">${messages[currentMessage]}</div>`;
                currentMessage = (currentMessage + 1) % messages.length;
                setTimeout(showNextMessage, 3000);
            }
            showNextMessage();

            // Events carousel animation
            const scroller = document.getElementById('eventsScroller');
            const cards = document.querySelectorAll('.event-card');
            let currentIndex = 0;
            
            function scrollEvents() {
                currentIndex = (currentIndex + 1) % cards.length;
                const scrollPosition = -currentIndex * (cards[0].offsetWidth + 32);
                scroller.style.transform = `translateX(${scrollPosition}px)`;
                setTimeout(scrollEvents, 3000);
            }
            
            if (cards.length > 1) {
                setTimeout(scrollEvents, 3000);
            }
        });
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>