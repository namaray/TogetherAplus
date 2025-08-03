<?php
session_start();
include 'dbconnect.php';

// --- Security and Logic Checks ---

// 1. Redirect user to login page if they are not logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. If the cart is empty, there's nothing to check out. Redirect to the cart page.
if (empty($_SESSION['cart'])) {
    header('Location: view_cart.php');
    exit;
}

// --- Data Fetching ---

$user_id = $_SESSION['user_id'];

// 3. Fetch user's details for pre-filling the shipping form.
$sql_user = "SELECT name, address, phone_number FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

$default_name = $user['name'] ?? '';
$default_address = $user['address'] ?? '';
$default_phone = $user['phone_number'] ?? '';

// 4. Fetch cart items to display in the order summary.
$cart_items = [];
$total_price = 0;
$product_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

$sql_cart = "SELECT product_id, name, price, image_url FROM products WHERE product_id IN ($placeholders)";
$stmt_cart = $conn->prepare($sql_cart);
$types = str_repeat('i', count($product_ids));
$stmt_cart->bind_param($types, ...$product_ids);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

while ($row = $result_cart->fetch_assoc()) {
    $quantity = $_SESSION['cart'][$row['product_id']];
    $row['quantity'] = $quantity;
    $row['subtotal'] = $row['price'] * $quantity;
    $cart_items[] = $row;
    $total_price += $row['subtotal'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">

        <!-- Navigation Links -->
        <div class="flex justify-between items-center mb-6">
            <a href="view_cart.php" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-gray-900 font-semibold text-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Back to Cart
            </a>
            <a href="userdashboard.php" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-gray-900 font-semibold text-sm transition-colors">
                Back to Dashboard
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L9 4.414V17a1 1 0 102 0V4.414l5.293 5.293a1 1 0 001.414-1.414l-7-7z" /></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            <!-- Shipping Form -->
            <div class="lg:col-span-3">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Shipping Information</h1>
                <form action="dummy_payment.php" method="POST" class="space-y-6">
                    <div>
                        <label for="shipping_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <div class="mt-1">
                            <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($default_name); ?>" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-3">
                        </div>
                    </div>
                    <div>
                        <label for="shipping_address" class="block text-sm font-medium text-gray-700">Full Shipping Address</label>
                        <div class="mt-1">
                            <textarea id="shipping_address" name="shipping_address" rows="4" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-3"><?php echo htmlspecialchars($default_address); ?></textarea>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium text-gray-700">City</label>
                            <div class="mt-1">
                                <input type="text" id="shipping_city" name="shipping_city" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-3">
                            </div>
                        </div>
                        <div>
                            <label for="shipping_postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                            <div class="mt-1">
                                <input type="text" id="shipping_postal_code" name="shipping_postal_code" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-3">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="shipping_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <div class="mt-1">
                            <input type="tel" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($default_phone); ?>" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-3">
                        </div>
                    </div>
                    <div class="pt-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Finalize Order & Proceed to Payment
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-8">
                    <h2 class="text-lg font-medium text-gray-900">Order Summary</h2>
                    <ul role="list" class="mt-6 divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                            <li class="flex py-4">
                                <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover object-center">
                                </div>
                                <div class="ml-4 flex flex-1 flex-col">
                                    <div>
                                        <div class="flex justify-between text-sm font-medium text-gray-900">
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <p class="ml-4">$<?php echo number_format($item['subtotal'], 2); ?></p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex justify-between text-base font-medium text-gray-900">
                            <p>Grand Total</p>
                            <p>$<?php echo number_format($total_price, 2); ?></p>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">Shipping and taxes will be calculated on the payment page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
