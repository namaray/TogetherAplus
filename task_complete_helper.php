<?php
include('db.php');
// Get tasks completed by helpers
$query = "SELECT h.helper_id, h.name, COUNT(t.task_id) AS tasks_completed 
          FROM helpers h 
          JOIN hiring_records hr ON h.helper_id = hr.helper_id 
          JOIN tasks t ON hr.task_id = t.task_id 
          WHERE t.status = 'completed' 
          GROUP BY h.helper_id, h.name";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "Helper: " . $row['name'] . ", Tasks Completed: " . $row['tasks_completed'] . "<br>";
}
?>
