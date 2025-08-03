<?php
/* ──────────────────────────────────────────────────────────────────
    userdashboard.php  –  TogetherA+ user portal
    ────────────────────────────────────────────────────────────────── */
session_start();
include 'dbconnect.php';

/* ───────────────── 1.  Auth & basic user data ─────────────────── */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$success_message = $_SESSION['success_message'] ?? null;
$error_message   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

/* ───────────────── 2.  Fetch name + profile photo ─────────────── */
$user_stmt = $conn->prepare(
    "SELECT name, profile_photo FROM users WHERE user_id = ?"
);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result   = $user_stmt->get_result()->fetch_assoc();
$user_name     = $user_result['name']          ?? 'User';
$profile_photo = $user_result['profile_photo'] ?? 'img/default-avatar.png';
$user_stmt->close();

// Count items in cart for the cart icon
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;


/* ───────────────── 3.  In-Progress tasks (for live timer) ─────── */
$inprogress_sql = "
    SELECT  t.task_id,
            t.title,
            h.name                 AS helpmate_name,
            hr.start_time,
            UNIX_TIMESTAMP(hr.start_time) AS start_timestamp
    FROM    hiring_records hr
    JOIN    tasks   t ON hr.task_id   = t.task_id
    JOIN    helpers h ON hr.helper_id = h.helper_id
    WHERE   hr.user_id = ?
      AND   hr.status  = 'in_progress'
    ORDER BY hr.start_time DESC
";
$inprogress_stmt = $conn->prepare($inprogress_sql);
$inprogress_stmt->bind_param("i", $user_id);
$inprogress_stmt->execute();
$in_progress_tasks = $inprogress_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inprogress_stmt->close();

$display_in_progress   = array_slice($in_progress_tasks, 0, 3);
$total_in_progress     = count($in_progress_tasks);

/* ───────────────── 4.  Tasks with pending applicants ──────────── */
$tasks_sql = "
    SELECT  t.task_id, t.title, t.description, t.created_at,
            COUNT(a.application_id) AS applicant_count
    FROM    tasks t
    JOIN    applications a
           ON t.task_id = a.task_id
          AND a.status  = 'pending'
    WHERE   t.user_id = ?
      AND   t.status  = 'open'
    GROUP BY t.task_id
    ORDER BY t.created_at DESC
";
$tasks_stmt = $conn->prepare($tasks_sql);
$tasks_stmt->bind_param("i", $user_id);
$tasks_stmt->execute();
$tasks_with_applicants = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks_stmt->close();

/* Ensure each task has an applicants array (prevents notices) */
foreach ($tasks_with_applicants as $k => $task) {
    if ($task['applicant_count'] > 0) {
        $app_sql = "
            SELECT  h.helper_id, h.name, h.rating, h.profile_photo
            FROM    applications a
            JOIN    helpers h ON a.helper_id = h.helper_id
            WHERE   a.task_id = ?
              AND   a.status  = 'pending'
        ";
        $app_stmt = $conn->prepare($app_sql);
        $app_stmt->bind_param("i", $task['task_id']);
        $app_stmt->execute();
        $tasks_with_applicants[$k]['applicants'] =
            $app_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $app_stmt->close();
    } else {
        $tasks_with_applicants[$k]['applicants'] = [];
    }
}

/* ───────────────── 5.  Open tasks with zero applicants ────────── */
$open_sql = "
    SELECT  t.task_id, t.title, t.description, t.created_at
    FROM    tasks t
    LEFT JOIN applications a ON t.task_id = a.task_id
    WHERE   t.user_id = ?
      AND   t.status  = 'open'
      AND   a.application_id IS NULL
    ORDER BY t.created_at DESC
";
$open_stmt = $conn->prepare($open_sql);
$open_stmt->bind_param("i", $user_id);
$open_stmt->execute();
$open_tasks = $open_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$open_stmt->close();

/* ───────────────── 6.  Pending payments ───────────────────────── */
$pay_sql = "
    SELECT  hr.hiring_id,
            (hr.logged_hours * hr.hourly_rate) AS payable_amount,
            h.name  AS helpmate_name,
            t.title AS task_title
    FROM    hiring_records hr
    JOIN    helpers h ON hr.helper_id = h.helper_id
    JOIN    tasks   t ON hr.task_id   = t.task_id
    WHERE   hr.user_id = ?
      AND   hr.status  = 'completed'
      AND   hr.user_confirmation = 'pending'
";
$pay_stmt = $conn->prepare($pay_sql);
$pay_stmt->bind_param("i", $user_id);
$pay_stmt->execute();
$pending_payments       = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pay_stmt->close();

$total_pending_payments = count($pending_payments);
$display_pending_payments = array_slice($pending_payments, 0, 3);


$pay_sql = "
    SELECT  hr.hiring_id,
            (hr.logged_hours * hr.hourly_rate) AS payable_amount,
            h.name  AS helpmate_name,
            t.title AS task_title
    FROM    hiring_records hr
    JOIN    helpers h ON hr.helper_id = h.helper_id
    JOIN    tasks   t ON hr.task_id   = t.task_id
    WHERE   hr.user_id = ?
      AND   hr.status  = 'completed'
      AND   hr.user_confirmation = 'pending'
";
$pay_stmt = $conn->prepare($pay_sql);
$pay_stmt->bind_param("i", $user_id);
$pay_stmt->execute();
$pending_payments       = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pay_stmt->close();

$total_pending_payments = count($pending_payments);
$display_pending_payments = array_slice($pending_payments, 0, 3);


/* ── Active (in_progress) jobs for this user ── */
$active = $conn->prepare("
    SELECT hr.hiring_id,
           hr.task_id,
           hr.helper_id,
           h.name            AS helper_name,
           t.title           AS task_title
    FROM   hiring_records hr
    JOIN   tasks   t ON t.task_id   = hr.task_id
    JOIN   helpers h ON h.helper_id = hr.helper_id
    WHERE  hr.user_id = ?
      AND  hr.status  = 'in_progress'
");
$active->bind_param("i", $user_id);
$active->execute();
$active_result = $active->get_result();
// --- START OF MODIFIED/ADDED PHP CODE ---
// --- NEW QUERY: Fetch completed tasks awaiting a review ---
$reviews_sql = "
    SELECT
        hr.hiring_id,
        t.title AS task_title,
        h.name AS helper_name
    FROM
        hiring_records hr
    JOIN
        tasks t ON hr.task_id = t.task_id
    JOIN
        helpers h ON hr.helper_id = h.helper_id
    LEFT JOIN
        reviews r ON hr.hiring_id = r.hiring_id
    WHERE
        hr.user_id = ?
        AND hr.status = 'completed'
        AND hr.user_confirmation = 'confirmed'
        AND r.review_id IS NULL
    ORDER BY
        hr.end_time DESC
";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $user_id);
$reviews_stmt->execute();
$pending_reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reviews_stmt->close();

/* ───────────────── 7.  Trusted contacts ───────────────────────── */
$cont_sql = "
    SELECT name, phone_number, relationship
    FROM   trusted_contacts
    WHERE  user_id = ?
";
$cont_stmt = $conn->prepare($cont_sql);
$cont_stmt->bind_param("i", $user_id);
$cont_stmt->execute();
$trusted_contacts = $cont_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cont_stmt->close();



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard – TogetherA+</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body{font-family:'Inter',sans-serif}
    .fade-in-up{opacity:0;transform:translateY(15px);transition:opacity .5s ease-out,transform .5s ease-out}
    .fade-in-up.visible{opacity:1;transform:translateY(0)}
    .applicant-list{transition:max-height .5s,opacity .5s,padding .5s;max-height:0;opacity:0;padding-top:0;padding-bottom:0;overflow:hidden}
    .applicant-list.open{max-height:1000px;opacity:1;padding-top:1rem;padding-bottom:1rem}
    .notification{transition:opacity .5s,transform .5s}
    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        height: 20px;
        width: 20px;
        background-color: #ef4444; /* red-500 */
        color: white;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
    .banner-close-btn {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: rgba(0,0,0,0.2);
        color: white;
        border-radius: 9999px;
        width: 1.75rem;
        height: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .banner-close-btn:hover {
        background: rgba(0,0,0,0.4);
    }

 /* --- START: Chatbot Styles --- */
.chat-launcher {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 3.5rem;
    height: 3.5rem;
    background-color: #4f46e5; /* indigo-600 */
    color: white;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    transition: transform 0.2s ease-out;
    z-index: 1000;
}
.chat-launcher:hover {
    transform: scale(1.1);
}
.chat-widget {
    position: fixed;
    bottom: 6.5rem; /* Position above the launcher */
    right: 2rem;
    width: 90%;
    max-width: 400px;
    height: 70vh;
    max-height: 600px; /* Increased height */
    background-color: white;
    border-radius: 0.75rem; /* 12px */
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    display: none; /* Hidden by default */
    flex-direction: column;
    overflow: hidden;
    z-index: 1000;
    border: 1px solid #e5e7eb; /* slate-200 */
}
.chat-widget.open {
    display: flex;
}
.chat-widget-header {
    background-color: #4f46e5; /* indigo-600 */
    color: white;
    padding: 0.75rem 1rem;
    font-weight: 600; /* semibold */
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}
.chat-widget-close {
    cursor: pointer;
    background: none;
    border: none;
    color: white;
    opacity: 0.8;
}
.chat-widget-close:hover {
    opacity: 1;
}
.chat-log {
    flex-grow: 1;
    padding: 1rem;
    overflow-y: auto;
    background-color: #f8fafc; /* slate-50 */
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
 /* Custom scrollbar for chat */
.chat-log::-webkit-scrollbar { width: 6px; }
.chat-log::-webkit-scrollbar-track { background: #f1f5f9; }
.chat-log::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.chat-log::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.chat-message { display: flex; align-items: flex-end; gap: 0.5rem; }
.chat-message .bubble { padding: 0.5rem 1rem; border-radius: 1rem; max-width: 80%; }
.chat-message.user { justify-content: flex-end; }
.chat-message.user .bubble { background-color: #4f46e5; color: white; border-bottom-right-radius: 0.25rem; }
.chat-message.bot .bubble { background-color: #e5e7eb; color: #1f2937; border-bottom-left-radius: 0.25rem; }
.chat-message.system { justify-content: center; }
.chat-message.system .bubble { background-color: #6b7280; color: white; font-size: 0.75rem; font-style: italic; padding: 0.25rem 0.75rem; }

.chat-input-area {
    padding: 0.75rem;
    border-top: 1px solid #e5e7eb; /* slate-200 */
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.chat-input {
    flex-grow: 1;
    border: 1px solid #d1d5db; /* gray-300 */
    padding: 0.5rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
}
.chat-input:focus { outline: 2px solid #4f46e5; outline-offset: 1px; }

/* Chat settings dropdown */
.chat-settings-menu {
    display: none;
    position: absolute;
    right: 1rem;
    top: 3.5rem;
    width: 280px;
    background-color: white;
    color: #1f2937;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    z-index: 1010;
    padding: 1rem;
    font-size: 0.875rem;
}
.chat-settings-menu.open { display: block; }
.tts-toggle-btn {
    position: relative; display: inline-flex; align-items: center;
    height: 1.5rem; width: 2.75rem; transition-colors: background-color 0.2s;
    border-radius: 9999px; background-color: #e5e7eb;
}
.tts-toggle-btn.enabled { background-color: #4f46e5; }
.tts-toggle-btn span {
    display: inline-block; width: 1.25rem; height: 1.25rem;
    transform: translateX(2px); background-color: white;
    border-radius: 9999px; transition: transform 0.2s;
}
.tts-toggle-btn.enabled span { transform: translateX(1.5rem); }

#chat-voice-btn.recording { color: #ef4444; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
/* --- END: Chatbot Styles --- */
</style>
</head>
<body class="bg-slate-100">

<!-- ────────────────── Notification toast ─────────────────── -->
<div id="notification-area" class="fixed top-5 right-5 z-50">
<?php if ($success_message): ?>
  <div class="notification bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg" role="alert">
      <p class="font-bold">Success</p><p><?=htmlspecialchars($success_message)?></p>
  </div>
<?php elseif ($error_message): ?>
  <div class="notification bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg" role="alert">
      <p class="font-bold">Error</p><p><?=htmlspecialchars($error_message)?></p>
  </div>
<?php endif; ?>
</div>

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
<!-- ────────────────── Header ────────────────────────────── -->
<header class="fade-in-up mb-8">
  <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
          <img src="<?=htmlspecialchars($profile_photo)?>" alt="Profile" class="h-14 w-14 rounded-full object-cover border-2 border-white shadow-md">
          <div>
              <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome, <?=htmlspecialchars($user_name)?>!</h1>
              <p class="mt-1 text-sm text-slate-500">This is your personal dashboard. Manage everything in one place.</p>
          </div>
      </div>
      <div class="flex items-center gap-4">
          <a href="marketplace.php" class="hidden sm:inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 border border-slate-200">Marketplace</a>
          <a href="view_cart.php" class="relative inline-flex items-center p-2 rounded-md bg-white text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 border border-slate-200">
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c.51 0 .962-.343 1.087-.835l1.838-5.514A1.875 1.875 0 0018.614 6H6.386a1.875 1.875 0 00-1.789 1.437L3.19 12M16.5 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm-7.5 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
              </svg>
              <?php if ($cart_count > 0): ?>
                  <span class="cart-badge"><?= $cart_count ?></span>
              <?php endif; ?>
          </a>
          <a href="task_posting.php" class="hidden sm:inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
              <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
              Post a New Task
          </a>
          <a href="logout.php" class="inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 border border-slate-200">Log Out</a>
      </div>
  </div>
</header>

<!-- ────────────────── Marketplace Banner ────────────────── -->
<section id="marketplace-banner" class="fade-in-up mb-6 relative" style="transition-delay:50ms;">
    <div class="bg-indigo-600 rounded-lg shadow-md overflow-hidden">
        <div class="p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-white">
                <h2 class="text-xl font-bold">Explore the Marketplace</h2>
                <p class="mt-1 text-indigo-100 text-sm">Find assistive devices and tools.</p>
            </div>
            <a href="marketplace.php" class="flex-shrink-0 inline-block bg-white text-indigo-600 font-semibold px-5 py-2 rounded-lg shadow-sm hover:bg-indigo-50 transition-transform hover:scale-105 text-sm">
                Browse Products &rarr;
            </a>
        </div>
    </div>
    <button id="close-banner-btn" class="banner-close-btn" aria-label="Close">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
</section>
<script>
    // This script checks if the banner was closed before and hides it on page load
    if (localStorage.getItem('marketplace_banner_closed') === 'true') {
        document.getElementById('marketplace-banner').style.display = 'none';
    }
</script>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

<!-- ────────────────── Main column ───────────────────────── -->
<main class="lg:col-span-2 space-y-6">

  <!-- ── In-Progress (compact, live timer) ───────────────── -->
  <section class="fade-in-up" style="transition-delay:100ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Tasks In Progress</h2>
    <?php if (empty($display_in_progress)): ?>
      <div class="text-center bg-white p-8 rounded-lg border border-dashed border-slate-300">
        <h3 class="mt-2 text-sm font-semibold text-gray-900">No Active Jobs</h3>
        <p class="mt-1 text-sm text-gray-500">Jobs that are currently in progress will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($display_in_progress as $job): ?>
        <div class="bg-white p-4 rounded-lg border border-blue-300 shadow-sm">
          <div class="flex justify-between items-center">
              <div>
                  <h3 class="font-bold text-blue-800"><?=htmlspecialchars($job['title'])?></h3>
                  <p class="text-sm text-slate-500">with <?=htmlspecialchars($job['helpmate_name'])?></p>
              </div>
              <?php if ($job['start_time']): ?>
              <div class="text-right">
                  <p class="text-xs text-green-600 font-semibold">Work In Progress</p>
                  <p class="font-mono text-sm text-slate-700"
                     data-start-timestamp="<?=$job['start_timestamp']?>">00:00:00</p>
              </div>
              <?php else: ?>
              <p class="text-sm font-semibold text-amber-600">Awaiting HelpMate to Start</p>
              <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($total_in_progress > 3): ?>
      <a href="my_tasks.php?status=in_progress"
         class="mt-3 w-full block text-center bg-slate-100 text-slate-800 text-sm font-semibold py-1.5 rounded-md hover:bg-slate-200">
         See All (<?=$total_in_progress?>)
      </a>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <!-- ── Review Applicants ───────────────────────────────── -->
  <section class="fade-in-up" style="transition-delay:200ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Review Applicants</h2>
    <?php if (empty($tasks_with_applicants)): ?>
      <div class="text-center bg-white p-8 rounded-lg border border-dashed border-slate-300">
        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <h3 class="mt-2 text-sm font-semibold text-gray-900">No Applicants Yet</h3>
        <p class="mt-1 text-sm text-gray-500">When HelpMates apply to your open tasks, they will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($tasks_with_applicants as $task): ?>
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
          <div class="p-4 flex justify-between items-center cursor-pointer"
               data-toggle="applicants-<?=$task['task_id']?>">
              <div>
                  <h3 class="font-semibold text-slate-900"><?=htmlspecialchars($task['title'])?></h3>
                  <p class="text-sm text-slate-500"><?=$task['applicant_count']?> HelpMate(s) have applied.</p>
              </div>
              <svg class="w-5 h-5 text-slate-400 transition-transform"
                   xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
          </div>
          <div id="applicants-<?=$task['task_id']?>" class="applicant-list border-t border-slate-200">
            <ul class="divide-y divide-slate-100">
              <?php foreach ($task['applicants'] as $applicant): ?>
              <li class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img class="h-10 w-10 rounded-full object-cover"
                         src="<?=htmlspecialchars($applicant['profile_photo'] ?? 'img/default-avatar.png')?>"
                         alt="">
                    <div>
                        <p class="font-semibold text-slate-800"><?=htmlspecialchars($applicant['name'])?></p>
                        <p class="text-xs text-slate-500">Rating: <?=number_format($applicant['rating'],2)?> ★</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="publicprofile_helper.php?id=<?=$applicant['helper_id']?>"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View Profile</a>
                    <a href="hire_helper.php?task_id=<?=$task['task_id']?>&helper_id=<?=$applicant['helper_id']?>"
                       class="text-xs font-semibold bg-green-600 text-white px-3 py-1 rounded-full hover:bg-green-700">Hire</a>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ── Tasks awaiting applicants ───────────────────────── -->
  <section class="fade-in-up" style="transition-delay:300ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Tasks Awaiting Applicants</h2>
    <?php if (empty($open_tasks)): ?>
      <div class="text-center bg-white p-8 rounded-lg border border-dashed border-slate-300">
        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <h3 class="mt-2 text-sm font-semibold text-gray-900">All Tasks Have Applicants</h3>
        <p class="mt-1 text-sm text-gray-500">Review the applications above to proceed.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($open_tasks as $task): ?>
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex justify-between items-center">
          <div>
              <h3 class="font-semibold text-slate-900"><?=htmlspecialchars($task['title'])?></h3>
              <p class="text-sm text-slate-500 truncate max-w-md"><?=htmlspecialchars($task['description'])?></p>
          </div>
          <span class="text-sm font-medium text-slate-400">0 Applicants</span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<!-- ────────────────── Sidebar ────────────────────────────── -->
<aside class="space-y-6">

  <!-- Pending payments ------------------------------------ -->
  <section class="fade-in-up" style="transition-delay:400ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Pending Payments</h2>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <?php if (empty($pending_payments)): ?>
        <p class="text-sm text-slate-500 text-center py-3">You have no payments awaiting confirmation.</p>
      <?php else: ?>
        <ul class="divide-y divide-slate-200">
          <?php foreach ($display_pending_payments as $pay): ?>
          <li class="py-2.5">
            <div class="flex justify-between items-center">
                <div>
                    <p class="font-medium text-sm text-slate-800"><?=htmlspecialchars($pay['task_title'])?></p>
                    <p class="text-xs text-slate-500">With <?=htmlspecialchars($pay['helpmate_name'])?></p>
                </div>
                <p class="font-bold text-slate-800">$<?=number_format($pay['payable_amount'],2)?></p>
            </div>
            <a href="confirm_payment.php?hiring_id=<?=$pay['hiring_id']?>"
               class="mt-2 w-full text-center block bg-green-100 text-green-800 text-xs font-semibold py-1.5 rounded-md hover:bg-green-200">Confirm &amp; Pay</a>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($total_pending_payments > 3): ?>
        <a href="pending_payments.php"
           class="mt-3 w-full block text-center bg-slate-100 text-slate-800 text-sm font-semibold py-1.5 rounded-md hover:bg-slate-200">
           See All (<?=$total_pending_payments?>)
        </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Quick Access ---------------------------------------- -->
  <section class="fade-in-up" style="transition-delay:500ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Quick Access</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-3">
      <a href="user_profile.php" class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm text-center hover:bg-slate-50 hover:border-slate-300 transition-all">
        <p class="font-semibold text-sm text-slate-800">My Profile</p>
      </a>
      <a href="payment.php"
         class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm text-center hover:bg-slate-50 hover:border-slate-300 transition-all">
        <p class="font-semibold text-sm text-slate-800">Payment History</p>
      </a>
      <a href="resources.php"
         class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm text-center hover:bg-slate-50 hover:border-slate-300 transition-all">
        <p class="font-semibold text-sm text-slate-800">Resource Repository</p>
      </a>
      <a href="recommended_helpers.php"
         class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm text-center hover:bg-slate-50 hover:border-slate-300 transition-all">
        <p class="font-semibold text-sm text-slate-800">Find HelpMates</p>
      </a>
    </div>
  </section>

  <!-- Trusted contacts ------------------------------------ -->
  <section class="fade-in-up" style="transition-delay:600ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">Trusted Contacts</h2>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <ul class="space-y-3">
        <?php if (empty($trusted_contacts)): ?>
          <p class="text-sm text-slate-500 text-center py-3">You haven't added any trusted contacts yet.</p>
        <?php else: ?>
          <?php foreach (array_slice($trusted_contacts,0,3) as $c): ?>
          <li class="flex items-center justify-between">
            <div>
              <p class="font-medium text-sm text-slate-800"><?=htmlspecialchars($c['name'])?></p>
              <p class="text-xs text-slate-500"><?=htmlspecialchars($c['relationship'])?></p>
            </div>
            <p class="text-xs font-mono text-slate-600"><?=htmlspecialchars($c['phone_number'])?></p>
          </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
      <a href="trusted_contacts.php"
         class="mt-3 w-full text-center block bg-slate-100 text-slate-800 text-sm font-semibold py-1.5 rounded-md hover:bg-slate-200">
         Manage Contacts
      </a>
    </div>
  </section>

  <section class="fade-in-up" style="transition-delay:150ms;">
  <h2 class="text-lg font-semibold text-slate-800 mb-3">Leave a Review</h2>

  <?php if (empty($pending_reviews)): ?>
    <!-- message when nothing to review -->
    <div class="text-center bg-white p-8 rounded-lg border border-dashed border-slate-300">
      <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
           viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
      </svg>
      <h3 class="mt-2 text-sm font-semibold text-gray-900">No Pending Reviews</h3>
      <p class="mt-1 text-sm text-gray-500">
        Completed tasks that are ready for your feedback will appear here.
      </p>
    </div>

  <?php else: ?>
    <!-- list of tasks awaiting review -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
      <ul class="divide-y divide-slate-200">
        <?php foreach ($pending_reviews as $item): ?>
          <li class="p-4 flex items-center justify-between">
            <div>
              <p class="font-semibold text-slate-900">
                <?= htmlspecialchars($item['task_title']) ?>
              </p>
              <p class="text-sm text-slate-500">
                Completed by <?= htmlspecialchars($item['helper_name']) ?>
              </p>
            </div>

            <a href="give_review.php?hiring_id=<?= $item['hiring_id'] ?>"
               class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3 py-1.5
                      text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
              Leave Review
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>


</aside>
</div> <!-- end grid -->
</div> <!-- end container -->
<<div id="chat-launcher" class="chat-launcher">
  <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
  </svg>
</div>

<div id="chat-widget" class="chat-widget">
  <div class="chat-widget-header">
    <div>
        <span class="font-bold">TogetherA+ Assistant</span>
        <p class="text-xs text-indigo-200">Online</p>
    </div>
    <div>
        <button id="chat-settings-btn" class="p-1 rounded-full hover:bg-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </button>
        <button id="chat-close-btn" class="chat-widget-close p-1 rounded-full hover:bg-indigo-500">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </button>
    </div>
  </div>
  <div id="chat-settings-menu" class="chat-settings-menu">
      <div class="flex items-center justify-between">
          <label for="tts-toggle-btn" class="font-medium">Enable Text-to-Speech</label>
          <button id="tts-toggle-btn" class="tts-toggle-btn">
              <span class="sr-only">Enable TTS</span>
              <span></span>
          </button>
      </div>
      <div class="mt-4">
          <label for="voice-select" class="block font-medium mb-1">Choose Voice:</label>
          <select id="voice-select" class="w-full p-2 border border-slate-300 rounded-md bg-white text-sm focus:ring-indigo-500 focus:border-indigo-500"></select>
      </div>
  </div>
  <div id="chat-log" class="chat-log">
    <div class="chat-message bot">
        <div class="bubble">Hello! How can I assist you with the TogetherA+ platform today?</div>
    </div>
  </div>
  <div class="chat-input-area">
    <button id="chat-voice-btn" title="Voice Input" class="p-2 rounded-full text-slate-500 hover:bg-slate-200 hover:text-indigo-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
    </button>
    <input type="text" id="chat-input" placeholder="Type your message..." class="chat-input">
    <button id="chat-send-btn" title="Send Message" class="p-2 rounded-full bg-indigo-600 text-white hover:bg-indigo-500 disabled:bg-indigo-300 disabled:cursor-not-allowed">
        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M3.105 11.4L17.43 4.23a.75.75 0 00-.96-1.04l-15 7.5a.75.75 0 000 1.34l3.14 1.57a.75.75 0 00.74-.16l5.32-4.04-4.66 5.82a.75.75 0 00.67 1.13l7.5-2.5a.75.75 0 000-1.34l-15-7.5a.75.75 0 00-.96 1.04l14.325 7.163-11.4-3.105z"/></svg>
    </button>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  /* Fade-in IntersectionObserver */
  const observer = new IntersectionObserver(entries=>{
    entries.forEach(e=>{
      if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target);}
    });
  },{threshold:0.1});
  document.querySelectorAll('.fade-in-up').forEach(el=>observer.observe(el));

  /* Auto-dismiss notifications */
  const noteArea=document.getElementById('notification-area');
  if(noteArea && noteArea.children.length){
    setTimeout(()=>{
      const n=noteArea.children[0];
      n.style.opacity='0'; n.style.transform='translateY(-20px)';
      setTimeout(()=>n.remove(),500);
    },4000);
  }

  /* Applicant accordion */
  document.querySelectorAll('[data-toggle]').forEach(toggler=>{
    const target=document.getElementById(toggler.dataset.toggle);
    const arrow=toggler.querySelector('svg');
    toggler.addEventListener('click',()=>{
      const isOpen=target.classList.toggle('open');
      arrow.classList.toggle('rotate-180',isOpen);
    });
  });

  /* Live HH:MM:SS timers */
  document.querySelectorAll('[data-start-timestamp]').forEach(el=>{
    const start=parseInt(el.dataset.startTimestamp,10)*1000;
    if(isNaN(start)) return;
    setInterval(()=>{
      const diff=Date.now()-start;
      const hrs=Math.floor(diff/3.6e6);
      const mins=Math.floor((diff%3.6e6)/6e4);
      const secs=Math.floor((diff%6e4)/1e3);
      el.textContent=
        (hrs<10?'0':'')+hrs+':' +
        (mins<10?'0':'')+mins+':' +
        (secs<10?'0':'')+secs;
    },1000);
  });

  /* Close marketplace banner */
  const closeBannerBtn = document.getElementById('close-banner-btn');
  const banner = document.getElementById('marketplace-banner');
  if(closeBannerBtn && banner){
      closeBannerBtn.addEventListener('click', () => {
          banner.style.transition = 'opacity 0.5s ease';
          banner.style.opacity = '0';
          setTimeout(() => {
              banner.style.display = 'none';
          }, 500);
          localStorage.setItem('marketplace_banner_closed', 'true');
      });
  }
});


// --- START: Chatbot Integration ---
document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elements ---
    const chatLauncher = document.getElementById('chat-launcher');
    const chatWidget = document.getElementById('chat-widget');
    const closeBtn = document.getElementById('chat-close-btn');
    const chatLog = document.getElementById('chat-log');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const voiceBtn = document.getElementById('chat-voice-btn');
    
    // Settings Elements
    const settingsBtn = document.getElementById('chat-settings-btn');
    const settingsMenu = document.getElementById('chat-settings-menu');
    const ttsToggleBtn = document.getElementById('tts-toggle-btn');
    const voiceSelect = document.getElementById('voice-select');

    // Function to toggle the chat widget's visibility
    const toggleChat = () => {
        chatWidget.classList.toggle('open');
        if (!chatWidget.classList.contains('open')) {
            settingsMenu.classList.remove('open'); // Close settings if chat closes
        }
    };

    // Add event listeners to open/close the chat
    chatLauncher.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);
    settingsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        settingsMenu.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
        if (!chatWidget.contains(e.target)) {
            settingsMenu.classList.remove('open');
        }
    });

    // --- Speech Recognition (Voice-to-Text) ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.continuous = false; // Stop after first result

        recognition.onresult = (event) => {
            const speechToText = event.results[0][0].transcript;
            handleSendMessage(speechToText);
        };
        
        recognition.onerror = (event) => {
            console.error("Voice recognition error:", event.error);
            appendChatMessage('system', `Voice error: ${event.error}`);
        };
        
        voiceBtn.addEventListener('click', () => {
            try {
                if (voiceBtn.classList.contains('recording')) {
                    recognition.stop();
                } else {
                    recognition.start();
                }
            } catch(e) {
                console.error("Could not start recognition:", e);
                appendChatMessage('system', "Couldn't start voice recognition. It might be already active or not supported.");
            }
        });
        
        recognition.onstart = () => {
            voiceBtn.classList.add('recording');
        };

        recognition.onend = () => {
            voiceBtn.classList.remove('recording');
        };

    } else {
        voiceBtn.disabled = true;
        voiceBtn.title = "Voice recognition not supported in this browser";
        voiceBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    // --- Text-to-Speech (TTS) ---
    let ttsEnabled = false;
    let voices = [];
    let chosenVoice = null;

    function populateVoiceList() {
        voices = speechSynthesis.getVoices();
        voiceSelect.innerHTML = '';
        
        if (!voices.length) {
            voiceSelect.innerHTML = '<option>No voices available</option>';
            return;
        }

        voices.forEach((voice, index) => {
            if(voice.lang.startsWith('en')) { // Filter for English voices
                const opt = document.createElement('option');
                opt.value = index;
                opt.textContent = `${voice.name} (${voice.lang})`;
                if (voice.default) {
                    opt.selected = true;
                }
                voiceSelect.appendChild(opt);
            }
        });
        // Set default chosen voice
        const defaultVoiceIndex = voiceSelect.selectedIndex;
        chosenVoice = voices[defaultVoiceIndex] || (voices.find(v => v.lang.startsWith('en'))) || null;
    }

    speechSynthesis.onvoiceschanged = populateVoiceList;
    populateVoiceList();

    ttsToggleBtn.addEventListener('click', () => {
        ttsEnabled = !ttsEnabled;
        ttsToggleBtn.classList.toggle('enabled', ttsEnabled);
        if (ttsEnabled) {
            speak("Text to speech enabled.");
        }
    });
    
    voiceSelect.addEventListener('change', () => {
        const selectedIndex = voiceSelect.value;
        if (selectedIndex && voices[selectedIndex]) {
            chosenVoice = voices[selectedIndex];
            if (ttsEnabled) {
                speak(`Voice changed to ${chosenVoice.name}.`);
            }
        }
    });

    function speak(text) {
        if (!ttsEnabled || !text) return;
        speechSynthesis.cancel(); 
        const utterance = new SpeechSynthesisUtterance(text);
        if (chosenVoice) {
            utterance.voice = chosenVoice;
        }
        speechSynthesis.speak(utterance);
    }

    // --- Core Chat Logic ---

    // Function to append a message to the chat log
    const appendChatMessage = (sender, text) => {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message', sender);
        
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        bubble.textContent = text;
        
        messageDiv.appendChild(bubble);
        chatLog.appendChild(messageDiv);
        chatLog.scrollTop = chatLog.scrollHeight;
    };

    const appendLoadingIndicator = () => {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-indicator';
        loadingDiv.classList.add('chat-message', 'bot');
        loadingDiv.innerHTML = `
            <div class="bubble flex items-center space-x-2">
                <div class="w-2 h-2 bg-slate-400 rounded-full animate-pulse" style="animation-delay: 0s;"></div>
                <div class="w-2 h-2 bg-slate-400 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                <div class="w-2 h-2 bg-slate-400 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
            </div>`;
        chatLog.appendChild(loadingDiv);
        chatLog.scrollTop = chatLog.scrollHeight;
    }

    // Function to handle sending a message
    const handleSendMessage = async (messageOverride = "") => {
        const message = messageOverride || chatInput.value.trim();
        if (!message) return;

        appendChatMessage('user', message);
        if(!messageOverride) chatInput.value = '';
        chatSendBtn.disabled = true;

        appendLoadingIndicator();

        try {
            const response = await fetch('chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) throw new Error(`Server error: ${response.statusText}`);

            const data = await response.json();
            const botReply = data.response || data.fallback_response || "Sorry, I'm having trouble connecting.";
            
            document.getElementById('loading-indicator')?.remove();
            appendChatMessage('bot', botReply);
            speak(botReply);

        } catch (err) {
            console.error("Chatbot fetch error:", err);
            document.getElementById('loading-indicator')?.remove();
            appendChatMessage('system', "Oops, something went wrong. Please try again.");
        } finally {
            chatSendBtn.disabled = false;
        }
    };

    // Event listeners for sending message
    chatSendBtn.addEventListener('click', () => handleSendMessage());
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSendMessage();
        }
    });
});
// --- END: Chatbot Integration ---
</script>


</body>
</html>
