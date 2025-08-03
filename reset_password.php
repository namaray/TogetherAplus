<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Check if the token exists in the URL
if (!isset($_GET['token'])) {
    die("Invalid or missing token.");
}

$token = $conn->real_escape_string(trim($_GET['token']));

// Fetch the reset request details from the database
$stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token.");
}

$row = $result->fetch_assoc();
$email = $row['email'];
$created_at = strtotime($row['created_at']);

// Check if the token is expired (valid for 1 hour)
if (time() - $created_at > 3600) {
    die("This reset link has expired. Please request a new password reset.");
}

// Handle form submission for resetting the password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the user's password in the corresponding table
        $tables = ['users', 'helpers', 'admins'];
        $updated = false;

        foreach ($tables as $table) {
            $stmt = $conn->prepare("UPDATE $table SET password_hash = ? WHERE email = ?");
            $stmt->bind_param('ss', $password_hash, $email);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $updated = true;
                break;
            }
        }

        if ($updated) {
            // Delete the reset token from the database
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();

            $success = "Your password has been reset successfully. <a href='login.php'>Login here</a>.";
        } else {
            $error = "Failed to reset the password. Please try again.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TogetherA+</title>
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

        input[type="password"],
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
    </style>
</head>
<body>
<div class="container">
    <h1>Reset Password</h1>
    <p>Enter your new password below.</p>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
</div>
<script src="EyeTracking.js"></script>

</body>
</html>
