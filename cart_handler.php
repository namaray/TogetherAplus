Generated php
<?php
session_start();

// Initialize cart if it doesn't exist in the session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ensure an action is specified
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    switch ($action) {
        // Action to add an item to the cart
        case 'add':
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            if ($product_id > 0 && $quantity > 0) {
                // If item already exists, increase quantity. Otherwise, add it.
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
            }
            // Redirect to the cart page to show the result
            header('Location: view_cart.php');
            break;

        // Action to update the quantity of an item
        case 'update':
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    // If quantity is 0 or less, remove the item
                    unset($_SESSION['cart'][$product_id]);
                }
            }
            header('Location: view_cart.php');
            break;

        // Action to completely remove an item from the cart
        case 'remove':
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
            header('Location: view_cart.php');
            break;
    }
} else {
    // If accessed without a POST action, just go to the cart page
    header('Location: view_cart.php');
}
exit;
?>
