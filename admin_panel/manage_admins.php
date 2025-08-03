 <?php
// This includes the header and defines the CORRECT variable: $current_admin_id
include 'admin_header.php';

// SECURITY: This page is for Super Admins only.
if ($current_admin_role !== 'super_admin') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission for this page.</div>";
    include 'admin_footer.php';
    exit;
}

// --- LOGIC TO HANDLE FORM SUBMISSIONS ---

// Handle role update form submission
if (isset($_POST['update_role'])) {
    $admin_id_to_update = (int)$_POST['admin_id'];
    $new_role = $conn->real_escape_string($_POST['role']);

    // This is the FIRST place the variable is used. It's now spelled correctly.
    if ($admin_id_to_update === $current_admin_id) {
        $error_message = "Error: You cannot modify your own role.";
    } else {
        $update_stmt = $conn->prepare("UPDATE admins SET role = ? WHERE admin_id = ?");
        $update_stmt->bind_param("si", $new_role, $admin_id_to_update);
        if ($update_stmt->execute()) {
            $success_message = "Admin role updated successfully.";
        } else {
            $error_message = "Failed to update role.";
        }
    }
}

// Handle new admin registration form submission
if (isset($_POST['add_admin'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error_message = "All fields are required to add a new admin.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssss", $name, $email, $password_hash, $role);
        if ($insert_stmt->execute()) {
            $success_message = "New admin registered successfully.";
        } else {
            $error_message = "Registration failed: This email might already exist.";
        }
    }
}

// --- DATA FETCHING ---
// Fetch all admins to display in the table
$result = $conn->query("SELECT admin_id, name, email, role FROM admins ORDER BY name ASC");
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Administrators</h1>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Admin List Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Administrator Accounts</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['admin_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?></span></td>
                                    <td>
                                        <?php
                                        // This is the SECOND place the variable is used. Also now spelled correctly.
                                        if ($row['admin_id'] !== $current_admin_id): 
                                        ?>
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="admin_id" value="<?php echo $row['admin_id']; ?>">
                                            <select name="role" class="form-select form-select-sm">
                                                <option value="moderator" <?php echo ($row['role'] == 'moderator') ? 'selected' : ''; ?>>Moderator</option>
                                                <option value="super_admin" <?php echo ($row['role'] == 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                                            </select>
                                            <button type="submit" name="update_role" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Cannot edit self</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Admin Column -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Admin</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Role</label><select name="role" class="form-select" required><option value="moderator">Moderator</option><option value="super_admin">Super Admin</option></select></div>
                        <button type="submit" name="add_admin" class="btn btn-success w-100">Add Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?>