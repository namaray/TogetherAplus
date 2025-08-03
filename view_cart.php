<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    // Get product IDs from the session cart to fetch details from the database
    $product_ids = array_keys($_SESSION['cart']);
    // Create placeholders for the SQL query (e.g., ?,?,?)
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $sql = "SELECT product_id, name, price, image_url, stock_quantity FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    // Dynamically bind product IDs to the prepared statement
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build an array of cart items with all necessary details
    while ($row = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$row['product_id']];
        $row['quantity'] = $quantity;
        $row['subtotal'] = $row['price'] * $quantity;
        $cart_items[] = $row;
        $total_price += $row['subtotal'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
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
        <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Your Shopping Cart</h1>

        <?php if (!empty($cart_items)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <!-- Cart Items List -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
                    <ul role="list" class="divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                            <li class="flex py-6 flex-col sm:flex-row">
                                <div class="flex-shrink-0 w-24 h-24 sm:w-32 sm:h-32 border border-gray-200 rounded-md overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-center object-cover">
                                </div>

                                <div class="ml-0 sm:ml-6 mt-4 sm:mt-0 flex-1 flex flex-col">
                                    <div>
                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                            <h3><a href="product_detail.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
                                            <p class="ml-4">$<?php echo number_format($item['subtotal'], 2); ?></p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">$<?php echo number_format($item['price'], 2); ?> each</p>
                                    </div>
                                    <div class="flex-1 flex items-end justify-between text-sm mt-4">
                                        <!-- Quantity Updater -->
                                        <form action="cart_handler.php" method="POST" class="flex items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <label for="quantity-<?php echo $item['product_id']; ?>" class="mr-2 font-medium text-gray-700">Qty:</label>
                                            <input type="number" id="quantity-<?php echo $item['product_id']; ?>" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="w-20 rounded-md border border-gray-300 text-center" onchange="this.form.submit()">
                                        </form>

                                        <!-- Remove Button -->
                                        <div class="flex">
                                            <form action="cart_handler.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <button type="submit" class="font-medium text-indigo-600 hover:text-indigo-500">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900">Order summary</h2>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between text-base font-medium text-gray-900">
                            <p>Grand Total</p>
                            <p>$<?php echo number_format($total_price, 2); ?></p>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">Shipping and taxes calculated at checkout.</p>
                        <div class="mt-6">
                            <a href="checkout.php" class="w-full flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-700">Proceed to Checkout</a>
                        </div>
                        <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                            <p>
                                or <a href="marketplace.php" class="font-medium text-indigo-600 hover:text-indigo-500">Continue Shopping<span aria-hidden="true"> &rarr;</span></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart -->
            <div class="text-center bg-white rounded-2xl shadow-lg p-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c.51 0 .962-.343 1.087-.835l1.838-5.514A1.875 1.875 0 0018.614 6H6.386a1.875 1.875 0 00-1.789 1.437L3.19 12M16.5 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Your cart is empty</h3>
                <p class="mt-1 text-sm text-gray-500">Looks like you haven't added anything to your cart yet.</p>
                <div class="mt-6">
                  <a href="marketplace.php" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Start Shopping
                  </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
