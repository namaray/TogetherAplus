<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- 1. Authentication & Authorization ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- 2. Input Validation ---
$hiring_id = filter_input(INPUT_GET, 'hiring_id', FILTER_VALIDATE_INT);
if (!$hiring_id) {
    $_SESSION['error_message'] = "Invalid hiring record specified.";
    header('Location: userdashboard.php');
    exit;
}

// --- 3. Fetch Record Details & Verify Ownership ---
$sql = "
    SELECT 
        hr.hiring_id, hr.user_id, hr.logged_hours, hr.hourly_rate,
        (hr.logged_hours * hr.hourly_rate) AS payable_amount,
        h.name AS helper_name,
        t.title AS task_title
    FROM hiring_records hr
    JOIN helpers h ON hr.helper_id = h.helper_id
    JOIN tasks t ON hr.task_id = t.task_id
    WHERE hr.hiring_id = ? AND hr.user_id = ? AND hr.status = 'completed' AND hr.user_confirmation = 'pending'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $hiring_id, $user_id);
$stmt->execute();
$payment_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If no record is found, or if it doesn't belong to the user, redirect.
if (!$payment_details) {
    $_SESSION['error_message'] = "Payment record not found or already confirmed.";
    header('Location: userdashboard.php');
    exit;
}

// --- 4. Handle Form Submission for Payment Confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $amount = $payment_details['payable_amount'];

    if (!in_array($payment_method, ['credit_card', 'paypal', 'mobile_payment'])) {
        $_SESSION['error_message'] = "Invalid payment method selected.";
        header("Location: confirm_payment.php?hiring_id=$hiring_id");
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Step A: Update hiring record to confirm payment
        $update_hr_sql = "UPDATE hiring_records SET user_confirmation = 'confirmed' WHERE hiring_id = ? AND user_id = ?";
        $update_hr_stmt = $conn->prepare($update_hr_sql);
        $update_hr_stmt->bind_param("ii", $hiring_id, $user_id);
        $update_hr_stmt->execute();
        $update_hr_stmt->close();

        // Step B: Insert the payment record
        $insert_payment_sql = "INSERT INTO payments (user_id, hiring_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'completed')";
        $insert_payment_stmt = $conn->prepare($insert_payment_sql);
        $insert_payment_stmt->bind_param("iids", $user_id, $hiring_id, $amount, $payment_method);
        $insert_payment_stmt->execute();
        $insert_payment_stmt->close();

        // If all queries succeed, commit the transaction
        $conn->commit();
        $_SESSION['success_message'] = "Payment of $" . number_format($amount, 2) . " was successful!";

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "A database error occurred. Please try again.";
        // For debugging: error_log("Payment confirmation error: " . $e->getMessage());
    }

    header('Location: userdashboard.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.8s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Custom styles for interactive payment cards */
        .payment-card {
            @apply p-4 border-2 border-slate-200 rounded-lg cursor-pointer transition-all duration-200 flex items-center gap-4;
        }
        .payment-card:hover {
            @apply border-indigo-400 bg-indigo-50;
        }
        /* Style the card when the hidden radio button inside is checked */
        input[type="radio"]:checked + .payment-card {
            @apply border-indigo-600 bg-indigo-100 ring-2 ring-indigo-500;
        }
    </style>
</head>
<body class="bg-slate-100">

    <main class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl mx-auto">
            <div class="fade-in bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="text-center">
                        <a href="userdashboard.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Back to Dashboard
                        </a>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Confirm Your Payment</h1>
                        <p class="mt-2 text-slate-600">You're about to pay for the completion of a task. Please review the details below.</p>
                    </div>

                    <div class="mt-8 p-6 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-slate-500">Task</p>
                                <p class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($payment_details['task_title']); ?></p>
                                <p class="text-sm text-slate-500">for <strong class="font-medium text-slate-700"><?php echo htmlspecialchars($payment_details['helper_name']); ?></strong></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-500">Amount Due</p>
                                <p class="text-3xl font-extrabold text-indigo-600">$<?php echo number_format($payment_details['payable_amount'], 2); ?></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-200 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Total Hours Worked</span><span class="font-medium text-slate-700"><?php echo number_format($payment_details['logged_hours'], 2); ?></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Hourly Rate</span><span class="font-medium text-slate-700">$<?php echo number_format($payment_details['hourly_rate'], 2); ?></span></div>
                        </div>
                    </div>

                    <form method="POST" action="confirm_payment.php?hiring_id=<?php echo $hiring_id; ?>" class="mt-8 space-y-6">
                        <div>
                            <label class="block text-lg font-bold text-slate-800 mb-4">Choose Payment Method</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label>
                                    <input type="radio" name="payment_method" value="credit_card" class="sr-only" checked>
                                    <div class="payment-card">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                        <span class="font-semibold text-slate-700">Credit Card</span>
                                    </div>
                                </label>
                                
                                <label>
                                    <input type="radio" name="payment_method" value="paypal" class="sr-only">
                                    <div class="payment-card">
                                         <svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7.483 3.007H21.51a.5.5 0 0 1 .494.576l-2.93 16.113a.5.5 0 0 1-.495.424H2.49a.5.5 0 0 1-.494-.576L4.927 3.43a.5.5 0 0 1 .495-.423h2.06zm-.64 1.002H5.16l-2.43 15.11h13.91l2.43-15.11H8.75a1 1 0 0 0-1 .906L6.843 4.01zm6.983 6.903c-.27-.58-.87-1.02-1.6-1.02h-2.19c-2.19 0-2.91 1.83-2.58 3.82.26 1.48 1.41 2.37 2.87 2.37h.65c.57 0 .93-.15.93-.15l.13.78s-.3.19-.8.19h-.79c-2.32 0-3.9-1.3-4.33-3.65-.5-2.67 1.29-4.35 3.73-4.35h2.3c1.19 0 2.05.52 2.42 1.63l-1.39.3z"/></svg>
                                        <span class="font-semibold text-slate-700">PayPal</span>
                                    </div>
                                </label>
                                
                                <label>
                                    <input type="radio" name="payment_method" value="mobile_payment" class="sr-only">
                                    <div class="payment-card">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        <span class="font-semibold text-slate-700">Mobile</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-lg text-base font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all transform hover:scale-105">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Pay $<?php echo number_format($payment_details['payable_amount'], 2); ?> Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // This is a more robust way to handle the fade-in animation
            const elements = document.querySelectorAll('.fade-in');
            
            // Check if the browser supports IntersectionObserver
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible'); // You can add a 'visible' class for CSS transitions
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                elements.forEach(el => observer.observe(el));
            } else {
                // Fallback for older browsers
                elements.forEach(el => el.classList.add('visible'));
            }
        });
    </script>
</body>
</html>