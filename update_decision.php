<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to approve a helper.'); window.location.href = 'login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision_id = (int)$_POST['decision_id'];
    $helper_id = (int)$_POST['helper_id'];
    $task_id = $_SESSION['task_id']; // Get task_id from session
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'];

    // Debugging: Print POST data
    // print_r($_POST);

    // Verify that the decision exists and belongs to the task
    $decision_check_query = "SELECT * FROM hiring_decisions 
                             WHERE decision_id = $decision_id AND task_id = $task_id";
    $decision_check_result = $conn->query($decision_check_query);

    if ($decision_check_result->num_rows === 0) {
        echo "<script>alert('Invalid request or decision does not exist.'); window.location.href = 'user_posted_jobs.php';</script>";
        exit;
    }

    if ($action === 'approve') {
        // Get hourly_rate and fixed_rate from the tasks table
        $task_query = "SELECT hourly_rate FROM tasks WHERE task_id = $task_id";
        $task_result = $conn->query($task_query);

        if ($task_result->num_rows === 0) {
            echo "<script>alert('Invalid task ID.'); window.location.href = 'user_posted_jobs.php';</script>";
            exit;
        }

        $task_data = $task_result->fetch_assoc();
        $hourly_rate = $task_data['hourly_rate'];
        $fixed_rate = $task_data['fixed_rate'];

        // Insert into hiring_records table based on task type
        if (!is_null($hourly_rate)) {
            // Hourly-based task
            $insert_query = "INSERT INTO hiring_records (user_id, helper_id, task_id, hourly_rate, status, created_at)
                             VALUES ($user_id, $helper_id, $task_id, $hourly_rate, 'in_progress', NOW())";
        }  
        } else {
            echo "<script>alert('Error: Task type not identified.'); window.location.href = 'user_posted_jobs.php';</script>";
            exit;
        }

        // Debugging: Print the query being executed
        // echo $insert_query;

        if ($conn->query($insert_query) === TRUE) {
            // Update decision_status in hiring_decisions table
            $update_query = "UPDATE hiring_decisions SET decision_status = 'approved' WHERE decision_id = $decision_id";
            if ($conn->query($update_query) === TRUE) {
                echo "<script>
                    alert('Helper approved and task is now in progress!');
                    window.location.href = 'progress_page.php';
                </script>";
            } else {
                die("Error: Unable to update decision status. " . $conn->error);
            }
        } else {
            die("Error: Unable to hire helper. " . $conn->error);
        }
    } elseif ($action === 'reject') {
        // Update decision_status to rejected
        $update_query = "UPDATE hiring_decisions SET decision_status = 'rejected' WHERE decision_id = $decision_id";
        if ($conn->query($update_query) === TRUE) {
            echo "<script>
                alert('Helper application rejected.');
                window.location.href = 'user_posted_jobs.php';
            </script>";
        } else {
            die("Error: Unable to reject application. " . $conn->error);
        }
    }

$conn->close();
?>
