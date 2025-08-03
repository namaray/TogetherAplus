<?php
/* ========================================= *
 * TogetherA+  –  User & Caretaker Sign-up  *
 * ========================================= */

$host     = 'localhost';
$dbName   = 'togetheraplus';
$username = 'root';      // XAMPP default
$password = '1234';      // change if you set another pw

$conn = new mysqli($host, $username, $password, $dbName);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) grab + sanitise
    $name         = trim($_POST['name']         ?? '');
    $emailRaw     = $_POST['email']        ?? '';
    $email        = filter_var($emailRaw, FILTER_SANITIZE_EMAIL);
    $phone        = trim($_POST['phone']        ?? '');
    $address      = trim($_POST['address']      ?? '');
    $passwordPlain = $_POST['password']     ?? '';
    $userType     = $_POST['user_type']    ?? ''; // ** NEW: Get user type from radio buttons

    // 2) basic validation
    if ($name === '')                            $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                                 $errors[] = 'A valid email is required.';
    if ($passwordPlain === '')                   $errors[] = 'Password is required.';
    if ($phone === '')                           $errors[] = 'Phone number is required.';
    if ($address === '')                         $errors[] = 'Address is required.';
    if ($userType === '')                        $errors[] = 'Please select your account type.'; // ** NEW: Validate user type
    if (!in_array($userType, ['disabled_individual', 'caretaker'])) {
                                                 $errors[] = 'Invalid account type selected.'; // ** NEW: Security check
    }


    // 3) duplicate email check
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'That email is already registered.';
        }
        $stmt->close();
    }

    // 4) insert user
    if (empty($errors)) {
        $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

        // ** MODIFIED: SQL query now inserts the selected user_type and no longer references the non-existent 'disability_type' column.
        $stmt = $conn->prepare(
            'INSERT INTO users
                (name, email, password_hash, phone_number, address, user_type)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        // ** MODIFIED: Bind 6 params instead of 7
        $stmt->bind_param('ssssss', $name, $email, $passwordHash, $phone, $address, $userType);

        if ($stmt->execute()) {
            $success = 'Registration successful! Once verified by an admin you can log in.';
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | TogetherA+</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary:  { DEFAULT:'#6D28D9', light:'#8B5CF6', dark:'#5B21B6' },
                        secondary:'#10B981',
                        neutral:  { light:'#F9FAFB', DEFAULT:'#F3F4F6', medium:'#9CA3AF', dark:'#374151', darkest:'#111827' }
                    },
                    animation:{'fade-in-up':'fadeInUp 1s ease-out forwards'},
                    keyframes:{ fadeInUp:{'0%':{opacity:'0',transform:'translateY(20px)'},
                                     '100%':{opacity:'1',transform:'translateY(0)'}} }
                }
            }
        }
    </script>
    <style>
        .animate-on-load{animation:fadeInUp 1s ease-out forwards}
        body{background:url('img/disabledhelper.jpg') center/cover fixed}
    </style>
</head>

<body class="text-neutral-dark font-sans antialiased">

<main class="py-8 md:py-12 min-h-screen bg-neutral-light/90">
    <div class="container mx-auto px-6 max-w-7xl">

        <a href="index.html" class="block mb-12 animate-on-load">
            <img src="img/logo2.png" alt="TogetherA+ Logo" class="h-10"
                 onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div style="display:none;" class="flex items-center gap-2">
                <svg class="h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="currentColor" d="M224,128a96,96,0,1,1-96-96A96,96,0,0,1,224,128ZM128,56a40,40,0,1,0,40,40A40,40,0,0,0,128,56Zm0,96a56,56,0,0,0-47.34,27.14,72,72,0,0,1,94.68,0A56,56,0,0,0,128,152Z"/></svg>
                <span class="text-3xl font-bold text-neutral-darkest">TogetherA+</span>
            </div>
        </a>

        <div class="grid lg:grid-cols-2 gap-16 items-center">

            <div class="animate-on-load" style="animation-delay:100ms">
                <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl">
                    <h2 class="text-3xl font-extrabold text-neutral-darkest mb-2">Create Your Account</h2>
                    <p class="text-neutral-medium mb-8">Join our community to find help or to offer your support.</p> <?php if (!empty($errors)): ?>
                        <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg text-sm">
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                            </ul>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg text-sm">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-5">
                        <input type="text"     name="name"     placeholder="Full Name" value="<?= htmlspecialchars($_POST['name']??'') ?>"
                               required class="w-full px-4 py-3 border border-neutral-DEFAULT rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <input type="email"    name="email"    placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email']??'') ?>"
                               required class="w-full px-4 py-3 border border-neutral-DEFAULT rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <input type="text"     name="phone"    placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone']??'') ?>"
                               required class="w-full px-4 py-3 border border-neutral-DEFAULT rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <input type="text"     name="address"  placeholder="Address" value="<?= htmlspecialchars($_POST['address']??'') ?>"
                               required class="w-full px-4 py-3 border border-neutral-DEFAULT rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        
                        <fieldset class="border border-neutral-DEFAULT rounded-lg p-4">
                            <legend class="text-sm font-medium text-neutral-medium px-2">I am a...</legend>
                            <div class="flex gap-x-6 justify-around pt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="user_type" value="disabled_individual"
                                           <?php if (isset($_POST['user_type']) && $_POST['user_type'] === 'disabled_individual') echo 'checked'; ?>
                                           required class="h-4 w-4 text-primary focus:ring-primary border-neutral-medium">
                                    <span class="font-medium">User (Seeking Help)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="user_type" value="caretaker"
                                           <?php if (isset($_POST['user_type']) && $_POST['user_type'] === 'caretaker') echo 'checked'; ?>
                                           required class="h-4 w-4 text-primary focus:ring-primary border-neutral-medium">
                                    <span class="font-medium">Caretaker</span>
                                </label>
                            </div>
                        </fieldset>
                        <input type="password" name="password" placeholder="Password"
                               required class="w-full px-4 py-3 border border-neutral-DEFAULT rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">

                        <button type="submit"
                                 class="w-full bg-primary text-white font-bold py-4 px-8 rounded-xl shadow-lg
                                        hover:bg-primary-dark hover:-translate-y-1 transform transition-all mt-4">
                            Register
                        </button>
                    </form>
                </div>
            </div>

            <div class="animate-on-load" style="animation-delay:250ms">
                <h3 class="text-2xl font-extrabold text-neutral-darkest mb-6">Your Independence, Supported.</h3>
                <p class="text-lg text-neutral-medium mb-8 leading-relaxed">
                    By creating an account, you gain access to a network of trusted individuals ready to help you live more independently.
                </p>

                <ul class="space-y-6">
                    <li class="flex items-start gap-4">
                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-neutral-darkest">Vetted & Verified Helpers</h4>
                            <p class="text-neutral-medium">Every helper is background-checked, so you can hire with complete confidence and peace of mind.</p>
                        </div>
                    </li>
                    </ul>
            </div>
        </div>
    </div>
</main>
</body>
</html>