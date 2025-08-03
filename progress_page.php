<?php
session_start();
include 'dbconnect.php'; // Include database connection


// Check if the user or helper is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to view task progress.'); window.location.href = 'login.php';</script>";
    exit;
}


// Determine user type
$user_id = $_SESSION['user_id'] ?? null;
$helper_id = $_SESSION['helper_id'] ?? null;


// Fetch tasks based on user or helper
if ($user_id) {
    // Fetch tasks posted by the logged-in user
    $query = "SELECT hr.hiring_id, t.title, t.description, hr.status, h.name AS helper_name, hr.start_time, hr.end_time
              FROM hiring_records hr
              JOIN tasks t ON hr.task_id = t.task_id
              JOIN helpers h ON hr.helper_id = h.helper_id
              WHERE hr.user_id = $user_id";
} else {
    // Fetch tasks assigned to the logged-in helper
    $query = "SELECT hr.hiring_id, t.title, t.description, hr.status, u.name AS user_name, hr.start_time, hr.end_time
              FROM hiring_records hr
              JOIN tasks t ON hr.task_id = t.task_id
              JOIN users u ON hr.user_id = u.user_id
              WHERE hr.helper_id = $helper_id";
}


$result = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Progress</title>
    <style>
        .progress-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }


        .progress-card {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }


        .progress-card h3 {
            margin: 0;
            font-size: 18px;
        }


        .action-button {
            padding: 10px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }


        .mark-completed {
            background-color: #4caf50;
            color: white;
        }


        .mark-completed:hover {
            background-color: #43a047;
        }


        .mark-in-progress {
            background-color: #007bff;
            color: white;
        }


        .mark-in-progress:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include"header_user.php"?>
    <div class="progress-container">
        <h1>Task Progress</h1>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="progress-card">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($row['status'])); ?></p>
                    <?php if ($user_id): ?>
                        <p><strong>Helper:</strong> <?php echo htmlspecialchars($row['helper_name']); ?></p>
                    <?php else: ?>
                        <p><strong>User:</strong> <?php echo htmlspecialchars($row['user_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($row['start_time']): ?>
                        <p><strong>Start Time:</strong> <?php echo htmlspecialchars($row['start_time']); ?></p>
                    <?php endif; ?>
                    <?php if ($row['end_time']): ?>
                        <p><strong>End Time:</strong> <?php echo htmlspecialchars($row['end_time']); ?></p>
                    <?php endif; ?>


                    <!-- Allow status update if task is in_progress -->
                    <?php if ($row['status'] === 'in_progress'): ?>
                        <form method="POST" action="update_progress.php">
                            <input type="hidden" name="hiring_id" value="<?php echo $row['hiring_id']; ?>">
                            <button type="submit" name="status" value="completed" class="action-button mark-completed">Mark as Completed</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tasks in progress.</p>
        <?php endif; ?>
    </div>

</body>
</html>



