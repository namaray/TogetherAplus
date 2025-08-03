<?php
session_start();
// This assumes 'dbconnect.php' is in the same directory and handles the database connection.
// Make sure it establishes a connection to a variable named $conn.
include 'dbconnect.php'; 

// --- Authentication and Authorization ---
// If user is not logged in, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if the user is active and verified before allowing them to post a task
$user_check_stmt = $conn->prepare("SELECT status, verification_status FROM users WHERE user_id = ?");
$user_check_stmt->bind_param("i", $user_id);
$user_check_stmt->execute();
$user_result = $user_check_stmt->get_result();

if ($user_result->num_rows === 0) {
    // It's good practice to handle cases where the user ID in session is invalid.
    session_destroy();
    header('Location: login.php');
    exit;
}

$user = $user_result->fetch_assoc();
// If the account is not active and verified, prevent task posting.
if ($user['status'] !== 'active' || $user['verification_status'] !== 'verified') {
    // In a real application, you might redirect to a page with a more friendly error.
    die("Error: Your account must be active and verified to post a new task. Please contact support.");
}
$user_check_stmt->close();

$form_error = '';
$form_success = '';

// --- Form Submission Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $title = trim($_POST['task-title']);
    $description = trim($_POST['description']);
    // Use null coalescing operator for safety, in case the field isn't submitted.
    $skill_required = trim($_POST['skill_required'] ?? ''); 
    $hourly_rate = filter_input(INPUT_POST, 'hourly-rate', FILTER_VALIDATE_FLOAT);
    $urgency = trim($_POST['urgency'] ?? '');

    // --- Server-Side Validation ---
    if (empty($title) || empty($description) || empty($skill_required) || empty($urgency) || $hourly_rate === false) {
        $form_error = 'Please fill out all required fields correctly.';
    } elseif ($hourly_rate <= 0) {
        $form_error = 'The hourly rate must be a positive number.';
    } else {
        // --- Secure Database Insertion with Prepared Statements ---
        $query = "INSERT INTO tasks (user_id, title, description, skill_required, hourly_rate, urgency) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        // 'isssds' corresponds to the data types: i=integer, s=string, d=double
        $stmt->bind_param("isssds", $user_id, $title, $description, $skill_required, $hourly_rate, $urgency);

        if ($stmt->execute()) {
            // Success: Set a session flash message and redirect to the user portal.
            $_SESSION['task_posted_success'] = "Your task has been successfully posted!";
            header('Location: userdashboard.php');
            exit;
        } else {
            // Handle potential database errors
            $form_error = "Error: Unable to post task. Please try again later.";
            // For debugging in a development environment, you might log the specific error.
            // error_log("Task post error: " . $stmt->error);
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a New Task - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Use a global font for the body */
        body { font-family: 'Inter', sans-serif; }
        
        /* Keyframe animations are fine to keep here */
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-100">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-5xl mx-auto fade-in">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
                
                <!-- Left Side: Information & Tips -->
                <div class="p-8 bg-indigo-700 text-white order-last md:order-first flex flex-col">
                    <div class="flex-grow">
                        <a href="userdashboard.php" class="text-sm font-semibold text-indigo-200 hover:text-white flex items-center gap-1 mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Back to Portal
                        </a>
                        <h1 class="text-3xl font-bold tracking-tight">Post a New Task</h1>
                        <p class="mt-4 text-indigo-200">Describe the support you need, and let our community of verified HelpMates find you.</p>
                        
                        <div class="mt-8 pt-6 border-t border-indigo-500 border-opacity-50 space-y-6">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0"><svg class="h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg></div>
                                <div>
                                    <h4 class="font-semibold">Be Specific</h4>
                                    <p class="text-sm text-indigo-200">Clearly describe the task, including any specific requirements or times.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0"><svg class="h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                <div>
                                    <h4 class="font-semibold">Offer a Fair Rate</h4>
                                    <p class="text-sm text-indigo-200">A competitive hourly rate will attract more qualified and experienced HelpMates.</p>
                                </div>
                            </div>
                             <div class="flex gap-4">
                                <div class="flex-shrink-0"><svg class="h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" /></svg></div>
                                <div>
                                    <h4 class="font-semibold">Safety First</h4>
                                    <p class="text-sm text-indigo-200">Remember, all HelpMates are verified by our platform for your peace of mind.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Form -->
                <div class="p-8">
                    <?php if ($form_error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-md mb-6" role="alert">
                            <p class="font-bold">Error</p>
                            <p><?php echo htmlspecialchars($form_error); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-8">
                        <div>
                            <label for="task-title" class="block text-sm font-semibold text-slate-800 mb-2">Task Title</label>
                            <input type="text" id="task-title" name="task-title" class="block w-full rounded-lg border-gray-300 bg-slate-100 py-3 px-4 text-slate-900 shadow-sm transition-colors duration-200 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50" placeholder="A clear and concise title" required />
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-semibold text-slate-800 mb-2">Description</label>
                            <textarea id="description" name="description" rows="4" class="block w-full rounded-lg border-gray-300 bg-slate-100 py-3 px-4 text-slate-900 shadow-sm transition-colors duration-200 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50" placeholder="Describe the task in detail..." required></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-2">Skill Category</label>
                            <div class="grid grid-cols-3 gap-3">
                                <?php 
                                $skills = [
                                    "Tutoring" => '<path d="M12 6.25278V19.2528M12 6.25278C10.8321 5.46388 9.23129 5.44496 8 6.25278C6.76871 7.06061 6.76871 8.68594 8 9.49377C9.23129 10.3016 10.8321 10.3205 12 9.49377M12 6.25278C13.1679 5.46388 14.7687 5.44496 16 6.25278C17.2313 7.06061 17.2313 8.68594 16 9.49377C14.7687 10.3016 13.1679 10.3205 12 9.49377M3 21.2528H21"/>',
                                    "Reading Assistance" => '<path d="M4 19.2528V5.25278C4 4.14821 4.89543 3.25278 6 3.25278H14.5L19 7.75278V19.2528C19 20.3574 18.1046 21.2528 17 21.2528H6C4.89543 21.2528 4 20.3574 4 19.2528Z"/>',
                                    "Mobility Support" => '<path d="M12 22.2528C17.5228 22.2528 22 17.7757 22 12.2528C22 6.72993 17.5228 2.25278 12 2.25278C6.47715 2.25278 2 6.72993 2 12.2528C2 17.7757 6.47715 22.2528 12 22.2528Z M12 12.2528H16 M12 12.2528V16.2528"/>',
                                    "Driving" => '<path d="M19 12.2528H21M17 17.2528L18 16.2528M7 7.25278L6 8.25278M5 12.2528H3M12 5.25278V3.25278M12 21.2528V19.2528M12 12.2528L7 17.2528L5 15.2528L9.5 10.7528L12 12.2528Z"/>',
                                    "Shopping" => '<path d="M3 3.25278H5.20371C5.66698 3.25278 6.06648 3.52989 6.22301 3.96781L10.223 15.9678C10.3795 16.4057 10.779 16.6828 11.2423 16.6828H17.5M7 21.2528C7.55228 21.2528 8 20.8051 8 20.2528C8 19.7005 7.55228 19.2528 7 19.2528C6.44772 19.2528 6 19.7005 6 20.2528C6 20.8051 6.44772 21.2528 7 21.2528ZM17 21.2528C17.5523 21.2528 18 20.8051 18 20.2528C18 19.7005 17.5523 19.2528 17 19.2528C16.4477 19.2528 16 19.7005 16 20.2528C16 20.8051 16.4477 21.2528 17 21.2528Z"/>',
                                    "Housekeeping" => '<path d="M12.9875 3.32832L21.3621 10.9571C21.7225 11.2863 21.8028 11.8091 21.5701 12.2458L16.8824 21.0519C16.6496 21.4886 16.1017 21.6705 15.6264 21.5201L4.85175 17.674C4.37648 17.5235 4.02987 17.0673 4.02987 16.568V4.88721C4.02987 4.38792 4.37648 3.93175 4.85175 3.78129L12.0125 1.41921C12.4878 1.26875 13.0357 1.45063 13.2684 1.88731L13.8447 2.96425C13.911 3.08558 13.8584 3.23232 13.7371 3.29865L3.84471 8.24531"/>',
                                    "Tech Support" => '<path d="M7 17.2528H17M7 21.2528H17M4 4.25278H20V13.2528C20 14.3574 19.1046 15.2528 18 15.2528H6C4.89543 15.2528 4 14.3574 4 13.2528V4.25278Z"/>',
                                    "Companion" => '<path d="M8 12.2528C9.65685 12.2528 11 10.9097 11 9.25278C11 7.59593 9.65685 6.25278 8 6.25278C6.34315 6.25278 5 7.59593 5 9.25278C5 10.9097 6.34315 12.2528 8 12.2528ZM16 12.2528C17.6569 12.2528 19 10.9097 19 9.25278C19 7.59593 17.6569 6.25278 16 6.25278C14.3431 6.25278 13 7.59593 13 9.25278C13 10.9097 14.3431 12.2528 16 12.2528ZM11.4011 15.5539C10.7028 15.8341 9.94827 16.0028 9.16641 16.0028C6.67876 16.0028 4.54922 14.7331 3.32422 12.8398M18.6758 12.8398C18.0622 13.8833 17.0911 14.7331 15.9082 15.2592"/>',
                                    "Other" => '<path d="M6 12.2528H18M12 6.25278V18.2528"/>'
                                ];
                                foreach ($skills as $skill => $svg_path): ?>
                                <div>
                                    <label>
                                        <input type="radio" name="skill_required" value="<?php echo htmlspecialchars($skill); ?>" class="sr-only peer" required>
                                        <div class="p-3 border-2 border-slate-200 rounded-lg cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-2 text-center hover:border-indigo-400 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-100 peer-checked:ring-2 peer-checked:ring-indigo-500 peer-checked:text-indigo-900">
                                            <svg class="h-8 w-8 text-slate-500 transition-colors duration-200 peer-checked:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><?php echo $svg_path; ?></svg>
                                            <span class="text-xs font-semibold text-slate-700 transition-colors duration-200 peer-checked:text-indigo-900"><?php echo htmlspecialchars($skill); ?></span>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-end">
                             <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-2">Urgency</label>
                                <div class="flex rounded-md shadow-sm">
                                    <label class="flex-1">
                                        <input type="radio" name="urgency" value="Low" class="sr-only peer" required>
                                        <div class="w-full text-center px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-l-md cursor-pointer transition-colors duration-200 hover:bg-slate-50 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:z-10">Low</div>
                                    </label>
                                    <label class="flex-1">
                                        <input type="radio" name="urgency" value="Medium" class="sr-only peer" checked>
                                        <div class="w-full text-center px-4 py-2 text-sm font-medium text-slate-600 bg-white border-y border-slate-300 cursor-pointer transition-colors duration-200 hover:bg-slate-50 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:z-10">Medium</div>
                                    </label>
                                    <label class="flex-1">
                                        <input type="radio" name="urgency" value="High" class="sr-only peer">
                                        <div class="w-full text-center px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-r-md cursor-pointer transition-colors duration-200 hover:bg-slate-50 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:z-10">High</div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label for="hourly-rate" class="block text-sm font-semibold text-slate-800 mb-2">Proposed Rate ($/hr)</label>
                                <div class="relative">
                                     <input type="number" id="hourly-rate" name="hourly-rate" class="block w-full rounded-lg border-gray-300 bg-slate-100 py-3 px-4 text-slate-900 shadow-sm transition-colors duration-200 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 text-center font-bold" value="25" step="1" min="10" max="100" required />
                                     <input type="range" id="rate-slider" min="10" max="100" value="25" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer mt-3">
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:scale-105">
                                Post Your Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // This IntersectionObserver is for the fade-in animation and can be kept as is.
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
            
            // Sync slider and number input for hourly rate
            const rateSlider = document.getElementById('rate-slider');
            const rateInput = document.getElementById('hourly-rate');

            rateSlider.addEventListener('input', (e) => {
                rateInput.value = e.target.value;
            });
            rateInput.addEventListener('input', (e) => {
                // Ensure the value doesn't go outside the min/max range
                if (parseInt(e.target.value) > 100) e.target.value = 100;
                if (parseInt(e.target.value) < 10) e.target.value = 10;
                rateSlider.value = e.target.value;
            });
        });
    </script>
</body>
</html>
