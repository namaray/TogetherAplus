<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- Authentication and Helper Data ---
if (!isset($_SESSION['helper_id'])) {
    header('Location: login.php');
    exit;
}
$helper_id = $_SESSION['helper_id'];

// --- Handle Flash Messages ---
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- 1. Fetch Helper's Profile ---
$helper_stmt = $conn->prepare("SELECT name, email, profile_photo, rating, skills FROM helpers WHERE helper_id = ?");
$helper_stmt->bind_param("i", $helper_id);
$helper_stmt->execute();
$helper_details = $helper_stmt->get_result()->fetch_assoc();
$helper_name = $helper_details['name'] ?? 'Helper';
$helper_rating = $helper_details['rating'] ?? 0;
$helper_skills = !empty($helper_details['skills']) ? array_map('trim', explode(',', $helper_details['skills'])) : [];
$helper_stmt->close();

// --- 2. Fetch Earnings & Stats ---
$stats_sql = "
    SELECT
        SUM(p.amount) as total_earnings,
        COUNT(DISTINCT hr.hiring_id) as completed_jobs
    FROM payments p
    JOIN hiring_records hr ON p.hiring_id = hr.hiring_id
    WHERE hr.helper_id = ? AND p.status = 'completed';
";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $helper_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result()->fetch_assoc();
$total_earnings = $stats_result['total_earnings'] ?? 0;
$completed_jobs = $stats_result['completed_jobs'] ?? 0;
$stats_stmt->close();

// --- 3. Fetch Available Tasks ---
$available_tasks_sql = "
    SELECT 
        t.task_id, t.title, t.description, t.skill_required, t.hourly_rate, t.urgency, t.created_at,
        u.name as user_name, u.profile_photo as user_photo
    FROM tasks t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.status = 'open' 
    AND t.task_id NOT IN (SELECT task_id FROM applications WHERE helper_id = ?)
    AND t.task_id NOT IN (SELECT task_id FROM hiring_decisions WHERE selected_helper_id = ?)
    ORDER BY t.created_at DESC;
";
$available_tasks_stmt = $conn->prepare($available_tasks_sql);
$available_tasks_stmt->bind_param("ii", $helper_id, $helper_id);
$available_tasks_stmt->execute();
$available_tasks = $available_tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$available_tasks_stmt->close();

// --- 4. Fetch Pending Job Offers ---
$offers_sql = "
    SELECT 
        hd.decision_id, t.title, t.description, t.hourly_rate, u.name as user_name
    FROM hiring_decisions hd
    JOIN tasks t ON hd.task_id = t.task_id
    JOIN users u ON t.user_id = u.user_id
    WHERE hd.selected_helper_id = ? AND hd.decision_status = 'pending';
";
$offers_stmt = $conn->prepare($offers_sql);
$offers_stmt->bind_param("i", $helper_id);
$offers_stmt->execute();
$pending_offers = $offers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$offers_stmt->close();

// --- 5. Fetch Applied Jobs ---
$applied_sql = "
    SELECT t.title, u.name as user_name
    FROM applications a
    JOIN tasks t ON a.task_id = t.task_id
    JOIN users u ON t.user_id = u.user_id
    WHERE a.helper_id = ? AND t.status = 'open'
    AND a.task_id NOT IN (SELECT task_id FROM hiring_decisions WHERE selected_helper_id = ?)
    ORDER BY a.created_at DESC
";
$applied_stmt = $conn->prepare($applied_sql);
$applied_stmt->bind_param("ii", $helper_id, $helper_id);
$applied_stmt->execute();
$applied_jobs = $applied_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$applied_stmt->close();

// --- 6. Fetch In-Progress Jobs for Timer ---
$in_progress_sql = "
    SELECT hr.hiring_id, t.title, u.name as user_name, u.user_id, hr.start_time, UNIX_TIMESTAMP(hr.start_time) as start_timestamp
    FROM hiring_records hr
    JOIN tasks t ON hr.task_id = t.task_id
    JOIN users u ON hr.user_id = u.user_id
    WHERE hr.helper_id = ? AND hr.status = 'in_progress'
    ORDER BY hr.start_time DESC
";
$in_progress_stmt = $conn->prepare($in_progress_sql);
$in_progress_stmt->bind_param("i", $helper_id);
$in_progress_stmt->execute();
$in_progress_jobs = $in_progress_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$in_progress_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helper Dashboard - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in-up { opacity: 0; transform: translateY(15px); transition: opacity 0.5s ease-out, transform 0.5s ease-out; }
        .fade-in-up.visible { opacity: 1; transform: translateY(0); }
        .notification { transition: opacity 0.5s, transform 0.5s; }
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
    </style>
</head>
<body class="bg-slate-100">
    
    <!-- Notification Area -->
    <div id="notification-area" class="fixed top-5 right-5 z-50">
        <?php if ($success_message): ?>
        <div class="notification bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg" role="alert">
            <p class="font-bold">Success</p>
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
        <?php elseif ($error_message): ?>
        <div class="notification bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Main Header -->
        <header class="fade-in-up mb-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome, <?php echo htmlspecialchars($helper_name); ?>!</h1>
                    <p class="mt-1 text-sm text-slate-500">Find new tasks and manage your jobs from your personal dashboard.</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="helper_profile.php?id=<?php echo $helper_id; ?>" class="hidden sm:inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 border border-slate-200">
                        Edit Profile
                    </a>
                    <a href="logout.php" class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Log Out
                    </a>
                </div>
            </div>
        </header>

        <!-- Stats and Profile Sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <aside class="lg:col-span-1 space-y-6">
                <!-- Profile Card -->
                <section class="fade-in-up bg-indigo-700 text-white p-6 rounded-xl shadow-lg" style="transition-delay: 100ms;">
                    <div class="flex items-center gap-4">
                        <img class="h-16 w-16 rounded-full object-cover border-2 border-indigo-400" src="<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                        <div>
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($helper_name); ?></h3>
                            <p class="text-sm text-indigo-200"><?php echo htmlspecialchars($helper_details['email']); ?></p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="font-semibold text-indigo-200">Overall Rating:</span>
                        <span class="font-bold text-amber-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            <?php echo number_format($helper_rating, 2); ?>
                        </span>
                    </div>
                     <div class="mt-4 pt-4 border-t border-indigo-600">
                        <h4 class="font-semibold text-sm text-indigo-200 mb-2">My Skills</h4>
                        <div class="flex flex-wrap gap-2">
                           <?php foreach($helper_skills as $skill): ?>
                            <span class="bg-indigo-500 text-indigo-50 text-xs font-medium px-2 py-1 rounded-full"><?php echo htmlspecialchars($skill); ?></span>
                           <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Earnings -->
                <section class="fade-in-up bg-indigo-700 text-white p-6 rounded-xl shadow-lg" style="transition-delay: 200ms;">
                     <h3 class="font-semibold mb-2">My Stats</h3>
                     <div class="space-y-3">
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-indigo-200">Total Earnings</span>
                            <span class="text-2xl font-bold text-green-400">$<?php echo number_format($total_earnings, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-indigo-200">Completed Jobs</span>
                            <span class="text-2xl font-bold text-white"><?php echo $completed_jobs; ?></span>
                        </div>
                     </div>
                </section>
                
                 <!-- My Jobs Sidebar -->
                <section class="fade-in-up" style="transition-delay: 300ms;">
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm space-y-4">
                        <div>
                            <h4 class="font-semibold text-sm mb-2 text-slate-600">Applied Jobs</h4>
                            <ul id="applied-jobs-list" class="space-y-2">
                            <?php if(empty($applied_jobs)): ?>
                                <li id="no-applied-jobs" class="text-xs text-slate-400">No jobs applied for yet.</li>
                            <?php else: ?>
                                <?php foreach($applied_jobs as $job): ?>
                                    <li class="text-sm p-2 bg-slate-50 rounded-md"><strong><?php echo htmlspecialchars($job['title']); ?></strong> for <?php echo htmlspecialchars($job['user_name']); ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Main Content: Tasks and Offers -->
            <main class="lg:col-span-2 space-y-6">
                <!-- PENDING JOB OFFERS -->
    <section class="fade-in-up" style="transition-delay: 100ms;">
    <h2 class="text-lg font-semibold text-slate-800 mb-3">New Job Offers</h2>
     <?php if (empty($pending_offers)): ?>
        <div class="text-center bg-white p-8 rounded-lg border border-dashed border-slate-300">
             <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No Pending Offers</h3>
            <p class="mt-1 text-sm text-gray-500">When a user offers you a job directly, it will appear here.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach($pending_offers as $offer): ?>
            <div class="bg-white p-5 rounded-lg border-2 border-indigo-500 shadow-lg">
                <p class="text-sm text-slate-600"><strong class="text-indigo-700"><?php echo htmlspecialchars($offer['user_name']); ?></strong> has offered you a job:</p>
                <h3 class="font-bold text-lg text-slate-900 mt-1"><?php echo htmlspecialchars($offer['title']); ?></h3>
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-lg font-bold text-slate-800">$<?php echo number_format($offer['hourly_rate'], 2); ?><span class="font-normal text-sm text-slate-500">/hr</span></p>
                    
                    <div class="flex items-center gap-2">
                        <a href="decline_offer.php?decision_id=<?php echo $offer['decision_id']; ?>" class="text-xs font-semibold bg-red-100 text-red-700 px-3 py-1.5 rounded-full hover:bg-red-200">Reject</a>
                        <a href="accept_offer.php?decision_id=<?php echo $offer['decision_id']; ?>" class="text-sm font-semibold bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Accept Offer</a>
                    </div>
                    </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
                
                <!-- Active Jobs -->
                <section class="fade-in-up" style="transition-delay: 200ms;">
                    <h2 class="text-lg font-semibold text-slate-800 mb-3">Active Jobs</h2>
                     <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                        <?php if(empty($in_progress_jobs)): ?>
                            <p class="text-sm text-slate-400 text-center py-4">No jobs currently in progress.</p>
                        <?php else: ?>
                            <ul class="space-y-4">
                            <?php foreach($in_progress_jobs as $job): ?>
                                <li class="text-sm p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
 <div>
    <p class="font-bold text-blue-800"><?php echo htmlspecialchars($job['title']); ?></p>
    <div class="flex items-center text-xs mt-1 mb-3 sm:mb-0">
        <span class="text-blue-600">for <?php echo htmlspecialchars($job['user_name']); ?></span>
        
        <a href="publicprofile_user.php?id=<?php echo $job['user_id']; ?>" target="_blank" 
           class="ml-2 inline-flex items-center gap-1 bg-blue-100 text-blue-700 font-semibold px-2 py-0.5 rounded-full hover:bg-blue-200 transition-colors">
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" />
            </svg>
            Profile
        </a>
    </div>
</div>
                                        <div class="flex items-center gap-2 w-full sm:w-auto">
                                            <?php if (is_null($job['start_time'])): ?>
                                                <a href="handle_work_timer.php?hiring_id=<?php echo $job['hiring_id']; ?>&action=start" class="flex-1 text-center text-sm font-semibold bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700">Start Work</a>
                                            <?php else: ?>
                                                 <div class="text-center bg-white p-2 rounded-md flex-shrink-0">
                                                    <p class="text-xs text-slate-500">Time Elapsed</p>
                                                    <p class="font-mono text-sm text-slate-700" data-start-timestamp="<?php echo $job['start_timestamp']; ?>">00:00:00</p>
                                                </div>
                                                 <a href="handle_work_timer.php?hiring_id=<?php echo $job['hiring_id']; ?>&action=end" class="flex-1 text-center text-sm font-semibold bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700">End Work</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="fade-in-up" style="transition-delay: 300ms;">
                    <h2 class="text-lg font-semibold text-slate-800 mb-3">Available Tasks For You</h2>
                    <div id="available-tasks-container" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                        <?php if (empty($available_tasks)): ?>
                            <div class="text-center bg-white p-12 rounded-lg border border-dashed border-slate-300">
                                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No Available Tasks</h3>
                                <p class="mt-1 text-sm text-gray-500">Check back later for new opportunities.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($available_tasks as $task): ?>
                                <div class="task-card bg-white p-5 rounded-lg border border-slate-200 shadow-sm transition hover:shadow-md hover:border-slate-300" data-task-id="<?php echo $task['task_id']; ?>">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-3 mb-2">
                                                <img class="h-8 w-8 rounded-full object-cover" src="<?php echo htmlspecialchars($task['user_photo'] ?? 'img/default-avatar.png'); ?>" alt="User Photo">
                                                <span class="text-xs text-slate-500">Posted by <strong class="text-slate-700" data-user-name="<?php echo htmlspecialchars($task['user_name']); ?>"><?php echo htmlspecialchars($task['user_name']); ?></strong></span>
                                            </div>
                                            <h3 class="font-bold text-lg text-indigo-700" data-task-title="<?php echo htmlspecialchars($task['title']); ?>"><?php echo htmlspecialchars($task['title']); ?></h3>
                                            <p class="text-sm text-slate-600 mt-1 max-w-prose"><?php echo htmlspecialchars($task['description']); ?></p>
                                        </div>
                                        <div class="mt-4 sm:mt-0 sm:ml-6 text-right flex-shrink-0">
                                            <p class="text-2xl font-bold text-slate-900">$<?php echo number_format($task['hourly_rate'], 2); ?><span class="font-normal text-sm text-slate-500">/hr</span></p>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="bg-slate-200 text-slate-700 font-medium px-2 py-1 rounded-full"><?php echo htmlspecialchars($task['skill_required']); ?></span>
                                            <span class="bg-red-100 text-red-800 font-medium px-2 py-1 rounded-full"><?php echo htmlspecialchars($task['urgency']); ?></span>
                                        </div>
                                        <button class="apply-btn text-sm font-semibold bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Apply Now</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
    
    <!-- Application Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
<div class="modal-backdrop fixed inset-0 bg-black/30 backdrop-blur-sm"></div>        <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-slate-900">Confirm Application</h3>
            <p class="mt-2 text-sm text-slate-600">Do you want to apply for the task: <strong id="modal-task-title" class="text-indigo-700"></strong>?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="modal-cancel-btn" class="px-4 py-2 text-sm font-semibold bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300">Cancel</button>
                <button id="modal-confirm-btn" class="px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded-md hover:bg-green-700">Yes, Apply Now</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Observer & Notification logic ---
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));
            
            const notificationArea = document.getElementById('notification-area');
            if (notificationArea && notificationArea.children.length > 0) {
                setTimeout(() => {
                    const notification = notificationArea.children[0];
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-20px)';
                    setTimeout(() => notification.remove(), 500);
                }, 4000);
            }

            // --- Application Modal Logic ---
            const modal = document.getElementById('confirmation-modal');
            if(modal) {
                const modalTitle = document.getElementById('modal-task-title');
                const cancelBtn = document.getElementById('modal-cancel-btn');
                const confirmBtn = document.getElementById('modal-confirm-btn');
                const availableTasksContainer = document.getElementById('available-tasks-container');
                const appliedJobsList = document.getElementById('applied-jobs-list');
                const noAppliedJobsMsg = document.getElementById('no-applied-jobs');
                let currentTaskCard = null;

                if (availableTasksContainer) {
                    availableTasksContainer.addEventListener('click', (e) => {
                        if (e.target.classList.contains('apply-btn')) {
                            currentTaskCard = e.target.closest('.task-card');
                            const taskTitle = currentTaskCard.querySelector('[data-task-title]').dataset.taskTitle;
                            modalTitle.textContent = `"${taskTitle}"`;
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                        }
                    });
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentTaskCard = null;
                }

                cancelBtn.addEventListener('click', closeModal);
                modal.querySelector('.modal-backdrop').addEventListener('click', closeModal);

                confirmBtn.addEventListener('click', () => {
                    if (!currentTaskCard) return;
                    const taskId = currentTaskCard.dataset.taskId;
                    const taskTitle = currentTaskCard.querySelector('[data-task-title]').dataset.taskTitle;
                    const userName = currentTaskCard.querySelector('[data-user-name]').dataset.userName;
                    
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Applying...';

                    fetch('handle_application.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: taskId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentTaskCard.remove();
                            if (noAppliedJobsMsg) noAppliedJobsMsg.remove();
                            const newLi = document.createElement('li');
                            newLi.className = 'text-sm p-2 bg-slate-50 rounded-md';
                            newLi.innerHTML = `<strong>${taskTitle}</strong> for ${userName}`;
                            appliedJobsList.prepend(newLi);
                            closeModal();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Yes, Apply Now';
                    });
                });
            }
            
            // --- Live Timer Logic ---
            document.querySelectorAll('[data-start-timestamp]').forEach(timerElement => {
                const startTime = parseInt(timerElement.dataset.startTimestamp, 10) * 1000;
                
                if(isNaN(startTime)) return;

                const timerInterval = setInterval(() => {
                    const now = new Date().getTime();
                    const distance = now - startTime;
                    
                    if (distance < 0) { timerElement.innerHTML = "00:00:00"; return; }

                    const hours = Math.floor(distance / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    timerElement.innerHTML = 
                        (hours < 10 ? "0" : "") + hours + ":" + 
                        (minutes < 10 ? "0" : "") + minutes + ":" + 
                        (seconds < 10 ? "0" : "") + seconds;
                }, 1000);
            });
        });
    </script>
</body>
</html>
