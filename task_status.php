<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in as a helper to view your tasks.'); window.location.href = 'login.php';</script>";
    exit;
}

$helper_id = $_SESSION['helper_id'];

// Fetch tasks the helper has applied for
$query = "SELECT t.title, t.description, t.skill_required, 
                 IF(t.hourly_rate IS NOT NULL, CONCAT('$', t.hourly_rate, ' /hr'), CONCAT('$', t.fixed_rate)) AS rate,
                 hr.status 
          FROM hiring_records hr
          JOIN tasks t ON hr.task_id = t.task_id
          WHERE hr.helper_id = $helper_id";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Status</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="task-container">
        <h1>Task Status</h1>
        <div class="task-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="task-card">
                        <div class="task-details">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                            <p><strong>Skill Required:</strong> <?php echo htmlspecialchars($row['skill_required']); ?></p>
                            <p><strong>Rate:</strong> <?php echo htmlspecialchars($row['rate']); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($row['status'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tasks found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
