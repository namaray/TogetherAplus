<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Handle Feedback Messages ---
$feedback_message = $_SESSION['feedback_message'] ?? null;
$feedback_type = $_SESSION['feedback_type'] ?? null;
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

// --- Handle Deletion of a Contact (with security checks) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contact_id'])) {
    $delete_contact_id = filter_input(INPUT_POST, 'delete_contact_id', FILTER_VALIDATE_INT);
    
    if ($delete_contact_id) {
        $sql_delete = "DELETE FROM trusted_contacts WHERE contact_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql_delete);
        // This ensures a user can only delete their OWN contacts.
        $stmt->bind_param("ii", $delete_contact_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['feedback_message'] = "Contact deleted successfully.";
            $_SESSION['feedback_type'] = 'success';
        } else {
            $_SESSION['feedback_message'] = "Error deleting contact.";
            $_SESSION['feedback_type'] = 'error';
        }
        $stmt->close();
        header('Location: trusted_contacts.php');
        exit;
    }
}

// --- Fetch all trusted contacts for the logged-in user ---
$sql = "SELECT contact_id, name, phone_number, relationship FROM trusted_contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$trusted_contacts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trusted Contacts - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.7s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
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

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <header class="fade-in mb-8">
             <a href="userdashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold flex items-center gap-1 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Dashboard
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Manage Trusted Contacts</h1>
            <p class="mt-1 text-slate-600">Add or remove trusted family members or friends who can be contacted in case of an emergency.</p>
        </header>

        <!-- Add New Contact Form -->
        <section class="fade-in bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8" style="animation-delay: 100ms;">
             <h2 class="text-lg font-bold text-slate-800 mb-4">Add a New Contact</h2>
             <form id="trusted-form" action="add_trusted_contact.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-1">
                    <label for="trusted-name" class="block text-sm font-medium text-slate-700">Full Name</label>
                    <input type="text" id="trusted-name" name="name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                <div class="md:col-span-1">
                    <label for="relationship" class="block text-sm font-medium text-slate-700">Relationship</label>
                    <input type="text" id="relationship" name="relationship" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                <div class="md:col-span-1">
                    <label for="trusted-phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
                    <input type="tel" id="trusted-phone" name="phone_number" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Add Contact</button>
                </div>
            </form>
        </section>

        <!-- Existing Contacts List -->
        <section class="fade-in" style="animation-delay: 200ms;">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Your Contacts</h2>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <ul class="divide-y divide-slate-200">
                <?php if (count($trusted_contacts) > 0): ?>
                    <?php foreach ($trusted_contacts as $contact): ?>
                        <li class="p-4 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600">
                                    <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($contact['name']); ?></p>
                                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($contact['relationship']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-mono text-slate-600"><?php echo htmlspecialchars($contact['phone_number']); ?></span>
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="delete_contact_id" value="<?php echo $contact['contact_id']; ?>">
                                    <button type="submit" class="p-2 rounded-full text-slate-400 hover:bg-red-100 hover:text-red-600">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="p-8 text-center text-slate-500">You haven't added any trusted contacts yet.</li>
                <?php endif; ?>
                </ul>
            </div>
        </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-slate-900">Confirm Deletion</h3>
            <p class="mt-2 text-sm text-slate-600">Are you sure you want to delete this contact? This action cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="modal-cancel-btn" class="px-4 py-2 text-sm font-semibold bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300">Cancel</button>
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
                    entry.target.classList.add('visible');
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

        // Delete Modal Logic
        const modal = document.getElementById('delete-modal');
        if (modal) {
            const cancelBtn = document.getElementById('modal-cancel-btn');
            const deleteBtn = document.getElementById('modal-delete-btn');
            let formToSubmit = null;

            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    formToSubmit = this;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                formToSubmit = null;
            }

            cancelBtn.addEventListener('click', closeModal);
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
