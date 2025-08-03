<?php
session_start();
include 'dbconnect.php';

// --- Security and Logic Checks ---

// 1. Ensure user is logged in, the cart isn't empty, and this was a POST request.
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: marketplace.php');
    exit;
}

// --- Data Preparation ---

$user_id = $_SESSION['user_id'];
// Sanitize all POST data from the checkout form
$shipping_name = $conn->real_escape_string($_POST['shipping_name']);
$shipping_address = $conn->real_escape_string($_POST['shipping_address']);
$shipping_city = $conn->real_escape_string($_POST['shipping_city']);
$shipping_postal_code = $conn->real_escape_string($_POST['shipping_postal_code']);
$shipping_phone = $conn->real_escape_string($_POST['shipping_phone']);

// --- Server-Side Price Calculation (CRUCIAL FOR SECURITY) ---
// Never trust a price sent from the client side. Always recalculate on the server.
$total_price = 0;
$product_details = [];
$product_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

$sql = "SELECT product_id, price, stock_quantity FROM products WHERE product_id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('i', count($product_ids));
$stmt->bind_param($types, ...$product_ids);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $product_details[$row['product_id']] = $row;
}

// Calculate total price and check stock availability
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    // Check if product still exists and if there's enough stock
    if (!isset($product_details[$product_id]) || $product_details[$product_id]['stock_quantity'] < $quantity) {
        // Handle error: product out of stock or does not exist
        die("Error: Product with ID $product_id is out of stock or no longer available. Please <a href='view_cart.php'>return to your cart</a> to make adjustments.");
    }
    $total_price += $product_details[$product_id]['price'] * $quantity;
}

// --- Database Transaction ---
// Using a transaction ensures that ALL database operations succeed, or NONE of them do.
// This prevents partial orders (e.g., an order is created but the items are not).

$conn->begin_transaction();

try {
    // 1. Insert into the main `orders` table
    $sql_order = "INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address, shipping_city, shipping_postal_code, shipping_phone, order_status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')"; // 'paid' because this is a dummy success
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("idsssss", $user_id, $total_price, $shipping_name, $shipping_address, $shipping_city, $shipping_postal_code, $shipping_phone);
    $stmt_order->execute();
    
    // Get the ID of the order we just created
    $order_id = $conn->insert_id;

    // 2. Insert each product from the cart into the `order_items` table
    $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
    $stmt_items = $conn->prepare($sql_items);

    // 3. Prepare to update the stock quantity in the `products` table
    $sql_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
    $stmt_stock = $conn->prepare($sql_stock);

    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $price_at_purchase = $product_details[$product_id]['price'];
        
        // Insert into order_items
        $stmt_items->bind_param("iiid", $order_id, $product_id, $quantity, $price_at_purchase);
        $stmt_items->execute();

        // Decrease the stock
        $stmt_stock->bind_param("ii", $quantity, $product_id);
        $stmt_stock->execute();
    }
    
    // If all queries were successful, commit the transaction
    $conn->commit();

    // Clear the shopping cart from the session
    unset($_SESSION['cart']);

    // --- Display Success Message ---
    echo "
        <div style='text-align:center; padding: 40px; font-family: sans-serif; max-width: 600px; margin: 40px auto; border: 1px solid #ddd; border-radius: 10px;'>
            <h1>Thank You for Your Order!</h1>
            <p>Your payment was successful and your order has been placed.</p>
            <p><strong>Your Order ID is: #$order_id</strong></p>
            <p>We will ship your items to the following address:</p>
            <p><em>$shipping_name<br>$shipping_address<br>$shipping_city, $shipping_postal_code</em></p>
            <br>
            <a href='marketplace.php' style='display:inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Continue Shopping</a>
        </div>
    ";

} catch (Exception $e) {
    // If any query failed, roll back the transaction
    $conn->rollback();
    // Log the error and show a user-friendly message
    error_log("Order placement failed: " . $e->getMessage());
    die("An unexpected error occurred while placing your order. Please try again.");
}

$stmt_order->close();
$stmt_items->close();
$stmt_stock->close();
$conn->close();
?>
