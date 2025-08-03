<?php
session_start();
include '../dbconnect.php'; // Include database connection

// Ensure only logged-in super admins can register new admins

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);

    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!in_array($role, ['super_admin', 'moderator'])) {
        $error = "Invalid role selected.";
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM admins WHERE email = '$email'";
        $check_result = $conn->query($check_query);

        if ($check_result->num_rows > 0) {
            $error = "An admin with this email already exists.";
        } else {
            // Hash the password and insert the new admin
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO admins (name, email, password_hash, role) VALUES ('$name', '$email', '$password_hash', '$role')";

            if ($conn->query($insert_query)) {
                $success = "New admin registered successfully!";
            } else {
                $error = "Error registering admin: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #F3F4F6;
        }
        .container {
            width: 400px;
            background: white;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        .container h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #4A5568;
        }
        .container input, .container select, .container button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #CBD5E0;
            border-radius: 4px;
            font-size: 16px;
        }
        .container button {
            background: #2B6CB0;
            color: white;
            border: none;
            cursor: pointer;
        }
        .container button:hover {
            background: #2C5282;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }
        .success {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    
<div class="container">
    <h1>Register New Admin</h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="super_admin">Super Admin</option>
            <option value="moderator">Moderator</option>
        </select>
        <button type="submit">Register Admin</button>
    </form>
</div>
</body>
</html>









