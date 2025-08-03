<?php
/**
 * handle_application.php
 *
 * Called via fetch() from helperdashboard.php when the helper clicks “Apply”.
 * Expects a JSON body: { "task_id": 123 }
 * Returns JSON: { "success": true|false, "message": "…" }
 */

session_start();
header('Content-Type: application/json');

require_once 'dbconnect.php';   // ↳ your mysqli $conn

/* -------------------------------------------------
   1.  Authentication – helper must be logged in
   -------------------------------------------------*/
if (!isset($_SESSION['helper_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in as a helper to apply.'
    ]);
    exit;
}

$helper_id = (int) $_SESSION['helper_id'];

/* -------------------------------------------------
   2.  Parse JSON payload
   -------------------------------------------------*/
$payload = json_decode(file_get_contents('php://input'), true);
$task_id = (int) ($payload['task_id'] ?? 0);

if (!$task_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid task ID.'
    ]);
    exit;
}

try {
    /* -------------------------------------------------
       3.  Start a transaction
       -------------------------------------------------*/
    $conn->begin_transaction();

    /* -------------------------------------------------
       4.  Ensure task is still OPEN
       -------------------------------------------------*/
    $taskChk = $conn->prepare(
        'SELECT status
           FROM tasks
          WHERE task_id = ?
            AND status = "open"
          FOR UPDATE'
    );
    $taskChk->bind_param('i', $task_id);
    $taskChk->execute();
    $taskOpen = $taskChk->get_result()->num_rows > 0;
    $taskChk->close();

    if (!$taskOpen) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Task is no longer open.'
        ]);
        exit;
    }

    /* -------------------------------------------------
       5.  Ensure helper is ACTIVE & VERIFIED
       -------------------------------------------------*/
    $helperChk = $conn->prepare(
        'SELECT helper_id
           FROM helpers
          WHERE helper_id = ?
            AND status = "active"
            AND verification_status = "verified"
          FOR UPDATE'
    );
    $helperChk->bind_param('i', $helper_id);
    $helperChk->execute();
    $helperOk = $helperChk->get_result()->num_rows > 0;
    $helperChk->close();

    if (!$helperOk) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Your account must be active and verified to apply.'
        ]);
        exit;
    }

    /* -------------------------------------------------
       6.  Prevent duplicate applications
       -------------------------------------------------*/
    $dupChk = $conn->prepare(
        'SELECT 1
           FROM applications
          WHERE task_id = ?
            AND helper_id = ?'
    );
    $dupChk->bind_param('ii', $task_id, $helper_id);
    $dupChk->execute();
    $alreadyApplied = $dupChk->get_result()->num_rows > 0;
    $dupChk->close();

    if ($alreadyApplied) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'You have already applied for this task.'
        ]);
        exit;
    }

    /* -------------------------------------------------
       7.  Insert the application
       -------------------------------------------------*/
    $ins = $conn->prepare(
        'INSERT INTO applications (task_id, helper_id, status)
         VALUES (?, ?, "pending")'
    );
    $ins->bind_param('ii', $task_id, $helper_id);
    $ins->execute();
    $ins->close();

    /* -------------------------------------------------
       8.  Commit & respond
       -------------------------------------------------*/
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!'
    ]);
    exit;

} catch (mysqli_sql_exception $e) {
    // 9. Roll back & report DB errors
    $conn->rollback();
    error_log('handle_application.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit;
}
?>
