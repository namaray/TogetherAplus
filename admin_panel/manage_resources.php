<?php
// The header includes our session, database connection, and basic page structure.
include 'admin_header.php';

// This logic only runs when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Data Collection and Sanitization ---
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category']);
    $admin_id = $_SESSION['admin_id']; // The ID of the admin uploading the resource.
    
    $error_message = '';
    $success_message = '';
    $file_path_for_db = '';

    // --- Secure File Upload Handling ---
    // Check if a file was uploaded and if there were no errors.
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        
        // Define the target directory. The '../' goes up one level from 'admin_panel'.
        $upload_dir = "../uploads/resources/";
        
        // Create the directory if it doesn't exist.
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Create a unique filename to prevent overwriting existing files and for security.
        $file_name = uniqid() . '-' . basename($_FILES['resource_file']['name']);
        $target_file_path = $upload_dir . $file_name;
        
        // The path to store in the database should be relative to the root directory.
        $file_path_for_db = "uploads/resources/" . $file_name;

        // Move the uploaded file from its temporary location to our target directory.
        if (!move_uploaded_file($_FILES['resource_file']['tmp_name'], $target_file_path)) {
            $error_message = "Sorry, there was an error uploading your file.";
        }
        
    } else {
        $error_message = "No file was uploaded or an error occurred during upload.";
    }

    // --- Database Insertion ---
    // Only proceed if there were no upload errors.
    if (empty($error_message)) {
        // Use a prepared statement to securely insert the data.
        $sql = "INSERT INTO resources (name, category, description, link, uploaded_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // 'ssssi' corresponds to the data types: String, String, String, String, Integer
        $stmt->bind_param("ssssi", $name, $category, $description, $file_path_for_db, $admin_id);
        
        if ($stmt->execute()) {
            $success_message = "Resource uploaded and added to the repository successfully!";
        } else {
            $error_message = "Database error: Failed to save the resource information.";
        }
    }
}
?>

<!-- The main container for our page content -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Resource</h1>

    <!-- Main Content Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload Resource File</h6>
        </div>
        <div class="card-body">
            <!-- Display success or error messages from the PHP logic above -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- The form MUST have enctype="multipart/form-data" for file uploads to work. -->
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Resource Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g., Guide to Screen Readers" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Briefly describe what this resource is." required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" disabled selected>-- Select a Category --</option>
                            <option value="video">Video Tutorial</option>
                            <option value="audio">Audio Guide</option>
                            <option value="document">Document / PDF</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="resource_file" class="form-label">Resource File</label>
                        <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Upload and Add Resource</button>
            </form>
        </div>
    </div>
</div>

<?php
// The footer closes all the HTML tags and includes necessary scripts.
include 'admin_footer.php'; 
?>