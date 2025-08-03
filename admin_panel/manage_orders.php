<?php
// The header includes our session, database connection, and basic page structure.
include 'admin_header.php';

// --- Page-Specific Logic for Managing Orders ---

// Handle the POST request when an admin updates an order's status.
if (isset($_POST['update_order_status'])) {
    $order_id_to_update = (int)$_POST['order_id'];
    $new_status = $conn->real_escape_string($_POST['order_status']);
    
    // Use a prepared statement to securely update the database.
    $update_stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id_to_update);
    
    if ($update_stmt->execute()) {
        $success_message = "Order #" . $order_id_to_update . " status has been updated.";
    } else {
        $error_message = "Failed to update order status.";
    }
} // <<< FIX: This is the missing closing brace that caused the parse error.

// --- Data Fetching ---

// Fetch all orders, joining with the users table to get the customer's name.
$query = "SELECT o.order_id, u.name as customer_name, o.total_amount, o.order_status, o.created_at
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id 
          ORDER BY o.created_at DESC";
$result = $conn->query($query);
?>

<!-- The main container for our page content -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Marketplace Orders</h1>
    
    <!-- Display success or error messages from the PHP logic above -->
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Main Content Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Orders</h6>
        </div>
        <div class="card-body">
            <!-- This div makes the table scroll horizontally on small screens -->
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo date("M d, Y, h:i A", strtotime($row['created_at'])); ?></td>
                            <td>
                                <!-- Use a switch statement for clear, colored status badges -->
                                <span class="badge bg-<?php 
                                    switch($row['order_status']) {
                                        case 'paid': echo 'info'; break;
                                        case 'shipped': echo 'primary'; break;
                                        case 'completed': echo 'success'; break;
                                        case 'failed': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst($row['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Form for updating the status of this specific order -->
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                    <select name="order_status" class="form-select form-select-sm">
                                        <option value="paid" <?php echo ($row['order_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="shipped" <?php echo ($row['order_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="completed" <?php echo ($row['order_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="failed" <?php echo ($row['order_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                    <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">Save</button>
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

<?php
// The footer closes all the HTML tags and includes necessary scripts.
include 'admin_footer.php'; 
?>