<?php
session_start();
include 'dbconnect.php';
 // Include database connection
  

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $password = !empty($_POST['password']) ? $_POST['password'] : null;

    $profile_photo = $user['profile_photo']; // Default to existing photo

    // Handle profile picture upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES['profile_photo']['name']);
        move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file);
        $profile_photo = $target_file;
    }

    // Update password only if provided
    $password_hash = $user['password_hash'];
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
        }
    }

    // Update user data
    if (!isset($error)) {
        $sql_update = "UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, profile_photo = ?, password_hash = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param('ssssssi', $name, $email, $phone, $address, $profile_photo, $password_hash, $user_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}

$conn->close();
?>
 <? include 'header_user.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin: 10px 0 5px;
            color:black;
        }

        input, button {
            margin: 5px 0 15px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .profile-photo-preview {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-photo-preview img {
            max-width: 150px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .success {
            color: green;
            text-align: center;
        }

        .error {
            color: red;
            text-align: center;
        }
    </style>
    <script>
        function previewImage(event) {
            const preview = document.getElementById('profile-photo-preview');
            preview.src = URL.createObjectURL(event.target.files[0]);
        }
    </script>
</head>

<body>

<div class="container">

    <h1>Edit Profile</h1>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="profile-photo-preview">
            <img id="profile-photo-preview" src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
        </div>
        <label for="profile_photo">Profile Photo</label>
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" onchange="previewImage(event)">
        
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>

        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>

        <label for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>

        <label for="password">New Password (optional)</label>
        <input type="password" id="password" name="password" placeholder="New Password (optional)">

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
