<?php
session_start();
// Ensure you have a valid database connection file
// Make sure to replace 'dbconnect.php' with your actual connection script
include 'dbconnect.php'; 

// --- Authentication Check ---
// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- 1. Fetch Monthly Spending Data for Chart & Table (REWRITTEN FOR COMPATIBILITY) ---
// This query is structured to be fully compliant with the 'only_full_group_by' SQL mode.
// We group by both the year and month number to ensure correct aggregation and ordering.
$monthly_sql = "
  SELECT
    DATE_FORMAT(MIN(hr.created_at), '%b %Y') AS month_year,
    SUM(IFNULL(hr.logged_hours, 0) * hr.hourly_rate)            AS monthly_spent,
    SUM(IFNULL(hr.logged_hours, 0) * hr.hourly_rate * 0.10)     AS monthly_platform_fee,
    -- helpers get the rest:
    SUM(IFNULL(hr.logged_hours, 0) * hr.hourly_rate)
      - SUM(IFNULL(hr.logged_hours, 0) * hr.hourly_rate * 0.10)
      AS monthly_to_helpers
  FROM hiring_records hr
  WHERE hr.user_id = ?
    AND hr.status = 'completed'
  GROUP BY YEAR(hr.created_at), MONTH(hr.created_at)
  ORDER BY YEAR(hr.created_at), MONTH(hr.created_at);
";
$stmt_monthly = $conn->prepare($monthly_sql);
$stmt_monthly->bind_param("i", $user_id);
$stmt_monthly->execute();
$monthly_data = $stmt_monthly->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare data for Chart.js
$monthly_labels_json = json_encode(array_column($monthly_data, 'month_year'));
$monthly_values_json = json_encode(array_column($monthly_data, 'monthly_spent'));


// --- 2. Fetch Helper-wise Spending Data for Chart & Table ---
// This query is already compliant, as helper_name is dependent on helper_id.
$helper_sql = "
    SELECT 
        h.name AS helper_name,
        SUM((IFNULL(hr.logged_hours, 0) * hr.hourly_rate) - (IFNULL(hr.logged_hours, 0) * hr.hourly_rate * 0.10)) AS total_paid_to_helper
    FROM hiring_records hr
    JOIN helpers h ON hr.helper_id = h.helper_id
    WHERE hr.user_id = ? AND hr.status = 'completed'
    GROUP BY h.helper_id, h.name
    ORDER BY total_paid_to_helper DESC;
";
$stmt_helper = $conn->prepare($helper_sql);
$stmt_helper->bind_param("i", $user_id);
$stmt_helper->execute();
$helper_data = $stmt_helper->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare data for Chart.js
$helper_labels_json = json_encode(array_column($helper_data, 'helper_name'));
$helper_values_json = json_encode(array_column($helper_data, 'total_paid_to_helper'));


// --- 3. Fetch Task-based Payment Breakdown ---
// This query does not use GROUP BY, so it is not affected by the SQL mode.
$task_sql = "
    SELECT 
        t.title AS task_title,
        hr.hiring_id,
        hr.logged_hours,
        hr.hourly_rate,
        (IFNULL(hr.logged_hours, 0) * hr.hourly_rate) AS total_fee,
        h.name AS helper_name
    FROM hiring_records hr
    JOIN tasks t ON hr.task_id = t.task_id
    JOIN helpers h ON hr.helper_id = h.helper_id
    WHERE hr.user_id = ? AND hr.status = 'completed'
    ORDER BY hr.hiring_id DESC
";
$stmt_task = $conn->prepare($task_sql);
$stmt_task->bind_param("i", $user_id);
$stmt_task->execute();
$task_payments = $stmt_task->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Insights - TogetherA+</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>


    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Lighter gray background */
        }

        /* Animation for elements fading in on scroll */
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Staggered animation delays */
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
    </style>
</head>

<body class="bg-slate-100 text-slate-800">
    
    <?php // include 'header_user.php'; // Optional: Include your standard header if it exists ?>

    <main class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Header -->
            <header class="fade-in-up">
                <a href="payment.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">&larr; Back to Dashboard</a>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 mt-2">Detailed Payment Analysis</h1>
                <p class="mt-1 text-slate-500">An in-depth look at your spending habits and transaction details.</p>
            </header>

            <!-- Monthly Spending Analysis -->
            <section class="fade-in-up delay-100 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Monthly Spending</h2>
                <p class="mt-1 text-sm text-slate-500 mb-6">Track your total spending on a month-by-month basis.</p>
                <div class="h-80">
                    <canvas id="monthlySpendingChart"></canvas>
                </div>
            </section>

            <!-- Helper & Task Breakdowns -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                
                <!-- Helper-wise Breakdown -->
                <div class="fade-in-up delay-200 lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Spending by Helper</h2>
                    <p class="mt-1 text-sm text-slate-500 mb-6">How your payments are distributed among different HelpMates.</p>
                    <div class="h-56 w-56 mx-auto mb-6">
                        <canvas id="helperSpendingChart"></canvas>
                    </div>
                    <div class="overflow-y-auto max-h-60 pr-2">
                        <ul class="divide-y divide-slate-200">
                                <?php if (count($helper_data) > 0): ?>
                                    <?php foreach ($helper_data as $helper): ?>
                                    <li class="py-3 flex justify-between items-center">
                                        <span class="font-medium text-slate-700"><?php echo htmlspecialchars($helper['helper_name']); ?></span>
                                        <span class="font-semibold text-slate-900">$<?php echo number_format($helper['total_paid_to_helper'], 2); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="py-3 text-center text-slate-500">No data available.</li>
                                <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Task-based Breakdown Table -->
                <div class="fade-in-up delay-300 lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-slate-900">Spending by Task</h2>
                        <p class="mt-1 text-sm text-slate-500">A detailed breakdown of every task you've paid for.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 font-medium">Task</th>
                                    <th scope="col" class="px-6 py-3 font-medium">Helper</th>
                                    <th scope="col" class="px-6 py-3 font-medium text-right">Total Fee</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                    <?php if (count($task_payments) > 0): ?>
                                        <?php foreach ($task_payments as $task): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-6 py-4 font-semibold text-slate-900"><?php echo htmlspecialchars($task['task_title']); ?></td>
                                                <td class="px-6 py-4 text-slate-500"><?php echo htmlspecialchars($task['helper_name']); ?></td>
                                                <td class="px-6 py-4 font-mono text-slate-800 text-right">$<?php echo number_format($task['total_fee'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-10 px-6 text-slate-500">No task payments found.</td>
                                        </tr>
                                    <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- Intersection Observer for animations ---
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

        // --- Monthly Spending Bar Chart ---
        const monthlyCtx = document.getElementById('monthlySpendingChart');
        if (monthlyCtx && <?php echo json_encode(!empty($monthly_data)); ?>) {
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo $monthly_labels_json; ?>,
                    datasets: [{
                        label: 'Total Spent',
                        data: <?php echo $monthly_values_json; ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.8)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Total Spent: $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- Helper Spending Doughnut Chart ---
        const helperCtx = document.getElementById('helperSpendingChart');
        if (helperCtx && <?php echo json_encode(!empty($helper_data)); ?>) {
            new Chart(helperCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $helper_labels_json; ?>,
                    datasets: [{
                        data: <?php echo $helper_values_json; ?>,
                        backgroundColor: ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'],
                        borderColor: '#f8fafc',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) { label += ': '; }
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
