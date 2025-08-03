<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $relationship = $conn->real_escape_string($_POST['relationship']);

    // Insert into trusted_contacts table
    $sql = "INSERT INTO trusted_contacts (user_id, name, phone_number, relationship, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $name, $phone_number, $relationship);
        if ($stmt->execute()) {
            header('Location: trusted_contacts.php'); // Redirect to list view
            exit;
        } else {
            $error = "Failed to add contact: " . $conn->error;
        }
    } else {
        $error = "Error preparing statement: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trusted Contact - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="trusted-contacts.css">
</head>
<body>
    <?php include('header_user.php'); ?> <!-- Include header -->
    <main>
        <div class="trusted-container">
            <h1 class="trusted-heading">Add Trusted Contact</h1>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form id="trusted-form" method="POST">
                <div class="trusted-form-group">
                    <label for="trusted-name">Name</label>
                    <input type="text" id="trusted-name" name="name" class="trusted-input" placeholder="Enter name" required>
                </div>
                <div class="trusted-form-group">
                    <label for="trusted-phone">Phone Number</label>
                    <input type="tel" id="trusted-phone" name="phone_number" class="trusted-input" placeholder="Enter phone number" required>
                </div>
                <div class="trusted-form-group">
                    <label for="relationship">Relationship</label>
                    <input type="text" id="relationship" name="relationship" class="trusted-input" placeholder="Enter relationship">
                </div>
                <button type="submit" class="trusted-add-btn">Add Contact</button>
            </form>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 TogetherA+. All rights reserved.</p>
    </footer>

</body>
</html>
