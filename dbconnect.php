<?php
// Database connection settings
$host = 'localhost';  // Change to your database host if not local
$db_name = 'togetheraplus'; // Name of your database
$username = 'root';  // Database username
$password = '1234';  // Database password

try {
    // Enable strict mode and error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Create a new connection object
    $conn = new mysqli($host, $username, $password, $db_name);

    // Check the connection
    if ($conn->connect_errno) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Optional: Log successful connection (for debugging during development)
    // Uncomment the line below if needed
    // error_log("Database connection successful on " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    // Log the error to a file (useful in production)
    error_log("Database connection error: " . $e->getMessage(), 3, 'logs/db_errors.log');
    
    // Show a user-friendly error message (optional)
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}
?>
