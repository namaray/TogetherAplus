<?php
include 'dbconnect.php'; // Include the database connection

try {
    $sql = "DELETE FROM password_resets WHERE created_at < NOW() - INTERVAL 1 HOUR";
    if ($conn->query($sql) === TRUE) {
        echo "Expired tokens cleaned up successfully.";
    } else {
        echo "Error cleaning expired tokens: " . $conn->error;
    }
} catch (Exception $e) {
    error_log("Cleanup error: " . $e->getMessage(), 3, 'logs/cleanup_errors.log');
}

$conn->close();
?>
