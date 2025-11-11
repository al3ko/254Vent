<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}
$user_id = $_SESSION["user_id"];

// Database connection
$conn = new mysqli("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all registered events for the user
$sql = "
    SELECT p.title, p.venue, p.event_date, p.start_time, p.end_time
    FROM event_registrations er
    JOIN posts p ON er.event_id = p.id
    WHERE er.user_id = ?
    ORDER BY p.event_date, p.start_time
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$registeredEvents = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['event_date'];
    $registeredEvents[$date][] = [
        'title' => $row['title'],
        'venue' => $row['venue'],
        'start_time' => date('h:i A', strtotime($row['start_time'])),
        'end_time' => date('h:i A', strtotime($row['end_time']))
    ];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UniVENT – Calendar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: #e6decf;
      color: #333;
    }

    #header {
      background: #191970;
      padding: 0.25rem 1.2rem;
      border-radius: 20px;
      margin: 1rem auto;
      max-width: 1200px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    #header h1 a {
      color: #fff;
      font-size: 1.6rem;
      text-decoration: none;
      font-weight: bold;
    }

    .nav-links {
      display: flex;
      gap: 0.8rem;
    }

    .nav-links a {
      color: #f0f0f0;
      text-decoration: none;
      font-weight: 500;
      padding: 0.3rem 0.6rem;
      border-radius: 5px;
      font-size: 0.9rem;
      transition: background-color 0.3s;
    }

    .nav-links a:hover {
      background: #4682b4;
      color: #fff;
    }

    .calendar-container {
      max-width: 900px;
      margin: 2rem auto;
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .calendar-header h1 {
      font-size: 1.8rem;
    }

    .calendar-header button {
      background: #FFA500;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .calendar-header button:hover {
      background: #e69500;
    }

    .weekdays,
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 0.5rem;
    }

    .weekdays div {
      text-align: center;
      padding: 0.5rem;
      font-weight: bold;
      background: #f4f4f4;
      border-radius: 5px;
    }

    .calendar-grid div {
      text-align: center;
      padding: 0.5rem;
      background: #fffaf0;
      border: 1px solid #e0dcd0;
      border-radius: 5px;
      position: relative;
      cursor: pointer;
      transition: all 0.2s;
      height: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .calendar-grid div:hover {
      transform: scale(1.05);
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .calendar-grid div.highlight {
      background: #87ceeb;
      color: #fff;
      font-weight: bold;
    }

    .calendar-grid div.highlight::after {
      content: '';
      position: absolute;
      bottom: 5px;
      left: 50%;
      transform: translateX(-50%);
      width: 6px;
      height: 6px;
      background: #191970;
      border-radius: 50%;
    }

    .calendar-grid div.multi-event::after {
      width: 12px;
      border-radius: 3px;
    }

    .event-details {
      margin-top: 2rem;
      padding: 1.5rem;
      background: #f8f9fa;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .event-details h2 {
      color: #191970;
      margin-bottom: 1rem;
      border-bottom: 2px solid #FFA500;
      padding-bottom: 0.5rem;
    }

    .event-date-header {
      color: #191970;
      font-size: 1.2rem;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #ddd;
    }

    .event-item {
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: white;
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .event-item h3 {
      color: #4682b4;
      margin-bottom: 0.5rem;
    }

    .event-item p {
      margin: 0.3rem 0;
      color: #555;
    }

    .event-time {
      font-weight: bold;
      color: #FFA500;
    }

    .no-events {
      color: #666;
      font-style: italic;
      text-align: center;
      padding: 1rem;
    }
  </style>
</head>
<body>

  <header id="header">
    <h1><a href="homepage.php">UniVENT</a></h1>
    <nav class="nav-links">
      <a href="homepage.php">Home</a>
      <a href="events.php">Events</a>
      <a href="calender.php">Calendar</a>
      <a href="profile.php">Profile</a>
    </nav>
  </header>

  <div class="calendar-container">
    <div class="calendar-header">
      <button id="prev-month">&lt;</button>
      <h1 id="current-month">Loading...</h1>
      <button id="next-month">&gt;</button>
    </div>

    <div class="weekdays">
      <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>
    <div class="calendar-grid" id="calendar-grid"></div>
    
    <div class="event-details" id="event-details">
      <h2>Event Details</h2>
      <div id="events-container">
        <p class="no-events">Click on a highlighted date to view events</p>
      </div>
    </div>
  </div>

  <script>
    const registeredEvents = <?= json_encode($registeredEvents) ?>;
    const calendarGrid = document.getElementById("calendar-grid");
    const monthLabel = document.getElementById("current-month");
    const prevBtn = document.getElementById("prev-month");
    const nextBtn = document.getElementById("next-month");
    const eventsContainer = document.getElementById("events-container");

    let dateObj = new Date();

    function renderCalendar() {
      const Y = dateObj.getFullYear(), M = dateObj.getMonth();
      const firstDay = new Date(Y, M, 1).getDay();
      const daysInM = new Date(Y, M + 1, 0).getDate();

      monthLabel.textContent = `${dateObj.toLocaleString('default',{month:'long'})} ${Y}`;
      calendarGrid.innerHTML = '';

      // Empty cells for days before the first of the month
      for (let i = 0; i < firstDay; i++) {
        calendarGrid.appendChild(document.createElement('div'));
      }

      // Cells for each day of the month
      for (let d = 1; d <= daysInM; d++) {
        const dateStr = `${Y}-${String(M+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cell = document.createElement('div');
        cell.textContent = d;
        cell.dataset.date = dateStr;
        
        if (registeredEvents[dateStr]) {
          cell.classList.add('highlight');
          if (registeredEvents[dateStr].length > 1) {
            cell.classList.add('multi-event');
          }
          cell.addEventListener('click', () => showEvents(dateStr));
        }
        
        calendarGrid.appendChild(cell);
      }
    }

    function showEvents(dateStr) {
      const events = registeredEvents[dateStr];
      if (!events || events.length === 0) {
        eventsContainer.innerHTML = '<p class="no-events">No events for this date</p>';
        return;
      }
      
      let html = `
        <div class="event-date-header">
          ${new Date(dateStr).toLocaleDateString('en-US', { 
            weekday: 'long', 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric' 
          })}
        </div>
      `;
      
      events.forEach(event => {
        html += `
          <div class="event-item">
            <h3>${event.title}</h3>
            <p><strong>Venue:</strong> ${event.venue}</p>
            <p class="event-time"><strong>Time:</strong> ${event.start_time} - ${event.end_time}</p>
          </div>
        `;
      });
      
      eventsContainer.innerHTML = html;
    }

    // Event listeners for month navigation
    prevBtn.addEventListener('click', () => {
      dateObj.setMonth(dateObj.getMonth() - 1);
      renderCalendar();
      eventsContainer.innerHTML = '<p class="no-events">Click on a highlighted date to view events</p>';
    });
    
    nextBtn.addEventListener('click', () => {
      dateObj.setMonth(dateObj.getMonth() + 1);
      renderCalendar();
      eventsContainer.innerHTML = '<p class="no-events">Click on a highlighted date to view events</p>';
    });

    // Initial render
    renderCalendar();
  </script>
</body>
</html>
