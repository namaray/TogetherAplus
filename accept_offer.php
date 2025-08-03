<?php
/* ─────────────────────────────────────────────────────────────
   accept_offer.php – triggered when a helper accepts a direct offer
   ───────────────────────────────────────────────────────────── */
session_start();
require 'dbconnect.php';

// Ensure a helper is logged in
$helper_id = $_SESSION['helper_id'] ?? 0;
if (!$helper_id) {
    header('Location: helper_login.php'); // Redirect to helper login if not logged in
    exit;
}

// Get the decision_id from the URL (e.g., from an "Accept" button link)
$decision_id = isset($_GET['decision_id']) ? (int)$_GET['decision_id'] : 0;
if (!$decision_id) {
    $_SESSION['error_message'] = "Invalid offer specified.";
    header('Location: helper_dashboard.php'); // Redirect to their dashboard
    exit;
}

$conn->begin_transaction();
try {
    // 1. Fetch details and verify the offer belongs to this helper
    $verify_sql = "SELECT d.task_id, t.user_id, t.hourly_rate
                   FROM hiring_decisions d
                   JOIN tasks t ON d.task_id = t.task_id
                   WHERE d.decision_id = ? AND d.selected_helper_id = ? AND d.decision_status = 'pending'";
    
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $decision_id, $helper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Offer not found or already actioned.");
    }
    $offer_data = $result->fetch_assoc();
    $task_id = $offer_data['task_id'];
    $user_id = $offer_data['user_id'];
    $hourly_rate = $offer_data['hourly_rate'];
    $stmt->close();

    // 2. Update the hiring_decision to 'approved'
    $update_decision_stmt = $conn->prepare("UPDATE hiring_decisions SET decision_status = 'approved' WHERE decision_id = ?");
    $update_decision_stmt->bind_param("i", $decision_id);
    $update_decision_stmt->execute();
    $update_decision_stmt->close();

    // 3. Update the corresponding task to 'in_progress'
    $update_task_stmt = $conn->prepare("UPDATE tasks SET status = 'in_progress' WHERE task_id = ?");
    $update_task_stmt->bind_param("i", $task_id);
    $update_task_stmt->execute();
    $update_task_stmt->close();

    // 4. Create the final hiring_record. THIS is what enables Start/End functionality.
    $insert_hr_stmt = $conn->prepare(
        "INSERT INTO hiring_records (user_id, helper_id, task_id, hourly_rate, status) VALUES (?, ?, ?, ?, 'in_progress')"
    );
    $insert_hr_stmt->bind_param("iiid", $user_id, $helper_id, $task_id, $hourly_rate);
    $insert_hr_stmt->execute();
    $insert_hr_stmt->close();

    // If all queries succeed, commit the transaction
    $conn->commit();
    $_SESSION['success_message'] = "Offer accepted! The task is now in your 'In Progress' list.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Could not accept the offer: " . $e->getMessage();
}

// Redirect back to the helper's dashboard
header('Location: helperdashboard.php');
exit;
?>