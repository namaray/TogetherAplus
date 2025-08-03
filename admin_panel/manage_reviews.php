<?php
include 'admin_header.php';

if (isset($_POST['delete_review'])) {
    $review_id_to_delete = (int)$_POST['review_id'];
    $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $delete_stmt->bind_param("i", $review_id_to_delete);
    if ($delete_stmt->execute()) $success_message = "Review deleted.";
    else $error_message = "Delete failed.";
}

$query = "SELECT r.review_id, r.rating, r.comment, u.name as user_name, h.name as helper_name
          FROM reviews r 
          JOIN users u ON r.user_id = u.user_id 
          JOIN helpers h ON r.helper_id = h.helper_id 
          ORDER BY r.created_at DESC";
$result = $conn->query($query);
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Reviews</h1>
    <?php if(isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">All Reviews</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Rating</th><th>Comment</th><th>Review By (User)</th><th>Review For (Helper)</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo number_format($row['rating'], 1); ?> ★</td>
                            <td><?php echo htmlspecialchars($row['comment']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['helper_name']); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this review?');">
                                    <input type="hidden" name="review_id" value="<?php echo $row['review_id']; ?>">
                                    <button type="submit" name="delete_review" class="btn btn-danger btn-sm">Delete</button>
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