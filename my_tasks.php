<?php
/* ────────────────────────────────────────────────────────────────
   my_tasks.php - Displays a full list of tasks based on status
   ──────────────────────────────────────────────────────────────── */
session_start();
include 'dbconnect.php';

// --- 1. Authentication & Initialization ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- 2. Get and Validate Status from URL ---
// Default to 'in_progress' if not set, for safety.
$status_filter = $_GET['status'] ?? 'in_progress';

// Whitelist allowed statuses to prevent SQL injection or errors
$allowed_statuses = ['in_progress', 'completed', 'open'];
if (!in_array($status_filter, $allowed_statuses)) {
    // If an invalid status is provided, redirect to the dashboard
    $_SESSION['error_message'] = "Invalid task category specified.";
    header('Location: userdashboard.php');
    exit;
}

// --- 3. Fetch all tasks based on the status filter ---
$tasks_sql = "
    SELECT
        hr.hiring_id,
        t.title,
        t.description,
        h.name AS helpmate_name,
        h.profile_photo AS helpmate_photo,
        hr.start_time,
        UNIX_TIMESTAMP(hr.start_time) AS start_timestamp,
        hr.status
    FROM hiring_records hr
    JOIN tasks t ON hr.task_id = t.task_id
    JOIN helpers h ON hr.helper_id = h.helper_id
    WHERE hr.user_id = ? AND hr.status = ?
    ORDER BY hr.start_time DESC
";

// Special case for 'open' tasks which don't have a hiring_record yet
if ($status_filter === 'open') {
    $tasks_sql = "
        SELECT
            t.task_id,
            t.title,
            t.description,
            t.status,
            t.created_at
        FROM tasks t
        WHERE t.user_id = ? AND t.status = 'open'
        ORDER BY t.created_at DESC
    ";
}

$tasks_stmt = $conn->prepare($tasks_sql);

if ($status_filter === 'open') {
    $tasks_stmt->bind_param("i", $user_id);
} else {
    $tasks_stmt->bind_param("is", $user_id, $status_filter);
}

$tasks_stmt->execute();
$tasks = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks_stmt->close();


// --- 4. Prepare Page Title ---
$page_title = "My " . ucfirst(str_replace('_', ' ', $status_filter)) . " Tasks";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100">

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Page Header -->
        <header class="mb-8">
            <a href="userdashboard.php" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-900 font-semibold text-sm transition-colors mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Dashboard
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900"><?php echo htmlspecialchars($page_title); ?></h1>
        </header>

        <!-- Task List -->
        <main class="space-y-4">
            <?php if (empty($tasks)): ?>
                <div class="text-center bg-white p-12 rounded-lg border border-dashed border-slate-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No tasks found</h3>
                    <p class="mt-1 text-sm text-gray-500">You do not have any tasks with this status.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm transition hover:shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <!-- Task and Helper Info -->
                            <div class="flex items-center gap-4 flex-grow">
                                <?php if ($status_filter !== 'open'): ?>
                                <img src="<?php echo htmlspecialchars($task['helpmate_photo'] ?? 'img/default-avatar.png'); ?>" alt="HelpMate Photo" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                                <?php endif; ?>
                                <div>
                                    <h2 class="font-bold text-lg text-slate-800"><?php echo htmlspecialchars($task['title']); ?></h2>
                                    <?php if ($status_filter !== 'open'): ?>
                                    <p class="text-sm text-slate-500">with <strong class="font-medium"><?php echo htmlspecialchars($task['helpmate_name']); ?></strong></p>
                                    <?php else: ?>
                                    <p class="text-sm text-slate-500">Posted on <?php echo date('F j, Y', strtotime($task['created_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status/Timer Info -->
                            <div class="flex-shrink-0 text-right">
                                <?php if ($task['status'] === 'in_progress'): ?>
                                    <?php if ($task['start_time']): ?>
                                        <p class="text-xs text-green-600 font-semibold">Work In Progress</p>
                                        <p class="font-mono text-2xl text-slate-700" data-start-timestamp="<?php echo $task['start_timestamp']; ?>">00:00:00</p>
                                    <?php else: ?>
                                        <p class="text-sm font-semibold text-amber-600">Awaiting HelpMate to Start</p>
                                    <?php endif; ?>
                                <?php elseif ($task['status'] === 'open'): ?>
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Open for Applicants</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Live HH:MM:SS timers
            document.querySelectorAll('[data-start-timestamp]').forEach(el => {
                const start = parseInt(el.dataset.startTimestamp, 10) * 1000;
                if (isNaN(start)) return;

                // Set interval to update the timer every second
                setInterval(() => {
                    const diff = Date.now() - start;
                    if (diff < 0) return;

                    const hrs = Math.floor(diff / 3.6e6);
                    const mins = Math.floor((diff % 3.6e6) / 6e4);
                    const secs = Math.floor((diff % 6e4) / 1000);

                    el.textContent =
                        (hrs < 10 ? '0' : '') + hrs + ':' +
                        (mins < 10 ? '0' : '') + mins + ':' +
                        (secs < 10 ? '0' : '') + secs;
                }, 1000);
            });
        });
    </script>
</body>
</html>