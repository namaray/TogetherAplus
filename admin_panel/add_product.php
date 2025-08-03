 <?php
include 'admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-using the same robust logic from the previous version
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category = $conn->real_escape_string($_POST['category']);
    $vendor = $conn->real_escape_string($_POST['vendor']);
    
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "uploads/products/" . $image_name;
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }

    if (empty($name) || empty($description) || $price <= 0) {
        $error_message = "Please fill all fields correctly.";
    } elseif (!isset($error_message)) {
        $sql = "INSERT INTO products (name, description, price, stock_quantity, category, vendor, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisss", $name, $description, $price, $stock_quantity, $category, $vendor, $image_url);
        if ($stmt->execute()) {
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Database Error: " . $stmt->error;
        }
    }
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Product</h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stock_quantity" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" placeholder="e.g., Visual Aids">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="vendor" class="form-label">Vendor</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" placeholder="e.g., TechVision Inc.">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?>