<?php
include 'admin_header.php';

if (isset($_POST['update_helper'])) {
    $helper_id_to_update = (int)$_POST['helper_id'];
    $new_status = $conn->real_escape_string($_POST['status']);
    $new_verification = $conn->real_escape_string($_POST['verification_status']);
    
    $update_stmt = $conn->prepare("UPDATE helpers SET status = ?, verification_status = ?, verified_by = ? WHERE helper_id = ?");
    $update_stmt->bind_param("ssii", $new_status, $new_verification, $current_admin_id, $helper_id_to_update);
    if ($update_stmt->execute()) $success_message = "Helper status updated.";
    else $error_message = "Update failed.";
}

$search_term = $_GET['search'] ?? '';
$query = "SELECT helper_id, name, email, rating, verification_status, status FROM helpers WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$search_like = "%" . $search_term . "%";
$stmt->bind_param("ss", $search_like, $search_like);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Helpers</h1>
    <?php if(isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
    <?php if(isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Helper Accounts</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Rating</th><th>Status</th><th>Verification</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['helper_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo number_format($row['rating'], 2); ?> ★</td>
                            <td><span class="badge bg-<?php echo ($row['status'] == 'active') ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><span class="badge bg-<?php echo ($row['verification_status'] == 'verified') ? 'success' : (($row['verification_status'] == 'pending') ? 'warning' : 'danger'); ?>"><?php echo ucfirst($row['verification_status']); ?></span></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="helper_id" value="<?php echo $row['helper_id']; ?>">
                                    <select name="status" class="form-select form-select-sm"><option value="active" <?php echo ($row['status'] == 'active') ? 'selected' : ''; ?>>Active</option><option value="suspended" <?php echo ($row['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option></select>
                                    <select name="verification_status" class="form-select form-select-sm"><option value="pending" <?php echo ($row['verification_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option><option value="verified" <?php echo ($row['verification_status'] == 'verified') ? 'selected' : ''; ?>>Verified</option><option value="rejected" <?php echo ($row['verification_status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option></select>
                                    <button type="submit" name="update_helper" class="btn btn-primary btn-sm">Save</button>
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