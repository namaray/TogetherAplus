<?php
session_start();
include 'dbconnect.php'; // Include your database connection

// Check if the helper is logged in
if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in as a helper to view available tasks.'); window.location.href = 'login.php';</script>";
    exit;
}

// Fetch tasks from the database
$helper_id = $_SESSION['helper_id'];

$query = "SELECT t.task_id, t.title, t.user_id, t.description, t.skill_required, 
                 CONCAT('$', t.hourly_rate, ' /hr') AS rate, 
                 (SELECT COUNT(*) FROM hiring_records hr WHERE hr.task_id = t.task_id AND hr.helper_id = $helper_id) AS is_applied
          FROM tasks t 
          WHERE t.hourly_rate IS NOT NULL 
          ORDER BY t.created_at DESC";

$result = $conn->query($query);

// Check for query errors
if (!$result) {
    error_log("Error fetching tasks: " . $conn->error);
    echo "<p>Unable to fetch tasks at the moment. Please try again later.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tasks</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .logo img {
            height: 50px;
        }

        header nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        header nav ul li {
            margin: 0 15px;
        }

        header nav ul li a {
            text-decoration: none;
            color: white;
        }

        .task-container {
            padding: 20px;
            max-width: 1200px;
            margin: 30px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .task-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .task-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .task-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .task-details {
            flex: 1;
            margin-right: 20px;
            color: #333;
        }

        .task-details h3 {
            margin: 0;
            font-size: 18px;
        }

        .apply-button {
            padding: 10px 20px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .apply-button:hover {
            background-color: #43a047;
        }

        .applied-button {
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: not-allowed;
        }

        footer {
            text-align: center;
            padding: 10px;
            background-color: #007bff;
            color: white;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include "header_helper.php" ?>

    <div class="task-container">
        <h1 class="task-header">Available Tasks</h1>
        <div class="task-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="task-card">
                        <div class="task-details">
                            <h3>Posted by User ID: <?php echo htmlspecialchars($row['user_id']); ?></h3>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                            <p><strong>Skill Required:</strong> <?php echo htmlspecialchars($row['skill_required']); ?></p>
                            <p><strong>Rate:</strong> <?php echo htmlspecialchars($row['rate']); ?></p>
                        </div>
                        <?php if ($row['is_applied'] > 0): ?>
                            <button class="applied-button" disabled>Applied</button>
                        <?php else: ?>
                            <form method="POST" action="apply_task.php">
                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($row['task_id']); ?>">
                                <button type="submit" class="apply-button">Apply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tasks available.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 TogetherA+. All rights reserved.</p>
    </footer>
    <script src="EyeTracking.js"></script>

</body>

</html>
