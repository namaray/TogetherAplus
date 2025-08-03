<?php
session_start();
include 'dbconnect.php';

// Fetch currently available helpers
$sql = "
    SELECT 
        h.helper_id, 
        h.name, 
        h.skills, 
        h.rating, 
        h.address, 
        h.phone_number,
        CASE 
            WHEN 
                (SELECT COUNT(*) FROM hiring_records hr2 WHERE hr2.helper_id = h.helper_id AND hr2.status = 'in_progress') = 0 
            THEN 'Available'
            ELSE 'Unavailable'
        END AS availability
    FROM 
        helpers h
    WHERE h.status = 'active'
    HAVING availability = 'Available'
    ORDER BY h.rating DESC;
";

$result = $conn->query($sql);
$helpers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Helpers - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .helper-list { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin: 20px; }
        .helper-card { flex: 1 1 calc(33% - 40px); background-color: #f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); text-align: center; max-width: 300px; }
        .helper-card h3 { font-size: 18px; margin-bottom: 10px; color: #333; }
        .helper-card p { margin: 5px 0; color: #555; }
        .hire-button { display: inline-block; margin-top: 10px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .hire-button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <?php include 'header_user.php'; ?>
    <main>
        <h1 style="text-align: center; margin-bottom: 20px;">Available Helpers</h1>
        <div class="helper-list">
            <?php if (!empty($helpers)): ?>
                <?php foreach ($helpers as $helper): ?>
                    <div class="helper-card">
                        <h3><?php echo htmlspecialchars($helper['name']); ?></h3>
                        <p>Skills: <?php echo htmlspecialchars($helper['skills']); ?></p>
                        <p>Rating: <?php echo number_format($helper['rating'], 2); ?></p>
                        <p>Location: <?php echo htmlspecialchars($helper['address']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($helper['phone_number']); ?></p>
                        <a href="hire_helper.php?helper_id=<?php echo $helper['helper_id']; ?>" class="hire-button">Hire</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No available helpers found.</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
