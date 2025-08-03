<?php
/**
 * handle_work_timer.php   –   PHP-7 compatible
 * ------------------------------------------------------------
 * Handles “Start” and “End” clicks from helpers.
 *
 * GET params : hiring_id, action = start | end
 * SESSION    : $_SESSION['helper_id']
 *
 * • Normal <a href="..."> click  → redirects back to helperdashboard.php
 * • AJAX/fetch                    → returns JSON { success, message }
 * ------------------------------------------------------------
 */

session_start();
require 'dbconnect.php';

/* ── 0. AJAX detector (PHP-7 safe) ──────────────────────────── */
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
          (isset($_SERVER['HTTP_ACCEPT']) &&
           strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

/* ── 1. Auth & input validation ─────────────────────────────── */
if (!isset($_SESSION['helper_id'])) {
    $msg = 'Please log in.';
    if ($isAjax) { echo json_encode(['success'=>false,'message'=>$msg]); exit; }
    $_SESSION['error_message'] = $msg;
    header('Location: login.php'); exit;
}

$helper_id = (int)$_SESSION['helper_id'];
$hiring_id = filter_input(INPUT_GET, 'hiring_id', FILTER_VALIDATE_INT);
$action    = $_GET['action'] ?? '';

if (!$hiring_id || !in_array($action, ['start', 'end'], true)) {
    $msg = 'Invalid request.';
    if ($isAjax) { echo json_encode(['success'=>false,'message'=>$msg]); exit; }
    $_SESSION['error_message'] = $msg;
    header('Location: helperdashboard.php'); exit;
}

/* ── 2. Begin atomic transaction ───────────────────────────── */
$conn->begin_transaction();
try {
    /* lock the hiring record */
    $sel = $conn->prepare(
        'SELECT user_id, start_time, end_time, hourly_rate
           FROM hiring_records
          WHERE hiring_id = ? AND helper_id = ?
          FOR UPDATE'
    );
    $sel->bind_param('ii', $hiring_id, $helper_id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc() ?: [];
    $sel->close();

    if (!$row)                          throw new Exception('Job not found.');
    if ($action==='start' && $row['start_time']) throw new Exception('Work already started.');
    if ($action==='end'   && !$row['start_time']) throw new Exception('Work has not started.');
    if ($action==='end'   && $row['end_time'])    throw new Exception('Work already ended.');

    /* ── 3. Perform requested action ───────────────────────── */
    if ($action === 'start') {

        $upd = $conn->prepare(
            'UPDATE hiring_records
                SET start_time = NOW()
              WHERE hiring_id = ?'
        );
        $upd->bind_param('i', $hiring_id);
        $upd->execute();
        $upd->close();

        $msg = 'Work started!';

    } else { /* action === end */

        /* 3a. close timer – generated column will compute logged_hours */
        $upd = $conn->prepare(
            'UPDATE hiring_records
                SET end_time            = NOW(),
                    status              = "completed",
                    helper_confirmation = "confirmed"
              WHERE hiring_id = ?'
        );
        $upd->bind_param('i', $hiring_id);
        $upd->execute();
        $upd->close();

        /* 3b. create pending payment using generated logged_hours */
        $pay = $conn->prepare(
            'INSERT INTO payments (user_id, hiring_id, amount, payment_method, status)
             SELECT user_id,
                    hiring_id,
                    logged_hours * hourly_rate,
                    "credit_card",            -- must be one of the ENUM options
                    "pending"
               FROM hiring_records
              WHERE hiring_id = ?'
        );
        $pay->bind_param('i', $hiring_id);
        $pay->execute();
        $pay->close();

        $msg = 'Work ended — payment queued for user approval!';
    }

    /* ── 4. Commit & respond ───────────────────────────────── */
    $conn->commit();

    if ($isAjax) { echo json_encode(['success'=>true,'message'=>$msg]); exit; }
    $_SESSION['success_message'] = $msg;
    header('Location: helperdashboard.php'); exit;

} catch (Exception $e) {

    $conn->rollback();
    $msg = $e->getMessage();

    if ($isAjax) { echo json_encode(['success'=>false,'message'=>$msg]); exit; }
    $_SESSION['error_message'] = $msg;
    header('Location: helperdashboard.php'); exit;
}
