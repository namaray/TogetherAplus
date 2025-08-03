<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Authorization Check ---
$is_admin = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'moderator']));


// --- Handle Feedback Messages for Feedback ---
$feedback_message = $_SESSION['feedback_message'] ?? null;
$feedback_type = $_SESSION['feedback_type'] ?? null;
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);


// --- Handle Resource Deletion (Admin Only) ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource_id'])) {
    $resource_id_to_delete = filter_input(INPUT_POST, 'delete_resource_id', FILTER_VALIDATE_INT);
    
    if ($resource_id_to_delete) {
        $conn->begin_transaction();
        try {
            $path_stmt = $conn->prepare("SELECT link FROM resources WHERE resource_id = ?");
            $path_stmt->bind_param("i", $resource_id_to_delete);
            $path_stmt->execute();
            $file_result = $path_stmt->get_result();
            
            if ($file_row = $file_result->fetch_assoc()) {
                $file_path = $file_row['link'];
                
                $delete_stmt = $conn->prepare("DELETE FROM resources WHERE resource_id = ?");
                $delete_stmt->bind_param("i", $resource_id_to_delete);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                if (file_exists($file_path) && is_file($file_path)) {
                    unlink($file_path);
                }
            }
            $path_stmt->close();
            
            $conn->commit();
            $_SESSION['feedback_message'] = "Resource deleted successfully!";
            $_SESSION['feedback_type'] = 'success';
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $_SESSION['feedback_message'] = "Error deleting resource: " . $e->getMessage();
            $_SESSION['feedback_type'] = 'error';
        }
        header('Location: resources.php');
        exit;
    }
}

// --- Fetch all approved resources for display ---
$sql = "SELECT resource_id, name, category, description, link FROM resources ORDER BY created_at DESC";
$result = $conn->query($sql);
$resources = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Use your PNG logos for each category
$category_logos = [
    'video' => 'img/resource1.png',
    'audio' => 'img/audio.png',
    'tutorial' => 'img/tutorial.png'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Library - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .resource-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .resource-card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.1), 0 8px 10px -6px rgba(79, 70, 229, 0.1); }
        .fade-in { animation: fadeIn 0.8s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .filter-btn.active { background-color: #4f46e5; color: white; }
    </style>
</head>
<body class="bg-slate-100">
    
    <!-- Notification Area -->
    <div id="notification-area" class="fixed top-5 right-5 z-50">
        <?php if ($feedback_message): ?>
        <div class="notification rounded-md p-4 shadow-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?>" role="alert">
            <p class="font-bold"><?php echo ucfirst($feedback_type); ?></p>
            <p><?php echo htmlspecialchars($feedback_message); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <header class="fade-in mb-10 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Resource Library</h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-slate-600">A curated collection of tutorials, guides, and helpful materials for our community.</p>
            <?php if ($is_admin): ?>
                <a href="upload_resource.php" class="mt-6 inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                    Upload New Resource
                </a>
            <?php endif; ?>
        </header>

        <!-- Filters -->
        <div class="fade-in mb-8 flex justify-center gap-2" style="animation-delay: 100ms;">
            <button class="filter-btn active px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-50" data-filter="all">All</button>
            <button class="filter-btn px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-50" data-filter="video">Videos</button>
            <button class="filter-btn px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-50" data-filter="audio">Audio</button>
            <button class="filter-btn px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-50" data-filter="tutorial">Tutorials</button>
        </div>
        
        <!-- Resource Grid -->
        <div class="resource-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($resources)): ?>
                <?php foreach ($resources as $index => $row): ?>
                    <div class="resource-card fade-in bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col p-6" data-category="<?php echo htmlspecialchars($row['category']); ?>" style="animation-delay: <?php echo ($index * 50) + 200; ?>ms;">
                        <div class="flex-grow">
                             <div class="w-16 h-16 flex items-center justify-center rounded-lg bg-indigo-100 mb-4">
                                <img src="<?php echo htmlspecialchars($category_logos[$row['category']] ?? 'img/guides.png'); ?>" alt="<?php echo htmlspecialchars($row['category']); ?> icon" class="h-8 w-8">
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600"><?php echo htmlspecialchars($row['category']); ?></span>
                            <h3 class="font-bold text-lg text-slate-900 mt-1"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="text-sm text-slate-500 mt-2 mb-4 h-16 overflow-hidden"><?php echo htmlspecialchars($row['description']); ?></p>
                        </div>
                        <div class="mt-auto pt-4 flex items-center justify-center gap-2">
                             <button class="view-resource-btn flex-1 text-center font-semibold text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700" data-link="<?php echo htmlspecialchars($row['link']); ?>" data-title="<?php echo htmlspecialchars($row['name']); ?>" data-type="<?php echo htmlspecialchars($row['category']); ?>">View Resource</button>
                            <?php if ($is_admin): ?>
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="delete_resource_id" value="<?php echo $row['resource_id']; ?>">
                                    <button type="submit" class="delete-button p-2 rounded-lg bg-red-100 text-red-700 hover:bg-red-200">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-full text-center text-slate-500 py-10">No resources have been uploaded yet.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Resource Viewer Modal -->
    <div id="resource-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl flex flex-col">
            <header class="flex items-center justify-between p-4 border-b border-slate-200">
                <h3 id="modal-title" class="text-lg font-bold text-slate-900">Resource Title</h3>
                <button id="modal-close-btn" class="p-1 rounded-full hover:bg-slate-200">
                     <svg class="w-6 h-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </header>
            <div id="modal-content" class="p-4 bg-slate-200 flex-grow flex items-center justify-center">
                <!-- Media will be injected here -->
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-slate-900">Confirm Deletion</h3>
            <p class="mt-2 text-sm text-slate-600">Are you sure you want to delete this resource? This action cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="modal-cancel-btn-delete" class="px-4 py-2 text-sm font-semibold bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300">Cancel</button>
                <button id="modal-delete-btn" class="px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded-md hover:bg-red-700">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Animation Observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible', 'fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

        // Notification auto-hide
        const notificationArea = document.getElementById('notification-area');
        if (notificationArea && notificationArea.children.length > 0) {
            setTimeout(() => {
                const notification = notificationArea.children[0];
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
        
        // Filter Logic
        const filterButtons = document.querySelectorAll('.filter-btn');
        const resourceCards = document.querySelectorAll('.resource-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                const filter = button.dataset.filter;
                
                resourceCards.forEach(card => {
                    card.style.display = 'none'; // Hide all first
                    if (filter === 'all' || card.dataset.category === filter) {
                        card.style.display = 'flex'; // Then show matching ones
                    }
                });
            });
        });

        // Resource Viewer Modal Logic
        const resourceModal = document.getElementById('resource-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalContent = document.getElementById('modal-content');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const modalBackdrop = document.getElementById('modal-backdrop');

        function openResourceModal(title, type, link) {
            modalTitle.textContent = title;
            modalContent.innerHTML = ''; // Clear previous content

            let mediaElement;
            if (type === 'video') {
                mediaElement = document.createElement('video');
                mediaElement.src = link;
                mediaElement.controls = true;
                mediaElement.autoplay = true;
                mediaElement.className = 'max-w-full max-h-[70vh] rounded-lg';
            } else if (type === 'audio') {
                mediaElement = document.createElement('audio');
                mediaElement.src = link;
                mediaElement.controls = true;
                mediaElement.autoplay = true;
            } else { // tutorial (image)
                mediaElement = document.createElement('img');
                mediaElement.src = link;
                mediaElement.className = 'max-w-full max-h-[70vh] rounded-lg object-contain';
            }
            modalContent.appendChild(mediaElement);
            resourceModal.classList.remove('hidden');
            resourceModal.classList.add('flex');
        }

        function closeResourceModal() {
            modalContent.innerHTML = ''; // Stop media playback by removing the element
            resourceModal.classList.add('hidden');
            resourceModal.classList.remove('flex');
        }

        document.querySelectorAll('.view-resource-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const { title, type, link } = e.currentTarget.dataset;
                openResourceModal(title, type, link);
            });
        });
        
        modalCloseBtn.addEventListener('click', closeResourceModal);
        modalBackdrop.addEventListener('click', closeResourceModal);


        // Delete Modal Logic
        const deleteModal = document.getElementById('delete-modal');
        if (deleteModal) {
            const cancelBtnDel = document.getElementById('modal-cancel-btn-delete');
            const deleteBtn = document.getElementById('modal-delete-btn');
            let formToSubmit = null;

            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    formToSubmit = this;
                    deleteModal.classList.remove('hidden');
                    deleteModal.classList.add('flex');
                });
            });

            function closeDeleteModal() {
                deleteModal.classList.add('hidden');
                deleteModal.classList.remove('flex');
                formToSubmit = null;
            }

            cancelBtnDel.addEventListener('click', closeDeleteModal);
            deleteBtn.addEventListener('click', () => {
                if (formToSubmit) {
                    formToSubmit.submit();
                }
            });
        }

    });
    </script>
</body>
</html>
