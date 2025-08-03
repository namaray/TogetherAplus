<?php
/* ──────────────────────────────────────────────────────────────
   direct_offer.php – create a task that is offered to ONE helper
   ────────────────────────────────────────────────────────────── */
session_start();
require 'dbconnect.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$helper_id = intval($_POST['helper_id'] ?? 0);

$title       = trim($_POST['title']        ?? '');
$description = trim($_POST['description']  ?? '');
$skills      = trim($_POST['skill_needed'] ?? '');
$rate        = floatval($_POST['hourly_rate'] ?? 0);
$urgency     = trim($_POST['urgency']      ?? '');

if (!$user_id || !$helper_id || $title === '' || $rate <= 0) {
    $_SESSION['error_message'] = 'Missing or invalid form data.';
    header('Location: recommended_helpers.php');
    exit;
}

$conn->begin_transaction();
try {
    /* 1 ─ create the task (status still “open” so the user can track it) */
    $task = $conn->prepare(
        'INSERT INTO tasks
              (title, description, skill_required, hourly_rate, urgency, user_id, status)
         VALUES (?,?,?,?,?,?,"open")'
    );
    $task->bind_param('sssdsi', $title, $description, $skills, $rate, $urgency, $user_id);
    $task->execute();
    $task_id = $task->insert_id;
    $task->close();

    /* 2 ─ artificial “application” row so FK in hiring_decisions isn’t NULL,
            BUT we mark it as *approved* – therefore it never appears in
            the helper’s “Applied Jobs” list (that list only shows `pending`) */
    $app = $conn->prepare(
        'INSERT INTO applications (task_id, helper_id, status)
         VALUES (?,?, "approved")'
    );
    $app->bind_param('ii', $task_id, $helper_id);
    $app->execute();
    $application_id = $app->insert_id;
    $app->close();

    /* 3 ─ pending hiring decision → becomes the “Job Offer” card */
    $dec = $conn->prepare(
        'INSERT INTO hiring_decisions
              (task_id, application_id, selected_helper_id, decision_status)
         VALUES (?,?,?,"pending")'
    );
    $dec->bind_param('iii', $task_id, $application_id, $helper_id);
    $dec->execute();
    $dec->close();

    $conn->commit();
    $_SESSION['success_message'] = 'Offer sent to the helper!';
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Could not send offer: '.$e->getMessage();
}
header('Location: userdashboard.php');
exit;
?>
