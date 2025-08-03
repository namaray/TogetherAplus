<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to manage work tracking.'); window.location.href = 'login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hiring_id = (int)$_POST['hiring_id'];
    $action = $_POST['action'];

    // Fetch task details
    $query = "SELECT * FROM hiring_records WHERE hiring_id = $hiring_id";
    $result = $conn->query($query);

    if ($result->num_rows === 0) {
        echo "<script>alert('Invalid hiring record.'); window.location.href = 'current_jobs.php';</script>";
        exit;
    }

    $row = $result->fetch_assoc();
    $hourly_rate = $row['hourly_rate'];

    if ($action === 'start') {
        // Start work
        $start_time = date('Y-m-d H:i:s');
        $update_query = "UPDATE hiring_records SET start_time = '$start_time' WHERE hiring_id = $hiring_id";

        if ($conn->query($update_query) === TRUE) {
            echo "<script>alert('Work started successfully.'); window.location.href = 'current_jobs.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to start work. " . $conn->error . "');</script>";
        }
    } elseif ($action === 'end') {
        // End work and calculate logged hours
        $end_time = date('Y-m-d H:i:s');
        $update_query = "UPDATE hiring_records 
                         SET end_time = '$end_time', 
                             logged_hours = TIMESTAMPDIFF(HOUR, start_time, '$end_time'), 
                             status = 'completed' 
                         WHERE hiring_id = $hiring_id";

        if ($conn->query($update_query) === TRUE) {
            // Calculate payment amount
            $logged_hours_query = "SELECT logged_hours FROM hiring_records WHERE hiring_id = $hiring_id";
            $logged_hours_result = $conn->query($logged_hours_query);
            $logged_hours = $logged_hours_result->fetch_assoc()['logged_hours'];
            $amount = $logged_hours * $hourly_rate;

            // Insert into payments table
            $insert_payment_query = "INSERT INTO payments (user_id, hiring_id, amount, payment_method, status, created_at)
                                     VALUES ({$row['user_id']}, $hiring_id, $amount, 'pending', 'pending', NOW())";

            if ($conn->query($insert_payment_query) === TRUE) {
                echo "<script>alert('Work completed successfully. Payment has been calculated.'); window.location.href = 'current_jobs.php';</script>";
            } else {
                echo "<script>alert('Error: Unable to calculate payment. " . $conn->error . "');</script>";
            }
        } else {
            echo "<script>alert('Error: Unable to end work. " . $conn->error . "');</script>";
        }
    }
}

$conn->close();
?>
