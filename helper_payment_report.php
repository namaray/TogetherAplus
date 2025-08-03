<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch helper-wise payment summary
$sql = "
    SELECT 
        h.helper_id,
        h.name AS helper_name,
        h.email AS helper_email,
        SUM(hr.total_hours * hr.hourly_rate) AS total_paid,
        COUNT(hr.hiring_id) AS total_tasks,
        AVG(hr.hourly_rate) AS average_hourly_rate
    FROM hiring_records hr
    JOIN helpers h ON hr.helper_id = h.helper_id
    WHERE hr.user_id = ?
    GROUP BY h.helper_id, h.name, h.email
    ORDER BY total_paid DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$helper_payments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helper Payment Report</title>
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
        <h2>Helper Payment Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Helper Name</th>
                    <th>Helper Email</th>
                    <th>Total Paid ($)</th>
                    <th>Total Tasks</th>
                    <th>Average Hourly Rate ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($helper_payments) > 0): ?>
                    <?php foreach ($helper_payments as $helper): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($helper['helper_name']); ?></td>
                            <td><?php echo htmlspecialchars($helper['helper_email']); ?></td>
                            <td><?php echo number_format($helper['total_paid'], 2); ?></td>
                            <td><?php echo $helper['total_tasks']; ?></td>
                            <td><?php echo number_format($helper['average_hourly_rate'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-records">No payment records found for helpers.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
