<?php
session_start();
include('db_connect.php');

// Admin access check
if (!isset($_SESSION['user_email']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Check event_id
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Missing or invalid event ID.");
}
$event_id = intval($_GET['event_id']);

// Fetch event title
$event_stmt = $conn->prepare("SELECT title FROM events WHERE event_id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_result->num_rows === 0) {
    die("Event not found.");
}
$event = $event_result->fetch_assoc();
$event_stmt->close();

// Fetch registrants
$registrants_stmt = $conn->prepare("
    SELECT user_id
    FROM event_registrations
    WHERE event_id = ?
    ORDER BY registered_at DESC
");
$registrants_stmt->bind_param("i", $event_id);
$registrants_stmt->execute();
$registrants_result = $registrants_stmt->get_result();
$registrant_count = $registrants_result->num_rows;

// Handle PDF download
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    // CORRECTED TCPDF PATH - since TCPDF files are in root directory
    $tcpdf_path = __DIR__ . '/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        die("TCPDF library not found. Make sure tcpdf.php exists in the root directory.");
    }
    require_once($tcpdf_path);

    // Also include necessary TCPDF files
    require_once(__DIR__ . '/tcpdf_barcodes_1d.php');
    require_once(__DIR__ . '/tcpdf_barcodes_2d.php');

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('UniVENT System');
    $pdf->SetTitle("Registrations - ".$event['title']);
    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, "Registrations for: ".$event['title'], 0, 1, 'C');
    $pdf->Ln(5);

    // Registration count
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, "Total Registrations: " . $registrant_count, 0, 1, 'L');
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'No.', 1, 0, 'C');
    $pdf->Cell(40, 10, 'User ID', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Present', 1, 1, 'C');

    // Table rows
    $pdf->SetFont('helvetica', '', 12);
    
    // Reset pointer and use a counter
    $registrants_result->data_seek(0);
    $counter = 1;
    
    while ($row = $registrants_result->fetch_assoc()) {
        $pdf->Cell(40, 10, $counter, 1, 0, 'C');
        $pdf->Cell(40, 10, $row['user_id'], 1, 0, 'C');
        $pdf->Cell(40, 10, '[ ]', 1, 1, 'C'); // checkbox placeholder
        $counter++;
    }

    // Add generated date
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'L');

    $pdf->Output('Registrations-'.$event['title'].'.pdf', 'D');
    exit();
}

// Store registrants data for HTML display (since we can't use the result twice)
$registrants_data = [];
if ($registrant_count > 0) {
    $registrants_result->data_seek(0); // Reset pointer
    while ($row = $registrants_result->fetch_assoc()) {
        $registrants_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registrations - <?= htmlspecialchars($event['title']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root {
    --primary: #3498db;
    --primary-dark: #2980b9;
    --orange: #FFA500;
    --bg: #f4f4f4;
    --text-dark: #333;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--bg);
    padding: 2rem;
    color: var(--text-dark);
}
h1 {
    text-align: center;
    margin-bottom: 1.5rem;
}
.table-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background-color: var(--orange);
    color: white;
    font-weight: 600;
}
tr:hover {
    background-color: #f9f9f9;
}
.actions {
    text-align: center;
    margin-bottom: 1rem;
}
a.btn {
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    color: white;
    margin: 0 5px;
    display: inline-block;
    transition: background 0.3s;
}
a.btn-primary { background-color: var(--primary); }
a.btn-primary:hover { background-color: var(--primary-dark); }
a.btn-pdf { background-color: var(--orange); }
a.btn-pdf:hover { background-color: #e69500; }
.no-results {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    color: #777;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.registrant-count {
    text-align: center;
    margin: 1rem 0;
    font-weight: bold;
    color: var(--primary-dark);
}
</style>
</head>
<body>

<div class="actions">
    <a href="view-registrants.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Events</a>
    <?php if($registrant_count > 0): ?>
        <a href="?event_id=<?= $event_id ?>&download=pdf" class="btn btn-pdf"><i class="fas fa-file-pdf"></i> Download PDF</a>
    <?php endif; ?>
</div>

<h1>Registrations for "<?= htmlspecialchars($event['title']) ?>"</h1>

<?php if ($registrant_count > 0): ?>
    <div class="registrant-count">Total Registrations: <?= $registrant_count ?></div>
<?php endif; ?>

<div class="table-container">
<?php if ($registrant_count > 0): ?>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>User ID</th>
            <th>Present</th>
        </tr>
    </thead>
    <tbody>
        <?php $counter = 1; ?>
        <?php foreach ($registrants_data as $row): ?>
        <tr>
            <td><?= $counter ?></td>
            <td><?= htmlspecialchars($row['user_id']) ?></td>
            <td><input type="checkbox"></td>
        </tr>
        <?php $counter++; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="no-results">
    <i class="fas fa-user-slash" style="font-size:2rem;"></i>
    <p>No registrations found for this event.</p>
</div>
<?php endif; ?>
</div>

<?php
$registrants_stmt->close();
mysqli_close($conn);
?>
</body>
</html>