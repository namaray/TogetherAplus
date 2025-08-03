<?php
/* ─────────────────────────────────────────────────────────────
   decline_offer.php – triggered when a helper rejects a direct offer
   ───────────────────────────────────────────────────────────── */
session_start();
require 'dbconnect.php';

// Ensure a helper is logged in
$helper_id = $_SESSION['helper_id'] ?? 0;
if (!$helper_id) {
    header('Location: helper_login.php');
    exit;
}

// Get the decision_id from the URL
$decision_id = isset($_GET['decision_id']) ? (int)$_GET['decision_id'] : 0;
if (!$decision_id) {
    $_SESSION['error_message'] = "Invalid offer specified.";
    header('Location: helper_dashboard.php');
    exit;
}

$conn->begin_transaction();
try {
    /* 1 ── fetch task_id & sanity-check that this offer is still pending
            and really belongs to the logged-in helper               */
    $fetch = $conn->prepare(
        "SELECT task_id
           FROM hiring_decisions
          WHERE decision_id = ?
            AND selected_helper_id = ?
            AND decision_status  = 'pending'
          LIMIT 1"
    );
    $fetch->bind_param("ii", $decision_id, $helper_id);
    $fetch->execute();
    $result = $fetch->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Offer not found, already actioned, or not yours.");
    }
    $task_id = (int) $result->fetch_assoc()['task_id'];
    $fetch->close();

    /* 2 ── mark the decision as rejected                              */
    $upd_decision = $conn->prepare(
        "UPDATE hiring_decisions
            SET decision_status = 'rejected'
          WHERE decision_id = ?"
    );
    $upd_decision->bind_param("i", $decision_id);
    $upd_decision->execute();
    $upd_decision->close();

    /* 3 ── re-open the task so the user can re-offer / post again      */
    $reopen_task = $conn->prepare(
        "UPDATE tasks
            SET status = 'open'
          WHERE task_id = ?"
    );
    $reopen_task->bind_param("i", $task_id);
    $reopen_task->execute();
    $reopen_task->close();

    /* 4 ── remove any provisional hiring_record that was created
            when the helper first *accepted* but never started work    */
    $purge_hr = $conn->prepare(
        "DELETE FROM hiring_records
          WHERE task_id   = ?
            AND helper_id = ?
            AND status    = 'in_progress'
            AND start_time IS NULL"
    );
    $purge_hr->bind_param("ii", $task_id, $helper_id);
    $purge_hr->execute();
    $purge_hr->close();

    /* 5 ── all good – commit                                          */
    $conn->commit();
    $_SESSION['success_message'] = "Offer declined.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Could not decline the offer: " . $e->getMessage();
}

// Redirect back to the helper's dashboard
header('Location: helperdashboard.php');
exit;
?>
