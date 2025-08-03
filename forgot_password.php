<?php
session_start();
include 'dbconnect.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email']));

    try {
        // Check if the email exists in any of the tables
        $tables = ['users', 'helpers', 'admins'];
        $found = false;
        $role = null;

        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT email FROM $table WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $found = true;
                $role = $table;
                break;
            }
        }

        if ($found) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));

            // Insert the token into a password_resets table
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW()) 
                                    ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = NOW()");
            $stmt->bind_param('ss', $email, $token);
            $stmt->execute();

            // Send the reset email
            $reset_link = "http://localhost/reset_password.php?token=$token";
            $subject = "Password Reset Request - TogetherA+";
            $message = "Hi,\n\nYou requested a password reset. Click the link below to reset your password:\n$reset_link\n\nIf you did not request this, please ignore this email.";
            $headers = "From: no-reply@togetheraplus.com";

            if (mail($email, $subject, $message, $headers)) {
                $success = "A password reset link has been sent to your email.";
            } else {
                $error = "Failed to send the email. Please try again later.";
            }
        } else {
            $error = "No account found with this email.";
        }
    } catch (Exception $e) {
        error_log("Forgot Password error: " . $e->getMessage(), 3, 'logs/forgot_password_errors.log');
        $error = "An error occurred. Please try again later.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to bottom right, #f7f9fc, #dde9f5);
        }

        .container {
            background: white;
            padding: 20px;
            width: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        input[type="email"],
        button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .success {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        .links {
            margin-top: 10px;
        }

        .links a {
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }

        .links a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Forgot Password</h1>
    <p>Enter your email address to receive a password reset link.</p>
    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email (e.g., name@example.com)" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <div class="links">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
