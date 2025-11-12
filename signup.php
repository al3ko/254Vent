<?php
session_start();
include('db_connect.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['user_id']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($user_id) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {

            // Check if email already exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = "An account with this email already exists.";
            } else {
                // Determine user role
                $is_admin = 0;
                if (preg_match("/^111\d{3}$/", $user_id)) {
                    $is_admin = 1; // Admin ID pattern 111###
                }

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user data
                $stmt = $conn->prepare("INSERT INTO users (user_id, email, password, is_admin) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $user_id, $email, $hashed_password, $is_admin);

                if ($stmt->execute()) {
                    $message = "Registration successful! You can now log in.";
                } else {
                    $message = "Error: Could not register user.";
                }
                $stmt->close();
            }
            $check->close();
        } else {
            $message = "Passwords do not match.";
        }
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Create Account</h2>
    <?php if($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form action="signup.php" method="POST">
        <label>User ID</label>
        <input type="text" name="user_id" placeholder="Enter your user ID ()" required>

        <label>Email</label>
        <input type="email" name="email" placeholder="Enter your email" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Confirm password" required>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
