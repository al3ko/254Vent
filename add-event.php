<?php
session_start();

$conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$isEditing = false;
$event = [
    'event_id' => '',
    'title' => '',
    'description' => '',
    'venue' => '',
    'image_path' => '',
    'start_date' => '',
    'end_date' => '',
    'created_by' => ''
];

if (isset($_GET['edit']) && isset($_GET['id'])) {
    $isEditing = true;
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $event = $res->fetch_assoc();
        } else {
            echo "Event not found.";
            exit;
        }
        $stmt->close();
    } else {
        echo "Error preparing select statement: " . $conn->error;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $venue = $_POST['venue'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $admin_id = $_SESSION['user_id']; // Make sure this session variable exists
    
    // Combine date and time for database
    $start_date = $event_date . ' ' . $start_time . ':00';
    $end_date = $event_date . ' ' . $end_time . ':00';
    
    // Handle image input
    $image_path = $event['image_path'] ?? ''; // Keep existing image if editing
    
    if (isset($_POST['image_source'])) {
        if ($_POST['image_source'] === 'file' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Handle file upload
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
                } else {
                    echo "<script>alert('Error uploading image file.');</script>";
                }
            } else {
                echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.');</script>";
            }
        } 
        elseif ($_POST['image_source'] === 'url' && !empty($_POST['image_url'])) {
            // Handle URL
            $url = filter_var($_POST['image_url'], FILTER_VALIDATE_URL);
            if ($url) {
                $image_path = $url;
            } else {
                echo "<script>alert('Please enter a valid image URL.');</script>";
            }
        }
    }
    
    if ($isEditing) {
        // Update existing event
        $id = $_POST['id'];
        $sql = "UPDATE events SET title=?, description=?, venue=?, image_path=?, start_date=?, end_date=? WHERE event_id=?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssssi", $title, $description, $venue, $image_path, $start_date, $end_date, $id);
            
            if ($stmt->execute()) {
                // CHANGED: Redirect to manage-events.php instead of admin-homepage.php
                header("Location: manage-events.php");
                exit();
            } else {
                echo "Error executing statement: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing UPDATE statement: " . $conn->error;
        }
    } else {
        // Create new event
        $sql = "INSERT INTO events (title, description, venue, image_path, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssssi", $title, $description, $venue, $image_path, $start_date, $end_date, $admin_id);
            
            if ($stmt->execute()) {
                // CHANGED: Redirect to manage-events.php instead of admin-homepage.php
                header("Location: manage-events.php");
                exit();
            } else {
                echo "Error executing statement: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing INSERT statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo $isEditing ? "Edit Event" : "Add Event"; ?> - UniVENT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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

    /* Image upload styles */
    .image-input-container {
      margin-bottom: 1rem;
    }

    .image-option-selector {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .option-label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: #f8f9fa;
      border: 2px solid #e9ecef;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      flex: 1;
      justify-content: center;
    }

    .option-label:hover {
      border-color: #90ee90;
    }

    .option-label input[type="radio"] {
      margin: 0;
    }

    .image-source-section {
      margin-bottom: 1rem;
    }

    .image-source-section input {
      margin-bottom: 0.5rem;
    }

    .help-text {
      color: #666;
      font-size: 0.85em;
      display: block;
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

    @media screen and (max-width: 768px) {
      .form-wrapper {
        padding: 1.5rem;
        margin: 1rem;
      }
      
      .image-option-selector {
        flex-direction: column;
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
    <h2><?php echo $isEditing ? "Edit Event" : "Create Event"; ?></h2>

    <form action="" method="POST" enctype="multipart/form-data">
      <?php if ($isEditing): ?>
        <input type="hidden" name="id" value="<?php echo $event['event_id']; ?>">
      <?php endif; ?>

      <label for="title">Event Title</label>
      <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

      <label for="description">Description</label>
      <textarea name="description" id="description" rows="5"><?php echo htmlspecialchars($event['description']); ?></textarea>

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
        <?php if ($isEditing && !empty($event['image_path'])): ?>
          <div class="current-image">
            <strong>Current Image:</strong><br>
            <img src="<?php echo htmlspecialchars($event['image_path']); ?>" 
                 alt="Current event image" 
                 onerror="this.style.display='none'">
            <p><small><?php echo htmlspecialchars($event['image_path']); ?></small></p>
          </div>
        <?php endif; ?>

        <div class="image-option-selector">
          <label class="option-label">
            <input type="radio" name="image_source" value="file" checked onchange="toggleImageSource()"> 
            <span>Upload File</span>
          </label>
          <label class="option-label">
            <input type="radio" name="image_source" value="url" onchange="toggleImageSource()"> 
            <span>Use URL</span>
          </label>
        </div>
        
        <div id="file-upload-section" class="image-source-section">
          <input type="file" name="image" id="image" accept="image/*">
          <small class="help-text">Supported formats: JPG, PNG, GIF, WEBP (Max: 5MB)</small>
        </div>
        
        <div id="url-upload-section" class="image-source-section" style="display: none;">
          <input type="url" name="image_url" id="image_url" placeholder="https://example.com/image.jpg">
          <small class="help-text">Enter a direct image URL</small>
        </div>
        
        <div class="image-preview" id="image-preview"></div>
      </div>

      <button type="submit"><?php echo $isEditing ? "Update Event" : "Create Event"; ?></button>
    </form>

    <div class="back-link">
      <!-- CHANGED: Back link also goes to manage-events.php -->
      <a href="manage-events.php"><i class="fas fa-arrow-left"></i> Back to Manage Events</a>
    </div>
  </div>

  <script>
    function toggleImageSource() {
      const fileSection = document.getElementById('file-upload-section');
      const urlSection = document.getElementById('url-upload-section');
      const fileInput = document.getElementById('image');
      const urlInput = document.getElementById('image_url');
      
      const selectedSource = document.querySelector('input[name="image_source"]:checked').value;
      
      if (selectedSource === 'file') {
        fileSection.style.display = 'block';
        urlSection.style.display = 'none';
        fileInput.disabled = false;
        urlInput.disabled = true;
        urlInput.value = '';
      } else {
        fileSection.style.display = 'none';
        urlSection.style.display = 'block';
        fileInput.disabled = true;
        urlInput.disabled = false;
        fileInput.value = '';
      }
      
      document.getElementById('image-preview').innerHTML = '';
    }

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

    document.getElementById('image_url').addEventListener('input', function(e) {
      const preview = document.getElementById('image-preview');
      const url = this.value.trim();
      
      if (url) {
        preview.innerHTML = '<img src="' + url + '" alt="Image preview" onerror="this.style.display=\'none\'">';
      } else {
        preview.innerHTML = '';
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      toggleImageSource();
    });
  </script>

</body>
</html>