<?php
session_start();
include('db_connect.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = strtolower(trim($_POST['email'])); // lowercase for consistency
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Fetch user info from database
        $stmt = $conn->prepare("SELECT user_id, email, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $user_email, $hashed_password, $is_admin_db);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Determine if user is admin
                $isAdmin = ($is_admin_db == 1);

                // Store session info
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $user_email;
                $_SESSION['is_admin'] = $isAdmin;

                // Redirect based on role
                if ($isAdmin) {
                    header("Location: adminhomepage.php");
                } else {
                    header("Location: homepage.php");
                }
                exit;
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "No account found with that email.";
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UniVENT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Login</h2>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <button type="submit" class="btn">Login</button>
    </form>
    <p>Don’t have an account? <a href="signup.php">Sign up here</a></p>
</div>
</body>
</html>
