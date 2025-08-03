<?php
session_start();
include 'dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- FILTERING LOGIC ---
$search_query = $_GET['search'] ?? '';
$active_filter = $_GET['filter'] ?? 'all';
$min_rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$max_rate = isset($_GET['rate']) && $_GET['rate'] !== '' ? (float)$_GET['rate'] : null;

// --- DYNAMIC QUERY CONSTRUCTION ---

$sql_select = "
    SELECT
        h.helper_id,
        h.name,
        h.email,
        h.profile_photo,
        h.skills,
        h.address,
        h.rating,
        COUNT(DISTINCT hr.hiring_id) AS tasks_completed,
        (SELECT COUNT(*) FROM hiring_records hr2 WHERE hr2.helper_id = h.helper_id AND hr2.status = 'in_progress') = 0 AS is_available,
        (SELECT MIN(hr3.hourly_rate) FROM hiring_records hr3 WHERE hr3.helper_id = h.helper_id) as min_rate
";

$from_and_join = "FROM helpers h LEFT JOIN hiring_records hr ON h.helper_id = hr.helper_id";
$where_clauses = ["h.verification_status = 'verified'", "h.status = 'active'"];
$params = [];
$types = '';
$order_by = "ORDER BY is_available DESC, h.rating DESC, tasks_completed DESC";


// --- Apply filters based on the main category ---
if ($active_filter === 'previously_hired') {
    // For this specific filter, change the join and add a user-specific WHERE condition
    $from_and_join = "FROM helpers h INNER JOIN hiring_records hr ON h.helper_id = hr.helper_id";
    $where_clauses[] = "hr.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
    $order_by = "ORDER BY COUNT(DISTINCT hr.hiring_id) DESC, h.rating DESC"; // Order by most hired by this user
} elseif ($active_filter === 'new') {
    $where_clauses[] = "h.helper_id NOT IN (SELECT DISTINCT helper_id FROM hiring_records WHERE user_id = ?)";
    $params[] = $user_id;
    $types .= 'i';
}

// --- Apply additional text and range filters ---
if (!empty($search_query)) {
    $where_clauses[] = "(LOWER(h.name) LIKE ? OR LOWER(h.skills) LIKE ?)";
    $search_param = '%' . strtolower($search_query) . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($min_rating > 0) {
    $where_clauses[] = "h.rating >= ?";
    $params[] = $min_rating;
    $types .= 'd';
}

// --- Assemble The Query ---
$sql = $sql_select . " " . $from_and_join;

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " GROUP BY h.helper_id, h.name, h.email, h.profile_photo, h.skills, h.address, h.rating";

// --- Apply HAVING clauses (for aggregated columns like availability or rate) ---
$having_clauses = [];
if ($active_filter === 'available') {
    $having_clauses[] = "is_available = 1";
}
if ($max_rate !== null) {
    $having_clauses[] = "(min_rate <= ? OR min_rate IS NULL)";
    $params[] = $max_rate;
    $types .= 'd';
}

if (!empty($having_clauses)) {
    $sql .= " HAVING " . implode(' AND ', $having_clauses);
}

$sql .= " " . $order_by;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // This will help debug if the SQL is still wrong.
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$helpmates = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find HelpMates - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .filter-btn.active { background-color: #4f46e5; color: white; }
        #request-modal.hidden { display: none; }
    </style>
</head>
<body class="bg-slate-50">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div>
                <a href="userdashboard.php" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-900 font-semibold text-sm transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-extrabold text-slate-900 mt-2">Find Your Perfect HelpMate</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
            <aside class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-lg lg:sticky top-8">
                <form method="GET">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Filters</h2>
                    <div class="space-y-6">
                        <div>
                            <label for="search" class="block text-sm font-medium text-slate-700">Search by Name or Skill</label>
                            <input type="text" name="search" id="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="e.g., 'John' or 'Cooking'" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                             <div class="flex flex-wrap gap-2">
                                <button type="submit" name="filter" value="all" class="filter-btn flex-grow text-sm font-semibold py-2 px-3 rounded-md border border-slate-200 <?= $active_filter === 'all' ? 'active' : 'bg-white hover:bg-slate-100' ?>">All</button>
                                <button type="submit" name="filter" value="previously_hired" class="filter-btn flex-grow text-sm font-semibold py-2 px-3 rounded-md border border-slate-200 <?= $active_filter === 'previously_hired' ? 'active' : 'bg-white hover:bg-slate-100' ?>">Previously Hired</button>
                                <button type="submit" name="filter" value="available" class="filter-btn flex-grow text-sm font-semibold py-2 px-3 rounded-md border border-slate-200 <?= $active_filter === 'available' ? 'active' : 'bg-white hover:bg-slate-100' ?>">Available</button>
                                <button type="submit" name="filter" value="new" class="filter-btn flex-grow text-sm font-semibold py-2 px-3 rounded-md border border-slate-200 <?= $active_filter === 'new' ? 'active' : 'bg-white hover:bg-slate-100' ?>">New</button>
                            </div>
                        </div>
                        
                        <div>
                            <label for="rating" class="block text-sm font-medium text-slate-700">Minimum Rating</label>
                            <select name="rating" id="rating" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="0" <?= $min_rating == 0 ? 'selected' : '' ?>>Any Rating</option>
                                <option value="4" <?= $min_rating == 4 ? 'selected' : '' ?>>4 Stars & Up</option>
                                <option value="3" <?= $min_rating == 3 ? 'selected' : '' ?>>3 Stars & Up</option>
                                <option value="2" <?= $min_rating == 2 ? 'selected' : '' ?>>2 Stars & Up</option>
                                <option value="1" <?= $min_rating == 1 ? 'selected' : '' ?>>1 Star & Up</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="rate" class="block text-sm font-medium text-slate-700">Max Hourly Rate ($)</label>
                            <input type="number" name="rate" id="rate" value="<?= htmlspecialchars($max_rate ?? '') ?>" placeholder="e.g., 25" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2.5 px-4 rounded-lg shadow-md hover:bg-indigo-700">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </aside>

            <main class="lg:col-span-3">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php if (count($helpmates) > 0): ?>
                        <?php foreach ($helpmates as $helpmate): ?>
                            <div class="bg-white rounded-2xl shadow-lg flex flex-col overflow-hidden transition-transform hover:scale-105">
                                <div class="p-6">
                                    <div class="flex items-center gap-4">
                                        <img src="<?= htmlspecialchars($helpmate['profile_photo'] ?? 'img/default-avatar.png') ?>" alt="Profile of <?= htmlspecialchars($helpmate['name']) ?>" class="w-16 h-16 rounded-full object-cover">
                                        <div>
                                            <h3 class="font-bold text-lg text-slate-900"><?= htmlspecialchars($helpmate['name']) ?></h3>
                                            <div class="flex items-center gap-1 text-sm text-slate-600">
                                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span><?= number_format($helpmate['rating'], 2) ?></span>
                                                <span class="text-slate-400">(<?= $helpmate['tasks_completed'] ?> jobs)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-sm text-slate-500 space-y-2">
                                        <p class="line-clamp-2">Skills: <?= htmlspecialchars($helpmate['skills']) ?></p>
                                        <p><span class="font-semibold text-slate-600">Location:</span> <?= htmlspecialchars($helpmate['address']) ?></p>
                                    </div>
                                </div>
                                <div class="p-4 bg-slate-50 mt-auto flex items-center justify-between">
                                    <?php if ($helpmate['is_available']): ?>
                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Available</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unavailable</span>
                                    <?php endif; ?>
                                    <button onclick="openRequestModal(<?= $helpmate['helper_id'] ?>, '<?= htmlspecialchars(addslashes($helpmate['name'])) ?>')" class="font-semibold text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                                        Request Hire
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="md:col-span-2 xl:col-span-3 text-center py-16 bg-white rounded-2xl shadow-lg">
                            <h3 class="text-xl font-bold text-slate-800">No HelpMates Found</h3>
                            <p class="text-slate-500 mt-2">Try adjusting your filters to find more results.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <div id="request-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Send Hire Request to <span id="modal-helpmate-name"></span></h2>
                <button onclick="closeRequestModal()" class="text-slate-500 hover:text-slate-800">&times;</button>
            </div>
            <form action="direct_offer.php" method="POST" class="space-y-4">
                <input type="hidden" name="helper_id" id="modal-helpmate-id">
                
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Task Title</label>
                    <input type="text" name="title" id="title" required placeholder="e.g., 'Weekly Grocery Shopping'" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="skill_needed" class="block text-sm font-medium text-slate-700">Skills Required</label>
                    <input type="text" name="skill_needed" id="skill_needed" required placeholder="e.g., 'Driving, Communication'" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Task Description</label>
                    <textarea name="description" id="description" rows="4" required placeholder="Describe what you need help with..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                
                <div>
                    <label for="hourly_rate" class="block text-sm font-medium text-slate-700">Proposed Hourly Rate ($)</label>
                    <input type="number" name="hourly_rate" id="hourly_rate" required placeholder="e.g., 20" min="1" step="0.5" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                 <div>
                    <label for="urgency" class="block text-sm font-medium text-slate-700">Urgency</label>
                    <select name="urgency" id="urgency" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeRequestModal()" class="bg-white py-2 px-4 border border-slate-300 rounded-md shadow-sm text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-indigo-700">Send Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('request-modal');
        const helpmateNameEl = document.getElementById('modal-helpmate-name');
        const helpmateIdEl = document.getElementById('modal-helpmate-id');

        function openRequestModal(helpmateId, helpmateName) {
            helpmateIdEl.value = helpmateId;
            helpmateNameEl.textContent = helpmateName;
            modal.classList.remove('hidden');
        }

        function closeRequestModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>