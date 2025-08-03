<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to track work.'); window.location.href = 'login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hiring_id = isset($_POST['hiring_id']) ? (int)$_POST['hiring_id'] : 0;
    $action = $_POST['action'];

    if ($hiring_id <= 0 || empty($action)) {
        echo "<script>alert('Invalid request.'); window.location.href = 'current_jobs.php';</script>";
        exit;
    }

    if ($action === 'start') {
        // Start the work
        $query = "UPDATE hiring_records 
                  SET start_time = NOW() 
                  WHERE hiring_id = ? AND status = 'in_progress' AND start_time IS NULL";

    } elseif ($action === 'end') {
        // End the work and calculate logged hours
        $query = "UPDATE hiring_records 
                  SET end_time = NOW(), 
                      logged_hours = TIMESTAMPDIFF(HOUR, start_time, NOW()) 
                  WHERE hiring_id = ? AND status = 'in_progress' AND start_time IS NOT NULL";
    } else {
        echo "<script>alert('Invalid action.'); window.location.href = 'current_jobs.php';</script>";
        exit;
    }

    // Use prepared statements for security
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hiring_id);

    if ($stmt->execute()) {
        echo "<script>alert('Action recorded successfully.'); window.location.href = 'current_jobs.php';</script>";
    } else {
        error_log("Error updating hiring record: " . $conn->error);
        echo "<script>alert('An error occurred while updating the record.'); window.location.href = 'current_jobs.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
