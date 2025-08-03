<?php
session_start();
include 'dbconnect.php';

// Check if the hiring ID is provided
if (!isset($_GET['hiring_id'])) {
    die("Hiring ID not provided.");
}

$hiring_id = intval($_GET['hiring_id']);

// Fetch hiring record details
$sql = "
    SELECT 
        hr.hiring_id, 
        hr.start_time, 
        hr.end_time, 
        hr.total_hours, 
        hr.hourly_rate, 
        (hr.total_hours * hr.hourly_rate) AS total_fee,
        ((hr.total_hours * hr.hourly_rate) * 0.10) AS platform_fee,
        ((hr.total_hours * hr.hourly_rate) - ((hr.total_hours * hr.hourly_rate) * 0.10)) AS payable_amount,
        u.name AS user_name,
        h.name AS helper_name,
        h.email AS helper_email,
        h.phone_number AS helper_phone,
        h.skills AS helper_skills,
        h.rating AS helper_rating,
        t.title AS task_title,
        t.description AS task_description,
        p.payment_method,
        p.status AS payment_status
    FROM hiring_records hr
    JOIN users u ON hr.user_id = u.user_id
    JOIN helpers h ON hr.helper_id = h.helper_id
    LEFT JOIN tasks t ON hr.task_id = t.task_id
    LEFT JOIN payments p ON hr.hiring_id = p.hiring_id
    WHERE hr.hiring_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hiring_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiring Details</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .details-container {
            max-width: 800px;
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

        .details-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-container table th,
        .details-container table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .details-container table th {
            background-color: #333;
            color: white;
        }
    </style>
</head>
<body>
    <?php include('header_user.php'); ?> <!-- Include the navbar -->

    <div class="details-container">
        <h2>Hiring Details (ID: <?php echo $details['hiring_id']; ?>)</h2>
        <table>
            <tr><th>Task Title</th><td><?php echo htmlspecialchars($details['task_title']); ?></td></tr>
            <tr><th>Task Description</th><td><?php echo htmlspecialchars($details['task_description']); ?></td></tr>
            <tr><th>Start Time</th><td><?php echo $details['start_time']; ?></td></tr>
            <tr><th>End Time</th><td><?php echo $details['end_time']; ?></td></tr>
            <tr><th>Total Hours</th><td><?php echo $details['total_hours']; ?></td></tr>
            <tr><th>Hourly Rate</th><td>$<?php echo number_format($details['hourly_rate'], 2); ?></td></tr>
            <tr><th>Total Fee</th><td>$<?php echo number_format($details['total_fee'], 2); ?></td></tr>
            <tr><th>Platform Fee</th><td>$<?php echo number_format($details['platform_fee'], 2); ?></td></tr>
            <tr><th>Payable Amount (To Helper)</th><td>$<?php echo number_format($details['payable_amount'], 2); ?></td></tr>
            <tr><th>Payment Status</th><td><?php echo ucfirst($details['payment_status']); ?></td></tr>
            <tr><th>Helper Name</th><td><?php echo htmlspecialchars($details['helper_name']); ?></td></tr>
            <tr><th>Helper Email</th><td><?php echo htmlspecialchars($details['helper_email']); ?></td></tr>
            <tr><th>Helper Phone</th><td><?php echo htmlspecialchars($details['helper_phone']); ?></td></tr>
            <tr><th>Helper Skills</th><td><?php echo htmlspecialchars($details['helper_skills']); ?></td></tr>
            <tr><th>Helper Rating</th><td><?php echo number_format($details['helper_rating'], 2); ?></td></tr>
        </table>
    </div>
    <script src="EyeTracking.js"></script>

</body>
</html>
