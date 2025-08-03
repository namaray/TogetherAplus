<?php
session_start();
// Use your actual database connection file
include 'dbconnect.php'; 

// --- Authentication Check ---
// Redirect to login if the user is not authenticated.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Fetch User Details ---
// Fetches the logged-in user's name for a personalized greeting.
$sql_user = "SELECT name FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_details = $stmt_user->get_result()->fetch_assoc();
$user_name = $user_details['name'] ?? 'User'; // Fallback name

// --- Fetch Hiring & Payment Records ---
// Gathers all transaction data for the user.
$sql = "
    SELECT 
        hr.hiring_id, 
        hr.logged_hours, 
        hr.hourly_rate, 
        (hr.logged_hours * hr.hourly_rate) AS total_fee, 
        (hr.logged_hours * hr.hourly_rate * 0.10) AS platform_fee, 
        (hr.logged_hours * hr.hourly_rate) - (hr.logged_hours * hr.hourly_rate * 0.10) AS payable_amount,
        p.amount AS paid,
        p.status AS payment_status,
        hr.status AS hire_status
    FROM hiring_records hr
    LEFT JOIN payments p ON hr.hiring_id = p.hiring_id
    WHERE hr.user_id = ?
    ORDER BY hr.hiring_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hiring_records = $result->fetch_all(MYSQLI_ASSOC);

// --- Fetch Aggregated Summary Data ---
// Calculates total spending for the summary cards and chart.
$summary_sql = "
    SELECT 
        SUM(hr.logged_hours * hr.hourly_rate) AS total_spent,
        SUM(hr.logged_hours * hr.hourly_rate * 0.10) AS total_platform_fee,
        SUM((hr.logged_hours * hr.hourly_rate) - (hr.logged_hours * hr.hourly_rate * 0.10)) AS total_to_helpers
    FROM hiring_records hr
    WHERE hr.user_id = ? AND hr.status = 'completed'
";
$stmt_summary = $conn->prepare($summary_sql);
$stmt_summary->bind_param("i", $user_id);
$stmt_summary->execute();
$summary_data = $stmt_summary->get_result()->fetch_assoc();

// Initialize summary data to prevent errors if no records are found
$total_spent = $summary_data['total_spent'] ?? 0;
$total_platform_fee = $summary_data['total_platform_fee'] ?? 0;
$total_to_helpers = $summary_data['total_to_helpers'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Dashboard - TogetherA+</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Animation for elements fading in on scroll */
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

<body class="bg-slate-50 text-slate-800">
    
    <?php // include 'header_user.php'; // Optional: Include your standard header if it exists ?>

    <main class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header -->
            <header class="mb-8">
                <div class="flex items-start justify-between">
                    <div>
                        <a href="userdashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold flex items-center gap-1 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Back to Personal Dashboard
                        </a>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Payment Dashboard</h1>
                        <p class="mt-1 text-slate-500">Welcome back, <?php echo htmlspecialchars($user_name); ?>. Here's an overview of your payment activity.</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex-shrink-0">
                         <a href="payment_insights.php" class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            View Detailed Analysis
                        </a>
                    </div>
                </div>
            </header>

            <!-- Summary Cards & Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- Summary Cards Column -->
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Total Spent -->
                    <div class="fade-in-up bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-slate-500">Total Spent</h3>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900" data-target="<?php echo $total_spent; ?>">$0.00</p>
                            </div>
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01"></path></svg>
                            </div>
                        </div>
                    </div>
                    <!-- Total Paid to Helpers -->
                    <div class="fade-in-up bg-white p-6 rounded-xl border border-slate-200 shadow-sm" style="transition-delay: 100ms;">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-slate-500">Total Paid to Helpers</h3>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900" data-target="<?php echo $total_to_helpers; ?>">$0.00</p>
                            </div>
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                        </div>
                    </div>
                    <!-- Total Platform Fees -->
                    <div class="fade-in-up bg-white p-6 rounded-xl border border-slate-200 shadow-sm" style="transition-delay: 200ms;">
                         <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-slate-500">Total Platform Fees</h3>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900" data-target="<?php echo $total_platform_fee; ?>">$0.00</p>
                            </div>
                            <div class="p-2 bg-purple-100 rounded-lg">
                               <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="fade-in-up lg:col-span-1 bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center" style="transition-delay: 300ms;">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 text-center">Spending Overview</h3>
                    <div class="h-48 w-48 mx-auto">
                        <canvas id="spendingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Transaction Table -->
            <div class="fade-in-up bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" style="transition-delay: 400ms;">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-slate-900">Transaction History</h2>
                    <p class="mt-1 text-sm text-slate-500">A detailed record of all your payments.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Hiring ID</th>
                                <th scope="col" class="px-6 py-3 font-medium">Total Fee</th>
                                <th scope="col" class="px-6 py-3 font-medium">Platform Fee</th>
                                <th scope="col" class="px-6 py-3 font-medium">Amount to Helper</th>
                                <th scope="col" class="px-6 py-3 font-medium">Payment Status</th>
                                <th scope="col" class="px-6 py-3 font-medium">Hire Status</th>
                                <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (count($hiring_records) > 0): ?>
                                <?php foreach ($hiring_records as $record): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 font-mono text-slate-500">#<?php echo $record['hiring_id']; ?></td>
                                        <td class="px-6 py-4 font-semibold">$<?php echo number_format($record['total_fee'], 2); ?></td>
                                        <td class="px-6 py-4">$<?php echo number_format($record['platform_fee'], 2); ?></td>
                                        <td class="px-6 py-4">$<?php echo number_format($record['payable_amount'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $p_status = strtolower($record['payment_status'] ?? 'pending');
                                                $p_color = 'bg-yellow-100 text-yellow-800';
                                                if ($p_status == 'completed') $p_color = 'bg-green-100 text-green-800';
                                                if ($p_status == 'failed') $p_color = 'bg-red-100 text-red-800';
                                                echo "<span class='px-2 py-1 text-xs font-medium rounded-full $p_color'>" . ucfirst($p_status) . "</span>";
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $h_status = strtolower($record['hire_status'] ?? 'pending');
                                                $h_color = 'bg-yellow-100 text-yellow-800';
                                                if ($h_status == 'completed') $h_color = 'bg-green-100 text-green-800';
                                                if ($h_status == 'disputed') $h_color = 'bg-red-100 text-red-800';
                                                if ($h_status == 'active') $h_color = 'bg-blue-100 text-blue-800';
                                                echo "<span class='px-2 py-1 text-xs font-medium rounded-full $h_color'>" . ucfirst($h_status) . "</span>";
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="view_hiring.php?hiring_id=<?php echo $record['hiring_id']; ?>" class="font-medium text-indigo-600 hover:text-indigo-800">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-12 px-6 text-slate-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">No transactions yet</h3>
                                        <p class="mt-1 text-sm text-gray-500">Get started by hiring a HelpMate for a task.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- Animated Counter for Summary Cards ---
        function animateCounter(element) {
            const target = +element.getAttribute('data-target');
            const duration = 1500; // Animation duration in ms
            const stepTime = 20;   // Interval time in ms
            let current = 0;
            const increment = target / (duration / stepTime);

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = '$' + current.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }, stepTime);
        }

        // --- Intersection Observer for animations ---
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    
                    // Trigger counter animation if the element has a data-target attribute
                    const counter = entry.target.querySelector('[data-target]');
                    if (counter) {
                        animateCounter(counter);
                    }
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });

        // --- Chart.js Doughnut Chart ---
        const ctx = document.getElementById('spendingChart');
        if (ctx) {
            const chartData = {
                labels: ['Paid to Helpers', 'Platform Fees'],
                datasets: [{
                    label: 'Spending Breakdown',
                    data: [<?php echo $total_to_helpers; ?>, <?php echo $total_platform_fee; ?>],
                    backgroundColor: [
                        '#4f46e5', // indigo-600
                        '#d1d5db'  // gray-300
                    ],
                    borderColor: '#f8fafc', // bg-slate-50
                    hoverOffset: 4
                }]
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
