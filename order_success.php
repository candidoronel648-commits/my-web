<?php
// order_success.php
session_start();
require 'db.php';

// Get order ID from URL
$order_id = $_GET['order'] ?? 0;
$order_id = (int)$order_id;

if (!$order_id) {
    echo "Invalid order.";
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit;
}

// Decode cart JSON stored in orders
$cart_items_ids = json_decode($order['cart_json'], true);
if (!is_array($cart_items_ids)) {
    $cart_items_ids = [];
}

// Fetch full menu item details
$items = [];
$total = 0.0;
if ($cart_items_ids) {
    $ids = array_keys($cart_items_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($menu_items as $m) {
        $qty = $cart_items_ids[$m['id']] ?? 0;
        $line = $m['price'] * $qty;
        $total += $line;
        $items[] = [
            'name' => $m['name'],
            'price' => $m['price'],
            'qty' => $qty,
            'line' => $line
        ];
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order Success</title>
<link rel="stylesheet" href="cart.css"> <!-- reuse cart styling -->
</head>
<body>
<div class="login-container" style="text-align:left;">
    <h1>Order Placed Successfully!</h1>
    <p>Thank you, <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>!</p>
    <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
    <p>Payment Method: <?php echo htmlspecialchars($order['method']); ?></p>
    <p>Order Status: <?php echo htmlspecialchars($order['status']); ?></p>

    <h3>Order Details</h3>
    <?php if ($items): ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Item</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Line</th>
        </tr>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td>₱<?php echo number_format($item['price'],2); ?></td>
            <td><?php echo $item['qty']; ?></td>
            <td>₱<?php echo number_format($item['line'],2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3"><strong>Total</strong></td>
            <td><strong>₱<?php echo number_format($total,2); ?></strong></td>
        </tr>
    </table>
    <?php else: ?>
    <p>No items found in this order.</p>
    <?php endif; ?>

    <?php if (!empty($order['receipt_path'])): ?>
        <p>Receipt Uploaded: <a href="<?php echo htmlspecialchars($order['receipt_path']); ?>" target="_blank">View</a></p>
    <?php endif; ?>

    <p><a href="index.php">Back to Menu</a></p>
</div>
</body>
</html>
