<?php
session_start();
include 'dbconnect.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('User not logged in.');
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recommended_helpers.php');
    exit;
}

// --- Data Validation ---
$user_id = $_SESSION['user_id'];
$helpmate_id = filter_input(INPUT_POST, 'helpmate_id', FILTER_VALIDATE_INT);
$task_title = trim(filter_input(INPUT_POST, 'task_title', FILTER_SANITIZE_STRING));
$task_description = trim(filter_input(INPUT_POST, 'task_description', FILTER_SANITIZE_STRING));
$hourly_rate = filter_input(INPUT_POST, 'hourly_rate', FILTER_VALIDATE_FLOAT);
$urgency = in_array($_POST['urgency'], ['low', 'medium', 'high']) ? $_POST['urgency'] : 'medium';

if (!$helpmate_id || empty($task_title) || empty($task_description) || $hourly_rate === false || $hourly_rate <= 0) {
    $_SESSION['error_message'] = "Invalid data provided. Please fill out the form correctly.";
    header('Location: recommended_helpers.php');
    exit;
}


// --- Database Transaction ---
$conn->begin_transaction();

try {
    // 1. Create a new "direct" task.
    // We assume the `tasks` table has a `visibility` column ('public', 'direct')
    // For now, we will proceed without it, but it's a recommended addition.
    // We'll also pre-set the skill requirement based on the helpmate's profile if possible.
    $skill_query = "SELECT skills FROM helpers WHERE helper_id = ?";
    $skill_stmt = $conn->prepare($skill_query);
    $skill_stmt->bind_param("i", $helpmate_id);
    $skill_stmt->execute();
    $skill_result = $skill_stmt->get_result()->fetch_assoc();
    $skill_required = $skill_result['skills'] ?? 'General';

    $sql_task = "INSERT INTO tasks (title, description, skill_required, hourly_rate, urgency, user_id, status) VALUES (?, ?, ?, ?, ?, ?, 'open')";
    $stmt_task = $conn->prepare($sql_task);
    $stmt_task->bind_param("sssdsi", $task_title, $task_description, $skill_required, $hourly_rate, $urgency, $user_id);
    $stmt_task->execute();
    
    // Get the ID of the task we just created
    $task_id = $conn->insert_id;

    // 2. Create an application record to represent the direct offer.
    // A new status 'offered' could be added to the ENUM for clarity.
    $sql_app = "INSERT INTO applications (task_id, helper_id, status) VALUES (?, ?, 'pending')";
    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->bind_param("ii", $task_id, $helpmate_id);
    $stmt_app->execute();

    // If all queries were successful, commit the transaction
    $conn->commit();

    // Set success message and redirect
    $_SESSION['success_message'] = "Your hiring request has been sent to the HelpMate!";
    header('Location: userdashboard.php');
    exit;

} catch (Exception $e) {
    // If any query failed, roll back the transaction
    $conn->rollback();
    
    // Log the error and show a user-friendly message
    error_log("Direct hire request failed: " . $e->getMessage());
    $_SESSION['error_message'] = "An unexpected error occurred. Could not send the request.";
    header('Location: recommended_helpers.php');
    exit;
}
?>
