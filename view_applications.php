<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to view applicants.'); window.location.href = 'login.php';</script>";
    exit;
}

$task_id = (int)$_GET['task_id'];
$_SESSION['task_id'] = $task_id; // Store task_id in session
$user_id = $_SESSION['user_id'];

// Verify that the task belongs to the logged-in user
$check_query = "SELECT * FROM tasks WHERE task_id = $task_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if ($check_result->num_rows === 0) {
    echo "<script>alert('You do not have access to view applicants for this task.'); window.location.href = 'user_posted_jobs.php';</script>";
    exit;
}

// Fetch all applicants for this task
$query = "SELECT hd.decision_id, h.helper_id, h.name, h.phone_number, h.skills, h.rating, hd.decision_status
          FROM hiring_decisions hd
          JOIN helpers h ON hd.selected_helper_id = h.helper_id
          WHERE hd.task_id = $task_id";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants for Task</title>
    <style>
        .applicants-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .applicant-card {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .applicant-card h3 {
            margin: 0;
            font-size: 18px;
        }

        .approve-button, .reject-button, .chat-button {
            padding: 10px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .chat-button {
            background-color: rgb(255, 188, 4);
            color: white;
        }

        .approve-button {
            background-color: #4caf50;
            color: white;
        }

        .reject-button {
            background-color: #f44336;
            color: white;
        }

        .approve-button:hover {
            background-color: #43a047;
        }

        .reject-button:hover {
            background-color: #e53935;
        }
    </style>
</head>
<body>
<?php include"header_user.php"?>
    <div class="applicants-container">
        <h1>Applicants for Task</h1>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="applicant-card">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($row['phone_number']); ?></p>
                    <p><strong>Skills:</strong> <?php echo htmlspecialchars($row['skills']); ?></p>
                    <p><strong>Rating:</strong> <?php echo htmlspecialchars($row['rating']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($row['decision_status'])); ?></p>
                    <form method="POST" action="update_decision.php">
                        <input type="hidden" name="decision_id" value="<?php echo $row['decision_id']; ?>">
                        <input type="hidden" name="helper_id" value="<?php echo $row['helper_id']; ?>">
                        <button type="submit" name="action" value="approve" class="approve-button">Approve</button>
                        <button type="submit" name="action" value="reject" class="reject-button">Reject</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No applicants for this task yet.</p>
        <?php endif; ?>
    </div>
    <script src="EyeTracking.js"></script>

</body>
</html>
