<?php
session_start();
include 'dbconnect.php'; // Ensure database connection

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    die("You need to log in to upload resources.");
}

// Ensure the user is an admin
$admin_id = $_SESSION['admin_id'];
$is_admin_query = "SELECT role FROM admins WHERE admin_id = $admin_id";
$result = $conn->query($is_admin_query);

if ($result->num_rows == 0 || $result->fetch_assoc()['role'] !== 'super_admin') {
    die("Access denied. Only admins can upload resources.");
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $file_path = '';

    // Handle file upload
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/resources/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        $file_name = basename($_FILES['resource_file']['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name; // Generate unique file name

        if (!move_uploaded_file($_FILES['resource_file']['tmp_name'], $file_path)) {
            $error = "Failed to upload file.";
        }
    } else {
        $error = "No file uploaded or there was an error.";
    }

    // If no errors, insert the resource into the database
    if (!isset($error)) {
        $sql = "INSERT INTO resources (name, category, description, link, uploaded_by) 
                VALUES ('$name', '$category', '$description', '$file_path', '$admin_id')";

        if ($conn->query($sql) === TRUE) {
            $success = "Resource uploaded successfully!";
        } else {
            $error = "Error uploading resource: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resource</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Upload Resource</h1>

        <!-- Show success or error message -->
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Resource Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="audio">Audio</option>
                    <option value="video">Video</option>
                    <option value="tutorial">Tutorial</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="resource_file">Upload File:</label>
                <input type="file" id="resource_file" name="resource_file" required>
            </div>
            <div class="form-group">
                <button type="submit">Upload Resource</button>
            </div>
        </form>
    </div>

</body>
</html>
