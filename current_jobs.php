<?php
session_start();
include 'dbconnect.php';

// Ensure the helper is logged in
if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to view your current jobs.'); window.location.href = 'login.php';</script>";
    exit;
}

$helper_id = $_SESSION['helper_id'];

// Fetch hourly-based jobs
$hourly_query = "SELECT hr.hiring_id, t.title, t.description, t.skill_required, t.hourly_rate, 
                        u.name AS user_name, u.phone_number, hr.start_time, hr.logged_hours, hr.status
                 FROM hiring_records hr
                 JOIN tasks t ON hr.task_id = t.task_id
                 JOIN users u ON hr.user_id = u.user_id
                 WHERE hr.helper_id = $helper_id AND t.hourly_rate IS NOT NULL 
                 ORDER BY hiring_id DESC";

$hourly_jobs = $conn->query($hourly_query);

// Fetch total hours and earnings
$total_query = "SELECT SUM(logged_hours) AS total_hours, 
                       SUM(logged_hours * t.hourly_rate) AS total_earnings
                FROM hiring_records hr
                JOIN tasks t ON hr.task_id = t.task_id
                WHERE hr.helper_id = $helper_id AND hr.status = 'completed'";

$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc();
$total_hours = $total_data['total_hours'] ?? 0;
$total_earnings = $total_data['total_earnings'] ?? 0;

// Check for query errors
if (!$hourly_jobs) {
    error_log("Error fetching hourly jobs: " . $conn->error);
    echo "<p>Unable to fetch jobs at the moment. Please try again later.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Jobs</title>
    <style>
        .current-jobs-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .job-card {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .summary {
            margin: 20px 0;
            font-size: 16px;
        }

        .action-button {
            padding: 10px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .start-task {
            background-color: #007bff;
            color: white;
        }

        .start-task:hover {
            background-color: #0056b3;
        }

        .end-task {
            background-color: #4caf50;
            color: white;
        }

        .end-task:hover {
            background-color: #43a047;
        }

        .review-button {
            background-color: #ffc107;
            color: black;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .review-button:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
<?php include "header_helper.php"?>
    <div class="current-jobs-container">
        <h1>Your Current Jobs</h1>

        <!-- Summary Section -->
        <div class="summary">
            <p><strong>Total Hours Worked:</strong> <?php echo htmlspecialchars($total_hours); ?> hours</p>
            <p><strong>Total Earnings:</strong> $<?php echo htmlspecialchars(number_format($total_earnings, 2)); ?></p>
        </div>

        <!-- Hourly-Based Jobs -->
        <?php if ($hourly_jobs->num_rows > 0): ?>
            <?php while ($row = $hourly_jobs->fetch_assoc()): ?>
                <div class="job-card">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    <p><strong>Skill Required:</strong> <?php echo htmlspecialchars($row['skill_required']); ?></p>
                    <p><strong>Hourly Rate:</strong> $<?php echo htmlspecialchars($row['hourly_rate']); ?></p>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($row['user_name']); ?> (<?php echo htmlspecialchars($row['phone_number']); ?>)</p>
                    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($row['status'])); ?></p>
                    <p><strong>Start Time:</strong> <?php echo htmlspecialchars($row['start_time']); ?></p>
                    <p><strong>Logged Hours:</strong> <?php echo htmlspecialchars($row['logged_hours']); ?> hours</p>
                    
                    <!-- Conditional Buttons -->
                    <?php if ($row['status'] === 'in_progress'): ?>
                        <form method="POST" action="track_work2.php">
                            <input type="hidden" name="hiring_id" value="<?php echo $row['hiring_id']; ?>">
                            <button type="submit" name="action" value="start" class="action-button start-task">Start Work</button>
                            <button type="submit" name="action" value="end" class="action-button end-task">End Work</button>
                        </form>
                    <?php elseif ($row['status'] === 'completed'): ?>
                        <form method="GET" action="give_review_to_user.php">
                            <input type="hidden" name="hiring_id" value="<?php echo $row['hiring_id']; ?>">
                            
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hourly-based jobs at the moment.</p>
        <?php endif; ?>
    </div>

</body>
</html>