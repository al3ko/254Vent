<?php
session_start();
include('db_connect.php');

// Restrict access to admins
if (!isset($_SESSION['user_email']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Fetch events with registration count
$sql = "
    SELECT e.event_id, e.title, e.start_date, e.venue,
           COUNT(er.registration_id) AS registrant_count
    FROM events e
    LEFT JOIN event_registrations er ON e.event_id = er.event_id
    GROUP BY e.event_id
    ORDER BY e.start_date DESC
";

$result = $conn->query($sql);
if (!$result) {
    die("Error fetching events: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - View Registrations</title>
    <style>
        /* your styles */
    </style>
</head>
<body>
    <h2>Event Registrations Overview</h2>
    <table>
        <tr>
            <th>Event Title</th>
            <th>Start Date</th>
            <th>Venue</th>
            <th>Total Registrants</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['start_date']) ?></td>
            <td><?= htmlspecialchars($row['venue']) ?></td>
            <td><?= $row['registrant_count'] ?></td>
            <td>
                <a href="view-registrants.php?event_id=<?= $row['event_id'] ?>" class="view-btn">
                    View Registrants
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
