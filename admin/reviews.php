<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all reviews
$reviews = $conn->query("SELECT r.review_id, r.rating, r.comment, u.name AS user_name, h.name AS helper_name 
                         FROM reviews r 
                         JOIN users u ON r.user_id = u.user_id 
                         JOIN helpers h ON r.helper_id = h.helper_id 
                         ORDER BY r.created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $review_id = (int)$_POST['review_id'];
    $conn->query("DELETE FROM reviews WHERE review_id = $review_id");
    header('Location: reviews.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
    </style>
</head>
<body>
<?php include"header_admin.php"?>
    <div class="container"style="margin-left: 250px; padding: 20px;">
        <h1 class="mb-4">Manage Reviews</h1>

        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>User</th>
                    <th>Helper</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reviews->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['helper_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['rating']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="review_id" value="<?php echo $row['review_id']; ?>">
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
