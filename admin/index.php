<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch statistics
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_helpers = $conn->query("SELECT COUNT(*) AS count FROM helpers")->fetch_assoc()['count'];
$total_tasks = $conn->query("SELECT COUNT(*) AS count FROM tasks")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) AS count FROM reviews")->fetch_assoc()['count'];

// Fetch total earnings and revenue
$total_earnings_query = "SELECT SUM(hr.logged_hours * t.hourly_rate) AS total_earnings
                         FROM hiring_records hr
                         JOIN tasks t ON hr.task_id = t.task_id
                         WHERE hr.status = 'completed'";
$total_earnings = $conn->query($total_earnings_query)->fetch_assoc()['total_earnings'] ?? 0;

$total_revenue_query = "SELECT SUM((hr.logged_hours * t.hourly_rate) * 0.1) AS total_revenue
                        FROM hiring_records hr
                        JOIN tasks t ON hr.task_id = t.task_id
                        WHERE hr.status = 'completed'";
$total_revenue = $conn->query($total_revenue_query)->fetch_assoc()['total_revenue'] ?? 0;

// Fetch monthly revenue
$monthly_revenue_query = "SELECT 
                            DATE_FORMAT(hr.start_time, '%Y-%m') AS month,
                            SUM(hr.logged_hours * t.hourly_rate) AS monthly_revenue
                          FROM hiring_records hr
                          JOIN tasks t ON hr.task_id = t.task_id
                          WHERE hr.status = 'completed'
                          GROUP BY DATE_FORMAT(hr.start_time, '%Y-%m')
                          ORDER BY month ASC";
$monthly_revenue_result = $conn->query($monthly_revenue_query);

// Prepare data for the chart
$months = [];
$revenues = [];

if ($monthly_revenue_result->num_rows > 0) {
    while ($row = $monthly_revenue_result->fetch_assoc()) {
        $months[] = $row['month']; // e.g., '2024-01'
        $revenues[] = $row['monthly_revenue']; // e.g., 1500.50
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color:rgb(47, 48, 49);
            color: white;
            position: fixed;
            padding: 20px 15px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color:rgb(34, 34, 34);
        }
        .navbar {
            margin: center;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }
        .stat-card {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .chart-container {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
        <a href="helper.php"><i class="fa-solid fa-person"></i> Manage helper</a>
        <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="tasks.php"><i class="fas fa-tasks"></i> Manage Tasks</a>
        <a href="reviews.php"><i class="fas fa-comments"></i> Manage Reviews</a>
        <a href="../upload_resource.php"><i class="fa-solid fa-upload"></i> Upload Resource</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div style="margin-left: 250px; padding: 20px;">


        <!-- Dashboard Overview -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo $total_helpers; ?></h3>
                    <p>Total Helpers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo $total_tasks; ?></h3>
                    <p>Total Tasks</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo $total_reviews; ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="stat-card text-center">
                    <h3>$<?php echo number_format($total_earnings, 2); ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card text-center">
                    <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue (10% Commission)</p>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>, // Pass PHP array to JS
                datasets: [{
                    label: 'Monthly Revenue',
                    data: <?php echo json_encode($revenues); ?>, // Pass PHP array to JS
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    </script>
</body>
</html>
