<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all tasks
$tasks_query = "SELECT t.task_id, t.title, t.description, t.skill_required, t.hourly_rate, u.name AS user_name 
                FROM tasks t 
                JOIN users u ON t.user_id = u.user_id 
                ORDER BY t.created_at DESC";

$tasks = $conn->query($tasks_query);

if (!$tasks) {
    die("Error fetching tasks: " . $conn->error);
}

// Handle task deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $task_id = (int)$_POST['task_id'];

    // Delete the task
    $delete_query = "DELETE FROM tasks WHERE task_id = $task_id";
    if ($conn->query($delete_query)) {
        // Redirect after successful deletion
        header('Location: tasks.php');
        exit;
    } else {
        // Log and display error if query fails
        error_log("Error deleting task with ID $task_id: " . $conn->error);
        $error = "Unable to delete task. Please ensure the task has no related records.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            width: 83%;
            
        }

        .container {
            margin-top: 0px;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            
        }

        h1 {
            font-size: 28px;
            color: #333;
            font-weight: bold;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .btn {
            transition: background-color 0.3s ease;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .alert {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php include"header_admin.php"?>
    <div class="container"style="margin-left: 250px; padding: 20px;">
        <h1 class="mb-4">Manage Tasks</h1>

        <!-- Display Error Message if Deletion Fails -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Task Table -->
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Task Title</th>
                    <th>Description</th>
                    <th>Skill Required</th>
                    <th>Hourly Rate</th>
                    <th>Posted By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $tasks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['skill_required']); ?></td>
                        <td>$<?php echo htmlspecialchars($row['hourly_rate']); ?>/hr</td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td>
                            <!-- Delete Button -->
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
                                <button name="delete" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
