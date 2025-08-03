<?php
// The header includes our session and database connection.
include 'admin_header.php';

// --- Page-Specific Logic for Managing Products ---

// Handle the POST request to delete a product.
if (isset($_POST['delete_product'])) {
    $product_id_to_delete = (int)$_POST['product_id'];
    
    // Prepare the DELETE statement to prevent SQL injection.
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $delete_stmt->bind_param("i", $product_id_to_delete);
    
    // Execute the deletion and set an appropriate message.
    if ($delete_stmt->execute()) {
        $success_message = "Product with ID #$product_id_to_delete has been deleted successfully.";
    } else {
        // Provide a more relevant error message.
        $error_message = "Failed to delete product. It might be linked to existing orders, which prevents deletion.";
    }
}

// --- Data Fetching and Search Logic ---

// Get the search term from the URL, or default to empty.
$search_term = $_GET['search'] ?? '';

// Prepare the main query to fetch products. The LIKE operator allows for partial matches.
$query = "SELECT product_id, name, price, stock_quantity, category, vendor 
          FROM products 
          WHERE name LIKE ? OR category LIKE ? 
          ORDER BY created_at DESC";
          
$stmt = $conn->prepare($query);
// Add wildcard '%' to the search term for the LIKE clause.
$search_like = "%" . $search_term . "%";
$stmt->bind_param("ss", $search_like, $search_like);
$stmt->execute();
$products_result = $stmt->get_result();
?>

<!-- The main container for our page content -->
<div class="container-fluid">
    <!-- Page Title and "Add New" Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Products</h1>
        <a href="add_product.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm me-2"></i>Add New Product
        </a>
    </div>

    <!-- Display Success or Error Messages -->
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Main Content Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Product Inventory</h6>
            <!-- Search Form -->
            <form method="get" class="d-none d-sm-inline-block form-inline">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <!-- This div makes the table scroll horizontally on small screens -->
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Vendor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td><?php echo htmlspecialchars($product['vendor']); ?></td>
                            <td>
                                <!-- The form for deleting a specific product -->
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// The footer closes all the HTML tags and includes necessary scripts.
include 'admin_footer.php'; 
?>