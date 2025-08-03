<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to view your jobs.'); window.location.href = 'login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all tasks posted by the user
$query = "SELECT * FROM tasks WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include"header_user.php"?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Posted Jobs</title>
    <style>
        .task-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .task-card {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .task-card h3 {
            margin: 0;
            font-size: 18px;
        }

        .view-applicants-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .view-applicants-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="task-container">
        <h1>Your Posted Jobs</h1>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="task-card">
                    <h3>Task ID: <?php echo htmlspecialchars($row['task_id']); ?></h3>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($row['created_at']); ?></p>
                    <a href="view_applications.php?task_id=<?php echo $row['task_id']; ?>" class="view-applicants-button">View Applicants</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No jobs posted yet.</p>
        <?php endif; ?>
    </div>
    <script src="EyeTracking.js"></script>

</body>
</html>
