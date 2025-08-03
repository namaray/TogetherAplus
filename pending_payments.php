<?php
session_start();
include 'dbconnect.php'; // Include the database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch pending payments for the user. These are completed hiring records awaiting user confirmation.
// Ordered by creation date to show the newest items first.
$sql = "
    SELECT 
        hr.hiring_id,
        hr.logged_hours,
        hr.hourly_rate,
        (hr.logged_hours * hr.hourly_rate) AS total_fee,
        ((hr.logged_hours * hr.hourly_rate) * 0.10) AS platform_fee,
        ((hr.logged_hours * hr.hourly_rate) - ((hr.logged_hours * hr.hourly_rate) * 0.10)) AS payable_amount,
        h.name AS helper_name,
        t.title AS task_title,
        hr.created_at
    FROM hiring_records hr
    JOIN helpers h ON hr.helper_id = h.helper_id
    JOIN tasks t ON hr.task_id = t.task_id
    WHERE hr.user_id = ? AND hr.status = 'completed' AND hr.user_confirmation = 'pending'
    ORDER BY hr.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_payments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-slate-50">

    <?php // include 'header_user.php'; // Optional: Include your standard header if it exists ?>

    <main class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <!-- Header -->
        <header class="fade-in-up mb-8">
            <a href="userdashboard.php" class="inline-flex items-center gap-2 rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-200 border border-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Dashboard
            </a>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 mt-4">Pending Payments</h1>
            <p class="mt-1 text-slate-600">Review completed tasks and confirm payment to your HelpMates.</p>
        </header>

        <!-- Pending Payments List -->
        <div class="space-y-4">
            <?php if (empty($pending_payments)): ?>
                <div class="fade-in-up text-center bg-white p-12 rounded-xl border border-slate-200">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900">All Caught Up!</h3>
                    <p class="mt-1 text-sm text-gray-500">You have no pending payments to confirm.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_payments as $index => $payment): ?>
                    <div class="fade-in-up bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between" style="transition-delay: <?php echo $index * 100; ?>ms;">
                        <div class="flex-grow mb-4 sm:mb-0">
                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($payment['task_title']); ?></p>
                            <p class="text-sm text-slate-500">
                                Completed by <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($payment['helper_name']); ?></span>
                            </p>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                            <div class="text-right">
                                <p class="text-lg font-bold text-indigo-600">$<?php echo number_format($payment['payable_amount'], 2); ?></p>
                                <p class="text-xs text-slate-400">Total: $<?php echo number_format($payment['total_fee'], 2); ?></p>
                            </div>
                            <a href="confirm_payment.php?hiring_id=<?php echo $payment['hiring_id']; ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-lg text-sm font-medium h-10 px-4 py-2 bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Confirm & Pay
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

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
        });
    </script>
</body>
</html>
