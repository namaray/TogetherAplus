<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_admin_id = $_SESSION['admin_id']; // Current logged-in admin's ID

// Initialize search query
$search_query = "";

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $helpers_query = "SELECT * FROM helpers 
                      WHERE name LIKE '%$search_query%' 
                         OR email LIKE '%$search_query%' 
                         OR phone_number LIKE '%$search_query%' 
                         OR helper_id LIKE '%$search_query%'";
} else {
    // Default query to fetch all helpers
    $helpers_query = "SELECT * FROM helpers";
}

$helpers_result = $conn->query($helpers_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_helper'])) {
        $helper_id = (int)$_POST['helper_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $verification_status = $conn->real_escape_string($_POST['verification_status']);

        // Update helper status, verification status, and verified_by
        $update_query = "UPDATE helpers 
                         SET status = '$status', 
                             verification_status = '$verification_status', 
                             verified_by = $current_admin_id, 
                             updated_at = NOW() 
                         WHERE helper_id = $helper_id";
        if ($conn->query($update_query)) {
            $success = "Helper updated successfully!";
        } else {
            $error = "Error updating helper: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Helpers</title>
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

        .search-bar {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            margin-right: 10px;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .btn {
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>
<?php include"header_admin.php"?>
    <div class="container" style="margin-left: 250px; padding: 20px;">
        <h1 class="mb-4">Manage Helpers</h1>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <form method="GET" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search by Name, Email, Phone, or ID" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>

        <!-- Helpers Table -->
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Skills</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th>Verified By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($helper = $helpers_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($helper['helper_id']); ?></td>
                        <td><?php echo htmlspecialchars($helper['name']); ?></td>
                        <td><?php echo htmlspecialchars($helper['email']); ?></td>
                        <td><?php echo htmlspecialchars($helper['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($helper['skills']); ?></td>
                        <td><?php echo number_format($helper['rating'], 2); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($helper['status'])); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($helper['verification_status'])); ?></td>
                        <td>
                            <?php
                            if ($helper['verified_by']) {
                                // Fetch the admin name who verified the helper
                                $verifier_query = "SELECT name FROM admins WHERE admin_id = " . (int)$helper['verified_by'];
                                $verifier_result = $conn->query($verifier_query);
                                $verifier = $verifier_result->fetch_assoc();
                                echo htmlspecialchars($verifier['name'] ?? 'Unknown');
                            } else {
                                echo 'Not Verified';
                            }
                            ?>
                        </td>
                        <td>
                            <!-- Edit Helper -->
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="helper_id" value="<?php echo $helper['helper_id']; ?>">
                                <select name="status" class="form-select form-select-sm d-inline" style="width:auto;">
                                    <option value="active" <?php echo $helper['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $helper['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="deactivated" <?php echo $helper['status'] === 'deactivated' ? 'selected' : ''; ?>>Deactivated</option>
                                </select>
                                <select name="verification_status" class="form-select form-select-sm d-inline" style="width:auto;">
                                    <option value="pending" <?php echo $helper['verification_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="verified" <?php echo $helper['verification_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="rejected" <?php echo $helper['verification_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" name="update_helper" class="btn btn-success btn-sm mt-2">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
