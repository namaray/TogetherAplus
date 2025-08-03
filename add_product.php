<?php
session_start();
include '../dbconnect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category = $conn->real_escape_string($_POST['category']);
    $vendor = $conn->real_escape_string($_POST['vendor']);
    
    $image_url = '';
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]); // Create a unique name
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "uploads/products/" . $image_name;
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }

    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0) {
        $error = "Please fill all fields correctly.";
    } elseif (!isset($error)) {
        $sql = "INSERT INTO products (name, description, price, stock_quantity, category, vendor, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisss", $name, $description, $price, $stock_quantity, $category, $vendor, $image_url);

        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            $error = "Database Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Marketplace Product</title>
    <!-- Bootstrap is used for basic alert styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* This style block is for the main content area, respecting the sidebar */
        .content-wrapper {
            margin-left: 250px; /* Width of your sidebar */
            padding: 30px;
            background-color: #f8f9fa; /* Light grey background for content area */
        }
        
        /* The main form container */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: bold;
            color: #343a40;
        }

        /* Flexbox row for side-by-side fields */
        .form-row {
            display: flex;
            gap: 20px; /* Space between columns */
            width: 100%;
        }

        /* General styling for each form group (label + input) */
        .form-group {
            margin-bottom: 20px;
            width: 100%;
        }

        .form-group.flex-item {
            flex: 1; /* Makes items in a flex row share space equally */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        /* Consistent styling for all input fields */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Specific height for textarea */
        textarea.form-control {
            height: 120px;
            resize: vertical;
        }

        /* Submit button styling */
        .btn-submit {
            display: block;
            width: 100%;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include "header_admin.php"; ?>

    <div class="content-wrapper">
        <div class="form-container">
            <h1>Add Marketplace Product</h1>

            <!-- Display Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" required></textarea>
                </div>

                <!-- Flexbox Row for Price and Stock -->
                <div class="form-row">
                    <div class="form-group flex-item">
                        <label for="price">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group flex-item">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                    </div>
                </div>

                <!-- Flexbox Row for Category and Vendor -->
                <div class="form-row">
                    <div class="form-group flex-item">
                        <label for="category">Category</label>
                        <input type="text" class="form-control" id="category" name="category" placeholder="e.g., Visual Aids">
                    </div>
                    <div class="form-group flex-item">
                        <label for="vendor">Vendor</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" placeholder="e.g., TechVision Inc.">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                </div>

                <button type="submit" class="btn-submit">Add Product</button>
            </form>
        </div>
    </div>
</body>
</html>