<?php
session_start();
include 'dbconnect.php';

// Fetch helpers sorted by affordability (hourly rate)
$sql = "
    SELECT 
        h.helper_id, 
        h.name, 
        h.skills, 
        h.rating, 
        h.address, 
        h.phone_number,
        MIN(hr.hourly_rate) AS hourly_rate
    FROM 
        helpers h
    LEFT JOIN 
        hiring_records hr ON h.helper_id = hr.helper_id
    WHERE h.status = 'active'
    GROUP BY h.helper_id
    ORDER BY hourly_rate ASC, h.rating DESC;
";

$result = $conn->query($sql);
$helpers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affordable Helpers - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .helper-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px;
        }
        .helper-card {
            flex: 1 1 calc(33% - 40px);
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 300px;
        }
        .helper-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        .helper-card p {
            margin: 5px 0;
            color: #555;
        }
        .hire-button {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .hire-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include 'header_user.php'; ?>
    <main>
        <h1 style="text-align: center; margin-bottom: 20px;">Affordable Helpers</h1>
        <div class="helper-list">
            <?php if (!empty($helpers)): ?>
                <?php foreach ($helpers as $helper): ?>
                    <div class="helper-card">
                        <h3><?php echo htmlspecialchars($helper['name']); ?></h3>
                        <p>Skills: <?php echo htmlspecialchars($helper['skills']); ?></p>
                        <p>Rating: <?php echo number_format($helper['rating'], 2); ?></p>
                        <p>Location: <?php echo htmlspecialchars($helper['address']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($helper['phone_number']); ?></p>
                        <p>Hourly Rate: $<?php echo number_format($helper['hourly_rate'], 2); ?></p>
                        <a href="hire_helper.php?helper_id=<?php echo $helper['helper_id']; ?>" class="hire-button">Hire</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No affordable helpers found.</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
