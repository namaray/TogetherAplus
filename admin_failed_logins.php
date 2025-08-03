<?php
// Start the session (if not already started)
session_start();

// Check if the admin is logged in (basic role check, expand as needed)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can view this page.");
}

// Include the database connection
include 'dbconnect.php';

try {
    // Query to fetch failed login attempts within the last hour
    $query = "
        SELECT user_id, COUNT(*) AS failed_attempts 
        FROM login_logs 
        WHERE status = 'failed' AND login_time > (NOW() - INTERVAL 1 HOUR)
        GROUP BY user_id
    ";

    // Execute the query
    $result = $conn->query($query);

    // Check if there are results
    if ($result->num_rows > 0) {
        echo "<h1>Failed Login Attempts (Last Hour)</h1>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<thead>
                <tr>
                    <th>User ID</th>
                    <th>Failed Attempts</th>
                </tr>
              </thead>";
        echo "<tbody>";

        // Fetch and display results
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['user_id']}</td>
                    <td>{$row['failed_attempts']}</td>
                  </tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<h1>No failed login attempts in the last hour.</h1>";
    }
} catch (Exception $e) {
    // Log and handle errors
    error_log("Error fetching failed login attempts: " . $e->getMessage(), 3, 'logs/admin_errors.log');
    echo "An error occurred. Please try again later.";
}

// Close the database connection
$conn->close();
?>
