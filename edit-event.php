<?php
session_start();


// Database connection
$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($event_id <= 0) {
    echo "Invalid Event ID.";
    exit;
}

// Handle form submission for updating event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $venue = $_POST['venue'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Combine date and time for database
    $start_date = $event_date . ' ' . $start_time . ':00';
    $end_date = $event_date . ' ' . $end_time . ':00';
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/events/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }
    }
    
    // Update event in database
    if (!empty($image_path)) {
        // Update with new image
        $sql = "UPDATE events SET title=?, description=?, venue=?, image_path=?, start_date=?, end_date=? WHERE event_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $title, $description, $venue, $image_path, $start_date, $end_date, $event_id);
    } else {
        // Update without changing image
        $sql = "UPDATE events SET title=?, description=?, venue=?, start_date=?, end_date=? WHERE event_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $title, $description, $venue, $start_date, $end_date, $event_id);
    }
    
    if ($stmt->execute()) {
        // Redirect to manage events page after successful update
        header("Location: manage-events.php?updated=1");
        exit();
    } else {
        echo "Error updating event: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch the event to edit - CORRECTED: using events table and event_id
$sql = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Event not found.";
    exit;
}

$event = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - UniVENT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5dc;
            color: #333;
            padding: 2rem;
        }

        .form-wrapper {
            max-width: 700px;
            background: white;
            margin: 2rem auto;
            padding: 2rem 2.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 2px solid #90ee90;
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: #2d5016;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d5016;
            margin-top: 1.5rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #90ee90;
            box-shadow: 0 0 0 3px rgba(144, 238, 144, 0.1);
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .datetime-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        button {
            width: 100%;
            margin-top: 2rem;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #90ee90, #32cd32);
            color: #2d5016;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(144, 238, 144, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #32cd32;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .current-image {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #90ee90;
        }

        .current-image img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 6px;
        }

        .image-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        @media screen and (max-width: 768px) {
            .form-wrapper {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .datetime-row {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="form-wrapper">
    <h2>Edit Event</h2>
    
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $event['event_id']; ?>">

        <label for="title">Event Title</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($event['description']); ?></textarea>

        <div class="datetime-row">
            <div>
                <label for="event_date">Date</label>
                <input type="date" name="event_date" id="event_date" value="<?php echo date('Y-m-d', strtotime($event['start_date'])); ?>" required>
            </div>

            <div>
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" id="start_time" value="<?php echo date('H:i', strtotime($event['start_date'])); ?>" required>
            </div>

            <div>
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" id="end_time" value="<?php echo date('H:i', strtotime($event['end_date'])); ?>" required>
            </div>
        </div>

        <label for="venue">Venue</label>
        <input type="text" name="venue" id="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>

        <label for="image">Event Image</label>
        <div class="image-input-container">
            <?php if (!empty($event['image_path'])): ?>
                <div class="current-image">
                    <strong>Current Image:</strong><br>
                    <img src="<?php echo htmlspecialchars($event['image_path']); ?>" 
                         alt="Current event image" 
                         onerror="this.style.display='none'">
                    <p><small><?php echo htmlspecialchars($event['image_path']); ?></small></p>
                </div>
            <?php endif; ?>

            <input type="file" name="image" id="image" accept="image/*">
            <small style="color: #666; font-size: 0.85em;">Leave empty to keep current image. Supported formats: JPG, PNG, GIF, WEBP</small>
            
            <div class="image-preview" id="image-preview"></div>
        </div>

        <button type="submit">Update Event</button>
    </form>

    <div class="back-link">
        <a href="manage-events.php"><i class="fas fa-arrow-left"></i> Back to Manage Events</a>
    </div>
</div>

<script>
    // Image preview for file upload
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Image preview">';
            }
            reader.readAsDataURL(this.files[0]);
        } else {
            preview.innerHTML = '';
        }
    });
</script>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>