<?php
include 'admin_header.php';

if (isset($_POST['delete_task'])) {
    $task_id_to_delete = (int)$_POST['task_id'];
    $delete_stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
    $delete_stmt->bind_param("i", $task_id_to_delete);
    if ($delete_stmt->execute()) $success_message = "Task deleted.";
    else $error_message = "Delete failed. Task may be linked to hiring records.";
}

$query = "SELECT t.task_id, t.title, t.skill_required, t.hourly_rate, u.name as user_name 
          FROM tasks t JOIN users u ON t.user_id = u.user_id 
          ORDER BY t.created_at DESC";
$result = $conn->query($query);
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Helper Tasks</h1>
    <?php if(isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
    <?php if(isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Posted Tasks</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>ID</th><th>Title</th><th>Posted By</th><th>Skill</th><th>Rate</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['task_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['skill_required']); ?></td>
                            <td>$<?php echo number_format($row['hourly_rate'], 2); ?>/hr</td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
                                    <button type="submit" name="delete_task" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?>