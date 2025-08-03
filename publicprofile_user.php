<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- 1. Get Profile ID & Fetch User Data ---
$user_id_to_view = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id_to_view) {
    die("Invalid user profile requested.");
}

$user_stmt = $conn->prepare("SELECT name, email, profile_photo, address, created_at FROM users WHERE user_id = ? AND status = 'active' AND verification_status = 'verified'");
$user_stmt->bind_param("i", $user_id_to_view);
$user_stmt->execute();
$result = $user_stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found or is not active.");
}
$user_details = $result->fetch_assoc();
$user_stmt->close();

// --- 2. Fetch User Stats ---
$stats_sql = "
    SELECT
        (SELECT COUNT(*) FROM tasks WHERE user_id = ?) AS total_posted,
        (SELECT COUNT(*) FROM hiring_records WHERE user_id = ? AND status = 'completed') AS total_completed
";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ii", $user_id_to_view, $user_id_to_view);
$stats_stmt->execute();
$user_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// --- 3. Fetch User's Trusted Contacts ---
$contacts_sql = "SELECT name, phone_number, relationship FROM trusted_contacts WHERE user_id = ?";
$contacts_stmt = $conn->prepare($contacts_sql);
$contacts_stmt->bind_param("i", $user_id_to_view);
$contacts_stmt->execute();
$trusted_contacts = $contacts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$contacts_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_details['name']); ?>'s Profile - TogetherA+</title>
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
                        <img class="h-32 w-32 rounded-full object-cover border-4 border-slate-200" src="<?php echo htmlspecialchars($user_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                        <h2 class="text-xl font-bold text-slate-900 mt-4"><?php echo htmlspecialchars($user_details['name']); ?></h2>
                        <p class="text-sm text-slate-500">Member since <?php echo date('F Y', strtotime($user_details['created_at'])); ?></p>
                        <?php if(!empty($user_details['address'])): ?>
                        <p class="mt-2 text-sm text-slate-500 flex items-center gap-2">
                             <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            <?php echo htmlspecialchars($user_details['address']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200 space-y-2">
                        <h4 class="font-semibold text-sm text-slate-600 mb-2">Activity</h4>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Tasks Posted</span>
                            <span class="font-bold text-slate-800"><?php echo $user_stats['total_posted'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Tasks Completed</span>
                            <span class="font-bold text-slate-800"><?php echo $user_stats['total_completed'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Right Column: Trusted Contacts -->
            <main class="lg:col-span-2">
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-6">
                    <h3 class="text-xl font-bold text-slate-800">Trusted Contacts</h3>
                    <?php if (empty($trusted_contacts)): ?>
                        <p class="text-center text-slate-500 py-8">This user has not listed any trusted contacts.</p>
                    <?php else: ?>
                        <ul class="space-y-4">
                        <?php foreach ($trusted_contacts as $contact): ?>
                            <li class="p-4 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600">
                                        <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($contact['name']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($contact['relationship']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono text-slate-600"><?php echo htmlspecialchars($contact['phone_number']); ?></p>
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
