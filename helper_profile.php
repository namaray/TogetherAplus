<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- Authentication and Helper Data ---
if (!isset($_SESSION['helper_id'])) {
    header('Location: login.php');
    exit;
}
$helper_id = $_SESSION['helper_id'];

$form_message = '';
$message_type = '';

// --- Handle Form Submission for Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form inputs
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $skills = trim($_POST['skills']); // Comma-separated string
    
    $update_fields = [];
    $params = [];
    $types = '';

    // --- Dynamically build the query based on filled fields ---
    if (!empty($name)) { $update_fields[] = "name = ?"; $params[] = $name; $types .= 's'; }
    if (!empty($phone)) { $update_fields[] = "phone_number = ?"; $params[] = $phone; $types .= 's'; }
    if (!empty($address)) { $update_fields[] = "address = ?"; $params[] = $address; $types .= 's'; }
    if (!empty($skills)) { $update_fields[] = "skills = ?"; $params[] = $skills; $types .= 's'; }

    // --- Handle File Upload for Profile Picture ---
    $new_photo_path = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['profile_photo']['name']);
        $file_ext = strtolower($file_info['extension']);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $unique_name = uniqid('helper_'.$helper_id.'_', true) . '.' . $file_ext;
            $destination = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                $new_photo_path = $destination;
                $update_fields[] = "profile_photo = ?";
                $params[] = $new_photo_path;
                $types .= 's';
            } else {
                $form_message = "Error uploading new profile picture.";
                $message_type = 'error';
            }
        } else {
            $form_message = "Invalid file type. Please upload a JPG, PNG, or GIF.";
            $message_type = 'error';
        }
    }

    if (empty($form_message) && !empty($update_fields)) {
        $sql = "UPDATE helpers SET " . implode(', ', $update_fields) . " WHERE helper_id = ?";
        $params[] = $helper_id;
        $types .= 'i';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $form_message = "Profile updated successfully!";
            $message_type = 'success';
        } else {
            $form_message = "Error updating profile. Please try again.";
            $message_type = 'error';
        }
        $stmt->close();
    } elseif (empty($update_fields) && empty($_FILES['profile_photo']['name'])) {
        $form_message = "No changes were submitted.";
        $message_type = 'info';
    }
}

// --- Fetch Current Helper Data to display ---
$helper_stmt = $conn->prepare("SELECT name, email, profile_photo, rating, phone_number, address, skills FROM helpers WHERE helper_id = ?");
$helper_stmt->bind_param("i", $helper_id);
$helper_stmt->execute();
$helper_details = $helper_stmt->get_result()->fetch_assoc();
$conn->close();
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
        body { font-family: 'Inter', sans-serif; }
        .form-input { 
            @apply block w-full rounded-lg border-slate-300 bg-slate-50 py-2.5 px-4 text-slate-900 shadow-sm transition-colors duration-200 ease-in-out;
            @apply focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50;
        }
        .form-label { @apply block text-sm font-semibold text-slate-700 mb-1.5; }
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
        /* Style for readonly inputs to make them look like static text */
        .form-input:read-only {
            border-color: transparent;
            background-color: transparent;
            box-shadow: none;
            cursor: default;
            padding-left: 0.25rem; /* A bit of padding for alignment */
        }
        /* Style for the disabled photo upload button */
        .photo-label-disabled {
            @apply cursor-not-allowed bg-slate-100 text-slate-400 ring-slate-200;
        }
    </style>
</head>
<body class="bg-slate-100">

    <main class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="fade-in-up mb-8">
             <a href="helperdashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Dashboard
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 mt-2">My Profile</h1>
            <p class="mt-1 text-slate-500">View and update your personal information and skills.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <aside class="lg:col-span-1 fade-in-up" style="transition-delay: 100ms;">
                 <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-lg sticky top-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Profile Preview</h3>
                    <div class="flex flex-col items-center">
                        <img id="profile-preview-img" class="h-32 w-32 rounded-full object-cover border-4 border-slate-200" src="<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                        <h2 id="profile-preview-name" class="text-xl font-bold text-slate-900 mt-4"><?php echo htmlspecialchars($helper_details['name']); ?></h2>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($helper_details['email']); ?></p>
                        <div class="mt-2 text-amber-500 flex items-center gap-1">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            <span class="font-bold text-sm"><?php echo number_format($helper_details['rating'], 2); ?></span>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        <h4 class="font-semibold text-sm text-slate-600 mb-2">My Skills</h4>
                        <div id="profile-preview-skills" class="flex flex-wrap gap-2">
                           <?php $skills_array = !empty($helper_details['skills']) ? array_map('trim', explode(',', $helper_details['skills'])) : []; ?>
                           <?php if(empty($skills_array)): ?>
                                <p class="text-xs text-slate-400">No skills listed yet.</p>
                           <?php else: ?>
                               <?php foreach($skills_array as $skill): ?>
                                <span class="bg-indigo-100 text-indigo-700 text-xs font-medium px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($skill); ?></span>
                               <?php endforeach; ?>
                           <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="lg:col-span-2 fade-in-up" style="transition-delay: 200ms;">
                <form id="profile-form" action="helper_profile.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-8">
                     <?php if ($form_message): ?>
                        <div class="p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : ($message_type === 'info' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); ?>">
                            <p><?php echo $form_message; ?></p>
                        </div>
                     <?php endif; ?>

                    <div>
                        <div class="flex justify-between items-start border-b border-slate-200 pb-2 mb-6">
                            <h3 class="text-lg font-bold text-slate-800 pt-2">Update Profile</h3>
                            <button type="button" id="edit-button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Edit
                            </button>
                        </div>
                        
                        <div class="flex items-center gap-6">
                             <img id="upload-preview-img" class="h-20 w-20 rounded-full object-cover border-4 border-slate-200" src="<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>" alt="Profile Photo">
                             <div>
                                 <label id="photo-label" for="profile_photo" class="cursor-pointer inline-block rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 photo-label-disabled">
                                     Change Photo
                                 </label>
                                 <input id="profile_photo" name="profile_photo" type="file" class="sr-only" disabled>
                                 <p class="text-xs text-slate-500 mt-2">PNG, JPG, GIF up to 2MB.</p>
                             </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($helper_details['name']); ?>" readonly />
                        </div>
                        <div>
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($helper_details['phone_number']); ?>" readonly />
                        </div>
                    </div>

                    <div>
                        <label for="address" class="form-label">Address</label>
                        <input type="text" id="address" name="address" class="form-input" value="<?php echo htmlspecialchars($helper_details['address']); ?>" readonly />
                    </div>

                    <div>
                         <label for="skills" class="form-label">My Skills</label>
                         <input type="text" id="skills" name="skills" class="form-input" value="<?php echo htmlspecialchars($helper_details['skills']); ?>" placeholder="e.g. Tutoring, Driving, Shopping" readonly />
                         <p class="text-xs text-slate-500 mt-1">Separate each skill with a comma.</p>
                    </div>

                    <div id="form-actions" class="hidden pt-4 border-t border-slate-200 flex items-center gap-4">
                        <button type="submit" class="w-full sm:w-auto flex justify-center py-3 px-8 border border-transparent rounded-lg shadow-sm text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:scale-105">
                            Save Changes
                        </button>
                        <button type="button" id="cancel-button" class="w-full sm:w-auto flex justify-center py-3 px-8 border border-slate-300 rounded-lg shadow-sm text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 focus:outline-none">
                            Cancel
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

            // --- Form State Control Logic ---
            const form = document.getElementById('profile-form');
            const editButton = document.getElementById('edit-button');
            const cancelButton = document.getElementById('cancel-button');
            const formActions = document.getElementById('form-actions');
            const photoInput = document.getElementById('profile_photo');
            const photoLabel = document.getElementById('photo-label');
            const textInputs = form.querySelectorAll('input[type="text"]');
            
            const originalValues = {};
            textInputs.forEach(input => {
                originalValues[input.id] = input.value;
            });

            function setEditMode(isEditing) {
                if (isEditing) {
                    // Enable editing
                    textInputs.forEach(input => input.readOnly = false);
                    photoInput.disabled = false;
                    photoLabel.classList.remove('photo-label-disabled');
                    
                    editButton.classList.add('hidden');
                    formActions.classList.remove('hidden');
                } else {
                    // Disable editing (Read-only mode)
                    textInputs.forEach(input => {
                        input.readOnly = true;
                        input.value = originalValues[input.id]; // Reset to original value on cancel
                    });
                    
                    photoInput.disabled = true;
                    photoLabel.classList.add('photo-label-disabled');
                    // Reset photo input if a file was selected but not saved
                    photoInput.value = '';

                    editButton.classList.remove('hidden');
                    formActions.classList.add('hidden');

                    // Reset previews
                    updateNamePreview();
                    updateSkillsPreview();
                    document.getElementById('profile-preview-img').src = '<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>';
                    document.getElementById('upload-preview-img').src = '<?php echo htmlspecialchars($helper_details['profile_photo'] ?? 'img/default-avatar.png'); ?>';

                }
            }

            editButton.addEventListener('click', () => setEditMode(true));
            cancelButton.addEventListener('click', () => setEditMode(false));

            // --- Live Preview Logic ---
            const nameInput = document.getElementById('name');
            const skillsInput = document.getElementById('skills');
            
            const previewName = document.getElementById('profile-preview-name');
            const previewSkillsContainer = document.getElementById('profile-preview-skills');
            const previewImg = document.getElementById('profile-preview-img');
            const uploadPreviewImg = document.getElementById('upload-preview-img');

            function updateNamePreview() {
                previewName.textContent = nameInput.value || 'Your Name';
            }

            function updateSkillsPreview() {
                 const skills = skillsInput.value.split(',').map(s => s.trim()).filter(s => s);
                previewSkillsContainer.innerHTML = ''; // Clear current skills
                if (skills.length === 0 || skills[0] === '') {
                    previewSkillsContainer.innerHTML = '<p class="text-xs text-slate-400">No skills listed yet.</p>';
                } else {
                    skills.forEach(skill => {
                        const skillTag = document.createElement('span');
                        skillTag.className = 'bg-indigo-100 text-indigo-700 text-xs font-medium px-2.5 py-1 rounded-full';
                        skillTag.textContent = skill;
                        previewSkillsContainer.appendChild(skillTag);
                    });
                }
            }
            
            nameInput.addEventListener('input', updateNamePreview);
            skillsInput.addEventListener('input', updateSkillsPreview);

            photoInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        uploadPreviewImg.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>