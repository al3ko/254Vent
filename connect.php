<?php
// Database configuration
$host = 'localhost: 80,443';
$db   = 'univent';
$user = 'root';
$pass = 'QWE123!@#qwe';
$port = 3307;
$charset = 'utf8mb4'; // ✅ FIXED: was undefined

// Set up DSN and options for PDO
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect to the database
    $pdo = new PDO($dsn, $user, $pass, $options);
    error_log("[INFO] Database connection successful.");
} catch (\PDOException $e) {
    // Log error details for debugging
    error_log("[ERROR] Database connection failed: " . $e->getMessage());

    // Hide sensitive details from users
    http_response_code(500);
    echo "Internal server error. Please try again later.";
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[INFO] POST request received.");

    $name     = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo "All fields (name, email, password) are required.";
        error_log("[WARNING] Missing fields: name='$name', email='$email'.");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid email format.";
        error_log("[WARNING] Invalid email format: $email");
        exit;
    }

    // Check if email already exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo "Email is already registered.";
            error_log("[INFO] Email already exists: $email");
            exit;
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo "Internal error. Please try again.";
        error_log("[ERROR] Failed to check email existence: " . $e->getMessage());
        exit;
    }

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $passwordHash]);
        http_response_code(201);
        echo "Registration successful.";
        error_log("[SUCCESS] New user registered: $email");
    } catch (\PDOException $e) {
        http_response_code(500);
        echo "Failed to register user.";
        error_log("[ERROR] Registration failed: " . $e->getMessage());
    }

} else {
    // Method not allowed
    http_response_code(405);
    header('Allow: POST');
    echo "Method not allowed. Use POST.";
    error_log("[WARNING] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}
