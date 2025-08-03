<?php
/**
 * hire_helper.php
 * ------------------------------------------------------------
 * Approves a helper’s application, creates a hiring record,
 * and flips the task to “in-progress”.
 *
 * GET  : task_id, helper_id
 * SESSION : $_SESSION['user_id']
 * Redirects back to userdashboard.php with flash messages.
 * ------------------------------------------------------------
 */

session_start();
require 'dbconnect.php';          //  <-- your mysqli $conn

$task_id   = intval($_GET['task_id']   ?? 0);
$helper_id = intval($_GET['helper_id'] ?? 0);
$user_id   = intval($_SESSION['user_id'] ?? 0);

if (!$task_id || !$helper_id || !$user_id) {
    $_SESSION['error_message'] = 'Invalid request.';
    header('Location: userdashboard.php');
    exit;
}

$conn->begin_transaction();

try {
    /* ── 1. Lock the task & fetch hourly_rate ─────────────────── */
    $taskStmt = $conn->prepare(
        'SELECT status, hourly_rate
           FROM tasks
          WHERE task_id = ? AND user_id = ?
          FOR UPDATE'
    );
    $taskStmt->bind_param('ii', $task_id, $user_id);
    $taskStmt->execute();
    $task = $taskStmt->get_result()->fetch_assoc();
    $taskStmt->close();

    if (!$task)                     throw new Exception('Task not found or not yours.');
    if ($task['status'] !== 'open') throw new Exception('Task is no longer open.');

    $hourly_rate = (float)$task['hourly_rate'];

    /* ── 2. Lock the helper’s pending application ─────────────── */
    $appStmt = $conn->prepare(
        'SELECT application_id
           FROM applications
          WHERE task_id = ? AND helper_id = ? AND status = "pending"
          LIMIT 1
          FOR UPDATE'
    );
    $appStmt->bind_param('ii', $task_id, $helper_id);
    $appStmt->execute();
    $app = $appStmt->get_result()->fetch_assoc();
    $appStmt->close();

    if (!$app)                      throw new Exception('No pending application from this helper.');
    $application_id = (int)$app['application_id'];

    /* ── 3. Approve this application ──────────────────────────── */
    $upd = $conn->prepare(
        'UPDATE applications SET status = "approved"
          WHERE application_id = ?'
    );
    $upd->bind_param('i', $application_id);
    $upd->execute();
    $upd->close();

    /* ── 4. Reject other pending bids for this task ───────────── */
    $rej = $conn->prepare(
        'UPDATE applications
            SET status = "rejected"
          WHERE task_id = ? AND status = "pending" AND application_id <> ?'
    );
    $rej->bind_param('ii', $task_id, $application_id);
    $rej->execute();
    $rej->close();

    /* ── 5. Record the hiring decision (audit trail) ──────────── */
    $dec = $conn->prepare(
        'INSERT INTO hiring_decisions
              (task_id, application_id, selected_helper_id, decision_status)
         VALUES (?,       ?,             ?,                 "approved")'
    );
    $dec->bind_param('iii', $task_id, $application_id, $helper_id);
    $dec->execute();               // we keep the audit row, but no FK needed
    $dec->close();

    /* ── 6. Create the hiring_records row  (NO decision_id) ──── */
    $hire = $conn->prepare(
        'INSERT INTO hiring_records
              (user_id, helper_id, task_id, hourly_rate, status)
         VALUES (?,       ?,        ?,       ?,           "in_progress")'
    );
    $hire->bind_param('iiid', $user_id, $helper_id, $task_id, $hourly_rate);
    $hire->execute();
    $hire->close();

    /* ── 7. Flip task to in_progress ──────────────────────────── */
    $tskUpd = $conn->prepare(
        'UPDATE tasks SET status = "in_progress" WHERE task_id = ?'
    );
    $tskUpd->bind_param('i', $task_id);
    $tskUpd->execute();
    $tskUpd->close();

    /* ── 8. Commit & bounce back ──────────────────────────────── */
    $conn->commit();
    $_SESSION['success_message'] = 'Helper hired successfully!';

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Hiring failed: ' . $e->getMessage();
}

$update = $conn->prepare("
    UPDATE hiring_records
    SET    status     = 'in_progress',
           start_time = NOW()

    WHERE  hiring_id  = ?
      AND  status     = 'accepted'
");
$update->bind_param("i", $hiring_id);

if ($update->execute()) {

    // ===== CHAT-INTEGRATION >>> drop automatic system msg
    $sys = $conn->prepare("
        INSERT INTO chat_messages
              (sender_id, sender_role, receiver_id, receiver_role, message_content)
        SELECT u.user_id, 'user', h.helper_id, 'helper',
               '🔔 Chat has been enabled for this job. Feel free to coordinate here.'
        FROM hiring_records hr
        JOIN users   u ON u.user_id   = hr.user_id
        JOIN helpers h ON h.helper_id = hr.helper_id
        WHERE hr.hiring_id = ?
        LIMIT 1
    ");
    $sys->bind_param('i', $hiring_id);
    $sys->execute();
    // <<< CHAT-INTEGRATION =====

    $_SESSION['success_message'] = 'Task started and chat opened.';
} else {
    $_SESSION['error_message'] = 'Failed to start task.';
}
header('Location: userdashboard.php');
exit;
?>
