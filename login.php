<?php
session_start();
$error_message = ''; // Initialize error message variable

// Only process the form if it was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'dbconnect.php'; // Include the database connection

    $email = $_POST['email']; 
    $password = $_POST['password'];
    $generic_error = "The email or password you entered is incorrect.";

    // --- 1. Check in 'users' table ---
    $stmt = $conn->prepare("SELECT user_id, password_hash, status, verification_status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            if ($row['status'] !== 'active' || $row['verification_status'] !== 'verified') {
                 $error_message = "Your account is not active or verified. Please contact support.";
            } else {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = 'user';
                header('Location: userdashboard.php');
                exit;
            }
        }
    }
    
    // --- 2. Check in 'helpers' table ---
    if(empty($error_message)) {
        $stmt = $conn->prepare("SELECT helper_id, password_hash, status, verification_status FROM helpers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password_hash'])) {
                 if ($row['status'] !== 'active' || $row['verification_status'] !== 'verified') {
                    $error_message = "Your account is not active or verified. Please wait for admin approval.";
                 } else {
                    $_SESSION['helper_id'] = $row['helper_id'];
                    $_SESSION['role'] = 'helper';
                    header('Location: helperdashboard.php');
                    exit;
                 }
            }
        }
    }

    // --- 3. Check in 'admins' table ---
    if(empty($error_message)) {
        $stmt = $conn->prepare("SELECT admin_id, name, password_hash, role FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_name'] = $row['name'];
                $_SESSION['admin_role'] = $row['role'];
                header('Location: admin_panel/index.php');
                exit;
            }
        }
    }
    
    // If we reach here, no login was successful
    if(empty($error_message)) {
        $error_message = $generic_error;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TogetherA+</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in {
            animation: fadeIn 1s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-5xl flex flex-col md:flex-row bg-white rounded-2xl shadow-2xl overflow-hidden fade-in">
            
            <!-- Left Side: Image and Quote -->
            <div class="w-full md:w-1/2 bg-indigo-600 text-white p-12 hidden md:flex flex-col items-center justify-center text-center relative">
                <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('img/indexbg.jpg');"></div>
                <div class="relative z-10">
                    <a href="index.html" class="flex items-center justify-center gap-2 mb-8">
                        <img src="img/logo2.png" alt="TogetherA+ Logo" class="h-12">
                    </a>
                    <h2 class="text-3xl font-bold mt-4 leading-tight">Your Independence, Supported.</h2>
                    <p class="mt-4 text-indigo-200/90 max-w-sm mx-auto">
                        Connecting individuals with disabilities to a community of vetted, skilled, and compassionate helpers.
                    </p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                <div class="sm:mx-auto sm:w-full sm:max-w-md">
                    <h2 class="text-center text-2xl font-bold leading-9 tracking-tight text-slate-800">
                        Sign in to your account
                    </h2>
                </div>

                <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
                    <?php if (!empty($error_message)): ?>
                        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-500 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-6" action="login.php" method="POST">
                        <div>
                            <label for="email" class="block text-sm font-medium leading-6 text-slate-700">Email address</label>
                            <div class="mt-2">
                                <input id="email" name="email" type="email" autocomplete="email" required class="block w-full rounded-md border-0 p-3 bg-slate-100 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-medium leading-6 text-slate-700">Password</label>
                                <div class="text-sm">
                                    <a href="forgot_password.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                                </div>
                            </div>
                            <div class="mt-2">
                                <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-md border-0 p-3 bg-slate-100 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-3 text-base font-semibold leading-6 text-white shadow-lg hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-transform hover:-translate-y-0.5">
                                Sign in
                            </button>
                        </div>
                    </form>

                    <p class="mt-10 text-center text-sm text-slate-500">
                        Not a member?
                        <a href="registercustomer.php" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Register for a new account</a>
                    </p>
                    <p class="mt-10 text-center text-sm text-slate-500">
                        Want to become a HelpMate?
                        <a href="register.php" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Register for a new account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
