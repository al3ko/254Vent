<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $conn = mysqli_connect("localhost", "root", "QWE123!@#qwe", "univent", 3307);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    // Check if user exists
    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["is_admin"] = $user["is_admin"]; // Either 0 or 1

            // Redirect based on role
            if ($user["is_admin"]) {
                header("Location: admin-homepage.php");
            } else {
                header("Location: homepage.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }

    mysqli_close($conn);
}
?>

<!-- HTML Login Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UniVENT</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f8f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-box {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-box h2 {
            margin-bottom: 1rem;
            text-align: center;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        input[type="submit"] {
            background-color: #FFA500;
            color: white;
            border: none;
            padding: 0.75rem;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #e69500;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
        }

        .link {
            text-align: center;
            margin-top: 1rem;
        }

        .link a {
            color: #3498db;
            text-decoration: none;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login to UniVENT</h2>

        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST" action="login.php">
            <input type="email" name="email" placeholder="Email address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>

        <div class="link">
            <p>Don't have an account? <a href="signup.html">Sign Up</a></p>
            <p><a href="forgot-password.php">Forgot Password?</a></p>
        </div>
    </div>
</body>
</html>
