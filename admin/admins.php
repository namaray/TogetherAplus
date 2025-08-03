<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_admin_role = $_SESSION['role']; // Current admin's role
$current_admin_id = $_SESSION['admin_id']; // Current admin's ID

// Initialize search query
$search_query = "";

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $admins_query = "SELECT * FROM admins 
                     WHERE name LIKE '%$search_query%' 
                        OR email LIKE '%$search_query%' 
                        OR admin_id LIKE '%$search_query%'";
} else {
    // Default query to fetch all admins
    $admins_query = "SELECT * FROM admins";
}

$admins_result = $conn->query($admins_query);

// Handle form submissions for Super Admins
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_admin_role === 'super_admin') {
    if (isset($_POST['edit_role'])) {
        $admin_id = (int)$_POST['admin_id'];
        $new_role = $conn->real_escape_string($_POST['role']);

        // Prevent changing your own role
        if ($admin_id === $current_admin_id) {
            $error = "You cannot modify your own role.";
        } else {
            $update_query = "UPDATE admins SET role = '$new_role' WHERE admin_id = $admin_id";
            if ($conn->query($update_query)) {
                $success = "Role updated successfully!";
            } else {
                $error = "Error updating role: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .alert {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<?php include"header_admin.php"?>
    <div class="container" style="margin-left: 250px; padding: 20px;">
        <h1 class="mb-4">Manage Admins</h1>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <form method="GET" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search by Name, Email, or ID" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>

        <!-- Admin List -->
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <?php if ($current_admin_role === 'super_admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php while ($admin = $admins_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                        <td><?php echo htmlspecialchars($admin['name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo ucfirst($admin['role']); ?></td>
                        <?php if ($current_admin_role === 'super_admin'): ?>
                            <td>
                                <!-- Modify Role -->
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                    <select name="role" class="form-select form-select-sm d-inline" style="width:auto;">
                                        <option value="moderator" <?php echo $admin['role'] === 'moderator' ? 'selected' : ''; ?>>Moderator</option>
                                        <option value="super_admin" <?php echo $admin['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    </select>
                                    <button type="submit" name="edit_role" class="btn btn-success btn-sm mt-2">Update Role</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
