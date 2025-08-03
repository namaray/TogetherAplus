<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure a product ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // A more user-friendly error page would be better in a real application
    die("Product not found. <a href='marketplace.php'>Go back to marketplace</a>.");
}

$product_id = (int)$_GET['id'];

// Fetch the product details from the database
$sql = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// If no product is found, terminate the script
if (!$product) {
    die("Product not found. <a href='marketplace.php'>Go back to marketplace</a>.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom styles for the quantity input arrows */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
        <!-- Back to Marketplace Link -->
        <div class="mb-6">
            <a href="marketplace.php" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-gray-900 font-semibold text-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back to Marketplace
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Product Image Gallery -->
                <div class="p-4">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="w-full h-full max-h-[500px] object-contain rounded-xl">
                </div>

                <!-- Product Details -->
                <div class="p-6 md:p-8 flex flex-col">
                    <div class="flex-grow">
                        <!-- Vendor -->
                        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider"><?php echo htmlspecialchars($product['vendor']); ?></p>
                        
                        <!-- Product Name -->
                        <h1 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <!-- Price -->
                        <p class="text-4xl font-bold text-gray-800 mt-4">$<?php echo number_format($product['price'], 2); ?></p>
                        
                        <!-- Stock Status -->
                        <div class="mt-4">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="inline-flex items-center gap-2 text-green-700 bg-green-100 px-3 py-1 rounded-full text-sm font-semibold">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    In Stock
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-2 text-red-700 bg-red-100 px-3 py-1 rounded-full text-sm font-semibold">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="prose prose-sm text-gray-600 mt-6">
                            <h3 class="font-semibold text-gray-800">Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    </div>

                    <!-- Add to Cart Form -->
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <form action="cart_handler.php" method="POST">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                
                                <div class="flex flex-col sm:flex-row items-center gap-4">
                                    <!-- Quantity Selector -->
                                    <div class="flex items-center border border-gray-300 rounded-lg">
                                        <button type="button" onclick="this.nextElementSibling.stepDown()" class="quantity-btn p-3 text-gray-600 hover:bg-gray-100 rounded-l-lg">-</button>
                                        <input type="number" id="quantity" name="quantity" class="w-16 text-center border-x border-gray-300 font-semibold text-gray-800 focus:ring-0 focus:border-gray-300" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                        <button type="button" onclick="this.previousElementSibling.stepUp()" class="quantity-btn p-3 text-gray-600 hover:bg-gray-100 rounded-r-lg">+</button>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <button type="submit" class="w-full sm:w-auto flex-grow bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300">
                                        Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
