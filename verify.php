<?php
session_start();

// Load configuration
require_once __DIR__ . '/conf.php';

try {
    // Create PDO connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = isset($_POST['code']) ? trim($_POST['code']) : '';

    // Get user id (from session or hidden input)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ($_POST['user_id'] ?? '');

    if (empty($input_code) || empty($user_id)) {
        echo "Invalid request.";
        exit;
    }

    // Query DB for user’s verification code
    $stmt = $pdo->prepare("SELECT verification_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if ($row && $input_code === $row['verification_code']) {
        $_SESSION['verified'] = true;
        echo "<p style='color:green'>✅ Verification successful. You may now access your account.</p>";
    } else {
        echo "<p style='color:red'>❌ Invalid verification code. Access denied.</p>";
    }
} else {
    // Show form
    ?>
    <form method="post" action="">
        <label for="code">Enter Verification Code:</label>
        <input type="text" name="code" id="code" required>
        <!-- Hidden user_id field for testing -->
        <input type="hidden" name="user_id" value="1">
        <button type="submit">Verify</button>
    </form>
    <?php
}
