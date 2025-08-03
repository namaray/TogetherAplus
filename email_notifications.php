<?php
include('db.php');
// Email notification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id']; // User receiving the email
    $message = $_POST['message']; // Email message (e.g., "Your task has been updated.")
    $subject = "Task Update Notification";

    // Fetch user's email from the database
    $query = "SELECT email FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $to = $user['email'];
        $headers = "From: no-reply@togetheraplus.com";

        // Send email
        if (mail($to, $subject, $message, $headers)) {
            echo "Email notification sent successfully!";
        } else {
            echo "Failed to send email.";
        }
    } else {
        echo "User not found.";
    }
    $stmt->close();
}
?>
