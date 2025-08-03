<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch task-wise payment breakdown
$sql = "
    SELECT 
        t.title AS task_title,
        t.description AS task_description,
        hr.hiring_id,
        hr.total_hours,
        hr.hourly_rate,
        (hr.total_hours * hr.hourly_rate) AS total_fee,
        ((hr.total_hours * hr.hourly_rate) * 0.10) AS platform_fee,
        ((hr.total_hours * hr.hourly_rate) - ((hr.total_hours * hr.hourly_rate) * 0.10)) AS payable_amount,
        h.name AS helper_name
    FROM hiring_records hr
    JOIN tasks t ON hr.task_id = t.task_id
    JOIN helpers h ON hr.helper_id = h.helper_id
    WHERE hr.user_id = ?
    ORDER BY hr.hiring_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$task_payments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task-Based Payment Breakdown</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .details-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .details-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #333;
            color: white;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'header_user.php'; ?>
    <div class="details-container">
        <h2>Task-Based Payment Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Task Description</th>
                    <th>Hiring ID</th>
                    <th>Helper</th>
                    <th>Hours</th>
                    <th>Hourly Rate</th>
                    <th>Total Fee</th>
                    <th>Platform Fee</th>
                    <th>Payable Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($task_payments) > 0): ?>
                    <?php foreach ($task_payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['task_title']); ?></td>
                            <td><?php echo htmlspecialchars($payment['task_description']); ?></td>
                            <td><?php echo $payment['hiring_id']; ?></td>
                            <td><?php echo htmlspecialchars($payment['helper_name']); ?></td>
                            <td><?php echo $payment['total_hours']; ?></td>
                            <td>$<?php echo number_format($payment['hourly_rate'], 2); ?></td>
                            <td>$<?php echo number_format($payment['total_fee'], 2); ?></td>
                            <td>$<?php echo number_format($payment['platform_fee'], 2); ?></td>
                            <td>$<?php echo number_format($payment['payable_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-records">No task payments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
