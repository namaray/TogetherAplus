<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in as a helper to apply for tasks.'); window.location.href = 'login.php';</script>";
    exit;
}

$helper_id = $_SESSION['helper_id'];

// Check if the helper is active and verified
$helper_query = "SELECT status, verification_status FROM helpers WHERE helper_id = $helper_id";
$helper_result = $conn->query($helper_query);

if ($helper_result->num_rows === 0) {
    die("Error: Helper not found.");
}

$helper = $helper_result->fetch_assoc();

if ($helper['status'] !== 'active' || $helper['verification_status'] !== 'verified') {
    die("Error: You must be an active and verified helper to apply for tasks.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = (int)$_POST['task_id'];

    // Check if the helper already applied for this task
    $check_query = "SELECT * FROM hiring_decisions WHERE task_id = $task_id AND selected_helper_id = $helper_id";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        echo "<script>alert('You have already applied for this task.'); window.location.href = 'view_task.php';</script>";
    } else {
        // Insert the application into the hiring_decisions table
        $apply_query = "INSERT INTO hiring_decisions (task_id, selected_helper_id, decision_status) 
                        VALUES ($task_id, $helper_id, 'pending')";

        if ($conn->query($apply_query) === TRUE) {
            echo "<script>
                alert('You have successfully applied for the task!');
                window.location.href = 'view_task.php';
            </script>";
        } else {
            echo "<script>alert('Error: Unable to apply. " . $conn->error . "');</script>";
        }
    }
}

$conn->close();
?>
