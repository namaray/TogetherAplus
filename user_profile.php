<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Authentication and User Data ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$form_message = '';
$message_type = '';
$message_area = '';

// --- Handle Form Submissions (PHP Logic is Unchanged) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $form_message = "Invalid request. Please try again.";
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $message_area = 'profile';
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone_number']);
            $address = trim($_POST['address']);
            $update_fields = [];
            $params = [];
            $types = '';
            if (!empty($name)) { $update_fields[] = "name = ?"; $params[] = $name; $types .= 's'; }
            if (!empty($phone)) { $update_fields[] = "phone_number = ?"; $params[] = $phone; $types .= 's'; }
            if (!empty($address)) { $update_fields[] = "address = ?"; $params[] = $address; $types .= 's'; }
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profiles/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                $file_info = pathinfo($_FILES['profile_photo']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_ext, $allowed_exts)) {
                    $unique_name = uniqid('user_'.$user_id.'_', true) . '.' . $file_ext;
                    $destination = $upload_dir . $unique_name;
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                        $update_fields[] = "profile_photo = ?";
                        $params[] = $destination;
                        $types .= 's';
                    } else { $form_message = "Error uploading profile picture."; $message_type = 'error'; }
                } else { $form_message = "Invalid file type."; $message_type = 'error'; }
            }
            if (empty($form_message) && !empty($update_fields)) {
                $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
                $params[] = $user_id;
                $types .= 'i';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $form_message = "Profile updated successfully!";
                    $message_type = 'success';
                } else { $form_message = "Error updating profile."; $message_type = 'error'; }
                $stmt->close();
            } elseif (empty($update_fields) && empty($_FILES['profile_photo']['name'])) {
                $form_message = "No changes were submitted.";
                $message_type = 'info';
            }
        }
        elseif ($action === 'change_password') {
            $message_area = 'password';
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                 $form_message = "Please fill in all password fields.";
                 $message_type = 'error';
            } elseif ($new_password !== $confirm_password) {
                $form_message = "New passwords do not match.";
                $message_type = 'error';
            } else {
                $pass_stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
                $pass_stmt->bind_param("i", $user_id);
                $pass_stmt->execute();
                $pass_result = $pass_stmt->get_result()->fetch_assoc();
                $pass_stmt->close();
                if ($pass_result && password_verify($current_password, $pass_result['password'])) {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $update_pass_stmt->bind_param("si", $new_password_hash, $user_id);
                    if ($update_pass_stmt->execute()) {
                        $form_message = "Password changed successfully.";
                        $message_type = 'success';
                    } else { $form_message = "Failed to update password."; $message_type = 'error'; }
                    $update_pass_stmt->close();
                } else { $form_message = "Incorrect current password."; $message_type = 'error'; }
            }
        }
    }
}

// --- Fetch Current User Data to display ---
$user_stmt = $conn->prepare("SELECT name, email, profile_photo, phone_number, address, created_at FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$result = $user_stmt->get_result();
$user_details = $result->fetch_assoc();
$user_stmt->close();
$conn->close();

function render_message($area) { /* ... (this function is unchanged) ... */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; /* slate-100 */ }
        
        .form-input {
            @apply block w-full rounded-md py-2.5 px-4 shadow-sm transition-colors duration-200 ease-in-out text-center;
            @apply border-gray-300 bg-gray-50 text-slate-900;
            @apply focus:border-purple-500 focus:ring-1 focus:ring-purple-500;
        }
        /* NEW: Style for inputs when they are disabled (view mode) */
        .form-input:disabled {
            @apply bg-transparent border-transparent text-slate-800 font-medium;
            cursor: default;
        }
        
        .field-label { @apply text-sm text-slate-600; }
        .card { @apply bg-white border border-slate-200 rounded-xl shadow-sm; }

        /* NEW: Purple Box Button style */
        .btn-purple-box {
            @apply w-full text-center bg-purple-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md;
            @apply hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500;
            @apply transition-all duration-200;
        }
        .btn-secondary { @apply bg-white text-gray-700 border-gray-300 hover:bg-gray-50; }
    </style>
</head>
<body class="text-slate-800">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="mb-10">
            <a href="userdashboard.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center gap-1 mb-2">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Dashboard
            </a>
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Account Settings</h1>
            <p class="mt-2 text-lg text-slate-500">Manage your personal information and security settings.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">

            <aside class="lg:col-span-1">
                <div class="bg-gradient-to-br from-purple-700 to-violet-800 p-6 text-center sticky top-8 rounded-2xl shadow-xl">
                    <img id="profile-preview-img" class="h-32 w-32 rounded-full object-cover mx-auto border-4 border-violet-800/50 shadow-lg" src="<?php echo htmlspecialchars($user_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                    <div class="mt-4">
                        <h2 id="profile-preview-name" class="text-2xl font-bold text-white"><?php echo htmlspecialchars($user_details['name']); ?></h2>
                        <p class="text-sm text-purple-200"><?php echo htmlspecialchars($user_details['email']); ?></p>
                    </div>
                    <div class="mt-6 pt-5 border-t border-white/10">
                        <p class="text-xs text-purple-300">Member since <?php echo date('F Y', strtotime($user_details['created_at'])); ?></p>
                    </div>
                </div>
            </aside>

            <main class="lg:col-span-2 space-y-10">
                <form id="profile-form" action="user_profile.php" method="POST" enctype="multipart/form-data" class="card">
                    <div class="p-6 sm:p-8">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Personal Information</h3>
                                <p class="mt-1 text-sm text-slate-500">Your personal details.</p>
                            </div>
                            <button type="button" id="edit-profile-btn" class="px-4 py-2 text-sm font-semibold text-purple-600 bg-purple-100 hover:bg-purple-200 rounded-lg">Edit Profile</button>
                        </div>
                        <div class="mt-6 space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="flex items-center justify-between p-2 rounded-lg">
                                <label class="field-label">Profile Photo</label>
                                <div class="flex items-center gap-3">
                                    <img id="upload-preview-img" class="h-12 w-12 rounded-full object-cover" src="<?php echo htmlspecialchars($user_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                                    <label for="profile_photo" id="change-photo-label" class="cursor-pointer inline-block rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition ring-1 ring-slate-300">Change</label>
                                    <input id="profile_photo" name="profile_photo" type="file" class="sr-only" disabled>
                                </div>
                            </div>
                            <hr>
                            <div class="flex items-center justify-between p-2">
                                <label for="name" class="field-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-input w-1/2" value="<?php echo htmlspecialchars($user_details['name']); ?>" required disabled />
                            </div>
                            <hr>
                            <div class="flex items-center justify-between p-2">
                                <label for="phone_number" class="field-label">Phone Number</label>
                                <input type="tel" id="phone_number" name="phone_number" class="form-input w-1/2" value="<?php echo htmlspecialchars($user_details['phone_number']); ?>" disabled />
                            </div>
                            <hr>
                            <div class="flex items-center justify-between p-2">
                                <label for="address" class="field-label">Address</label>
                                <input type="text" id="address" name="address" class="form-input w-1/2" value="<?php echo htmlspecialchars($user_details['address']); ?>" disabled />
                            </div>
                        </div>
                    </div>
                    <div id="profile-form-footer" class="bg-slate-50 px-6 py-4 rounded-b-xl flex items-center justify-end gap-4 hidden">
                         <button type="button" id="cancel-profile-btn" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
                         <button type="submit" class="btn-purple-box w-auto px-8">Save Changes</button>
                    </div>
                </form>

                <form id="password-form" action="user_profile.php" method="POST" class="card">
                     </form>
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- NEW: View/Edit Mode Logic for Profile Form ---
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const cancelProfileBtn = document.getElementById('cancel-profile-btn');
        const profileFormFooter = document.getElementById('profile-form-footer');
        
        const profileInputs = [
            document.getElementById('name'),
            document.getElementById('phone_number'),
            document.getElementById('address')
        ];
        const photoInput = document.getElementById('profile_photo');
        const changePhotoLabel = document.getElementById('change-photo-label');

        // Store original values for cancellation
        const originalValues = {
            name: profileInputs[0].value,
            phone_number: profileInputs[1].value,
            address: profileInputs[2].value
        };
        const originalPhotoSrc = document.getElementById('upload-preview-img').src;
        
        // --- Function to enter EDIT mode ---
        function enterEditMode() {
            // Enable all text inputs
            profileInputs.forEach(input => input.disabled = false);
            // Enable file input
            photoInput.disabled = false;
            changePhotoLabel.classList.remove('bg-slate-100', 'text-slate-700', 'ring-slate-300');
            changePhotoLabel.classList.add('bg-purple-100', 'text-purple-700', 'ring-purple-300');

            // Show form footer with Save/Cancel buttons
            profileFormFooter.classList.remove('hidden');
            // Hide the 'Edit Profile' button
            editProfileBtn.classList.add('hidden');

            // Focus the first input field
            profileInputs[0].focus();
        }

        // --- Function to enter VIEW mode (or cancel) ---
        function enterViewMode() {
            // Disable all inputs
            profileInputs.forEach(input => input.disabled = true);
            photoInput.disabled = true;
            changePhotoLabel.classList.add('bg-slate-100', 'text-slate-700', 'ring-slate-300');
            changePhotoLabel.classList.remove('bg-purple-100', 'text-purple-700', 'ring-purple-300');


            // Reset values to original
            profileInputs[0].value = originalValues.name;
            profileInputs[1].value = originalValues.phone_number;
            profileInputs[2].value = originalValues.address;
            document.getElementById('profile-preview-img').src = originalPhotoSrc;
            document.getElementById('upload-preview-img').src = originalPhotoSrc;

            // Hide form footer
            profileFormFooter.classList.add('hidden');
            // Show the 'Edit Profile' button
            editProfileBtn.classList.remove('hidden');
        }

        editProfileBtn.addEventListener('click', enterEditMode);
        cancelProfileBtn.addEventListener('click', enterViewMode);


        // --- Live Preview Logic (for photo) ---
        if(photoInput) {
            photoInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const newImgSrc = e.target.result;
                        document.getElementById('profile-preview-img').src = newImgSrc;
                        document.getElementById('upload-preview-img').src = newImgSrc;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    </script>
</body>
</html>