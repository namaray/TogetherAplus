<?php
session_start();
include 'dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Capture user inputs
$skills = isset($_GET['skills']) ? strtolower(trim($_GET['skills'])) : '';
$location = isset($_GET['location']) ? strtolower(trim($_GET['location'])) : '';
$rating = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;

// Current user ID
$user_id = $_SESSION['user_id'];

// Query to find new helpers who haven't worked for the current user
$sql = "
    SELECT 
        h.helper_id, 
        h.name, 
        h.skills, 
        h.rating, 
        h.address, 
        h.phone_number,
        COUNT(hr.hiring_id) AS tasks_completed,
        CASE 
            WHEN h.status = 'active' AND 
                 (SELECT COUNT(*) 
                  FROM hiring_records hr2 
                  WHERE hr2.helper_id = h.helper_id 
                  AND hr2.status = 'in_progress') = 0 
            THEN 'Available'
            ELSE 'Unavailable'
        END AS availability
    FROM 
        helpers h
    LEFT JOIN 
        hiring_records hr ON h.helper_id = hr.helper_id AND hr.status = 'completed'
    WHERE 
        h.helper_id NOT IN (
            SELECT hr.helper_id
            FROM hiring_records hr
            WHERE hr.user_id = ?
        )
        AND h.status = 'active'
";

// Add skill filter
if (!empty($skills)) {
    $skillsArray = explode(',', $skills);
    $skillsFilter = [];
    foreach ($skillsArray as $skill) {
        $skillsFilter[] = "FIND_IN_SET('$skill', LOWER(h.skills)) > 0";
    }
    $sql .= " AND (" . implode(' AND ', $skillsFilter) . ")";
}

// Add location filter
if (!empty($location)) {
    $sql .= " AND LOWER(h.address) LIKE '%$location%'";
}

// Add rating filter
if ($rating > 0) {
    $sql .= " AND h.rating >= $rating";
}

// Sort results
$sql .= "
    GROUP BY h.helper_id, h.name, h.skills, h.rating, h.address
    ORDER BY h.rating DESC, tasks_completed DESC;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$helpers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find New Helpers - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        main {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filter-form label {
            flex: 1 1 calc(25% - 10px);
        }
        .filter-form input, .filter-form button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #0056b3;
        }
        .helper-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .helper-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex: 1 1 calc(33% - 20px);
        }
        .helper-card h3 {
            margin: 0 0 10px;
        }
        .availability {
            color: green;
            font-weight: bold;
        }
        .not-available {
            color: red;
            font-weight: bold;
        }
        .hire-button {
            margin-top: 10px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
        }
        .hire-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <?php include 'header_user.php'; ?>
    <main>
        <form method="GET" class="filter-form">
            <label>Skills:
                <input type="text" name="skills" placeholder="e.g., Cooking">
            </label>
            <label>Location:
                <input type="text" name="location" placeholder="City or Address">
            </label>
            <label>Minimum Rating:
                <input type="number" name="rating" step="0.1" min="0" max="5">
            </label>
            <button type="submit">Find Helpers</button>
        </form>

        <div class="helper-list">
            <?php if (count($helpers) > 0): ?>
                <?php foreach ($helpers as $helper): ?>
                    <div class="helper-card">
                        <h3><?php echo htmlspecialchars($helper['name']); ?></h3>
                        <p>Skills: <?php echo htmlspecialchars($helper['skills']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($helper['phone_number']); ?></p>
                        <p>Rating: <?php echo number_format($helper['rating'], 2); ?></p>
                        <p>Location: <?php echo htmlspecialchars($helper['address']); ?></p>
                        <p>Tasks Completed: <?php echo $helper['tasks_completed']; ?></p>
                        <p class="<?php echo $helper['availability'] === 'Available' ? 'availability' : 'not-available'; ?>">
                            <?php echo $helper['availability']; ?>
                        </p>
                        <a href="hire_helper.php?helper_id=<?php echo $helper['helper_id']; ?>" class="hire-button">Hire</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No new helpers match your criteria.</p>
            <?php endif; ?>
        </div>
    </main>


</body>
</html>
