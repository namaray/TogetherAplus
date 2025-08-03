<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- 1. Get Profile ID & Fetch Helper Data ---
$helper_id_to_view = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$helper_id_to_view) {
    die("Invalid helper profile requested.");
}

$helper_stmt = $conn->prepare("SELECT name, email, profile_photo, rating, skills, created_at FROM helpers WHERE helper_id = ? AND status = 'active' AND verification_status = 'verified'");
$helper_stmt->bind_param("i", $helper_id_to_view);
$helper_stmt->execute();
$result = $helper_stmt->get_result();

if ($result->num_rows === 0) {
    die("Helper not found or is not active.");
}
$helper_details = $result->fetch_assoc();
$helper_skills = !empty($helper_details['skills']) ? array_map('trim', explode(',', $helper_details['skills'])) : [];
$helper_stmt->close();

// --- 2. Fetch Helper Stats ---
$stats_stmt = $conn->prepare("SELECT COUNT(*) as completed_jobs FROM hiring_records WHERE helper_id = ? AND status = 'completed'");
$stats_stmt->bind_param("i", $helper_id_to_view);
$stats_stmt->execute();
$helper_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// --- 3. Fetch Reviews for this Helper ---
$reviews_sql = "
    SELECT r.rating, r.comment, r.created_at, u.name as user_name, u.profile_photo as user_photo
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.helper_id = ?
    ORDER BY r.created_at DESC;
";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $helper_id_to_view);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reviews_stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($helper_details['name']); ?>'s Profile - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100">

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Profile Card -->
            <aside class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-lg sticky top-8">
                    <div class="flex flex-col items-center">
                        <img class="h-32 w-32 rounded-full object-cover border-4 border-slate-200" src="<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                        <h2 class="text-xl font-bold text-slate-900 mt-4"><?php echo htmlspecialchars($helper_details['name']); ?></h2>
                        <p class="text-sm text-slate-500">Member since <?php echo date('F Y', strtotime($helper_details['created_at'])); ?></p>
                        <div class="mt-4 flex divide-x divide-slate-200 rounded-lg border border-slate-200 text-center overflow-hidden">
                            <div class="px-4 py-2">
                                <p class="text-xs text-slate-500">Rating</p>
                                <p class="font-bold text-amber-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <?php echo number_format($helper_details['rating'], 2); ?>
                                </p>
                            </div>
                            <div class="px-4 py-2">
                                <p class="text-xs text-slate-500">Jobs Completed</p>
                                <p class="font-bold text-indigo-600"><?php echo $helper_stats['completed_jobs'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        <h4 class="font-semibold text-sm text-slate-600 mb-2">Skills</h4>
                        <div class="flex flex-wrap gap-2">
                           <?php if(empty($helper_skills)): ?>
                                <p class="text-xs text-slate-400">No skills listed.</p>
                           <?php else: ?>
                               <?php foreach($helper_skills as $skill): ?>
                                <span class="bg-indigo-100 text-indigo-700 text-xs font-medium px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($skill); ?></span>
                               <?php endforeach; ?>
                           <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Right Column: Reviews -->
            <main class="lg:col-span-2">
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-6">
                    <h3 class="text-xl font-bold text-slate-800">Feedback & Reviews</h3>
                    <?php if (empty($reviews)): ?>
                        <p class="text-center text-slate-500 py-8">This helper has not received any reviews yet.</p>
                    <?php else: ?>
                        <ul class="space-y-6">
                        <?php foreach($reviews as $review): ?>
                            <li class="flex gap-4">
                                <img class="h-10 w-10 rounded-full object-cover mt-1" src="<?php echo htmlspecialchars($review['user_photo'] ?? 'img/default-avatar.png'); ?>" alt="User photo">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($review['user_name']); ?></p>
                                        <span class="text-xs text-slate-400"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <svg class="w-4 h-4 <?php echo ($i < floor($review['rating'])) ? 'text-amber-400' : 'text-slate-300'; ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-slate-600 text-sm mt-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </main>

        </div>
    </main>
</body>
</html>
