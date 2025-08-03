<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Check if the user or helper is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to update progress.'); window.location.href = 'login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hiring_id = isset($_POST['hiring_id']) ? (int)$_POST['hiring_id'] : 0;
    $new_status = $_POST['status'] ?? '';

    // Validate input
    if ($hiring_id <= 0 || $new_status !== 'completed') {
        echo "<script>alert('Invalid request.'); window.location.href = 'task_progress.php';</script>";
        exit;
    }

    // Fetch the hiring record to calculate payment
    $query = "SELECT hr.hiring_id, hr.logged_hours, t.hourly_rate, hr.helper_id, hr.user_id 
              FROM hiring_records hr
              JOIN tasks t ON hr.task_id = t.task_id
              WHERE hr.hiring_id = $hiring_id AND hr.status = 'in_progress'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
        $logged_hours = $record['logged_hours'] ?? 0;
        $hourly_rate = $record['hourly_rate'] ?? 0.00;
        $total_amount = $logged_hours * $hourly_rate;
        $helper_id = $record['helper_id'];
        $user_id = $record['user_id'];

        // Begin transaction to ensure data consistency
        $conn->begin_transaction();

        try {
            // Update the hiring_records table to mark as completed
            $update_query = "UPDATE hiring_records SET status = 'completed' WHERE hiring_id = $hiring_id";
            if (!$conn->query($update_query)) {
                throw new Exception("Error updating hiring record: " . $conn->error);
            }

            // Insert payment record into the payments table
            $payment_query = "INSERT INTO payments (user_id, hiring_id, amount, payment_method, status) 
                              VALUES ($user_id, $hiring_id, $total_amount, 'credit_card', 'completed')";
            if (!$conn->query($payment_query)) {
                throw new Exception("Error inserting payment record: " . $conn->error);
            }

            // Commit the transaction
            $conn->commit();

            // Redirect to the give_review page
            echo "<script>alert('Task marked as completed and payment recorded successfully. Redirecting to review.'); 
                  window.location.href = 'give_review.php?hiring_id=$hiring_id';</script>";
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            error_log($e->getMessage());
            echo "<script>alert('An error occurred: " . $e->getMessage() . "'); window.location.href = 'task_progress.php';</script>";
        }
    } else {
        echo "<script>alert('Hiring record not found or already completed.'); window.location.href = 'task_progress.php';</script>";
    }
}

$conn->close();
?>
