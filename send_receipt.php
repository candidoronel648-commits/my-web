<?php
session_start();
require 'db.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Handle generating receipt ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_receipt'])) {
    $orderId = intval($_POST['order_id']);

    // Fetch order info
    $stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Order not found.");
    }

    // Decode cart data
    $cart = json_decode($order['cart_data'], true);
    if (!is_array($cart)) {
        die("Invalid cart data.");
    }

    // Generate receipt HTML
    $receiptHTML = "<h2>Order Receipt #{$order['id']}</h2>";
    $receiptHTML .= "<p><strong>Customer:</strong> {$order['customer_name']}</p>";
    $receiptHTML .= "<p><strong>Date:</strong> {$order['created_at']}</p>";
    $receiptHTML .= "<p><strong>Payment Method:</strong> {$order['method']}</p>";
    $receiptHTML .= "<p><strong>Status:</strong> {$order['status']}</p>";
    $receiptHTML .= "<table border='1' cellpadding='6' cellspacing='0'>";
    $receiptHTML .= "<tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>";

    $total = 0;
    foreach ($cart as $item) {
        $name = htmlspecialchars($item['name']);
        $qty = intval($item['quantity']);
        $price = floatval($item['price']);
        $lineTotal = $qty * $price;
        $total += $lineTotal;
        $receiptHTML .= "<tr>
            <td>{$name}</td>
            <td>{$qty}</td>
            <td>₱" . number_format($price, 2) . "</td>
            <td>₱" . number_format($lineTotal, 2) . "</td>
        </tr>";
    }
    $receiptHTML .= "<tr><td colspan='3'><strong>Total</strong></td><td>₱" . number_format($total, 2) . "</td></tr>";
    $receiptHTML .= "</table>";

    // Force download as HTML file
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=receipt_order_{$order['id']}.html");
    echo $receiptHTML;
    exit;
}

// --- Fetch all orders for admin display ---
$stmt = $pdo->query("SELECT o.*, u.name AS customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Generate Receipt</title>
<style>
table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
table, th, td { border: 1px solid #ccc; }
th, td { padding: 8px; text-align: left; }
button { padding: 5px 10px; margin-top: 5px; }
</style>
</head>
<body>
<h1>Generate Receipt</h1>

<h2>Orders</h2>
<table>
<tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr>
<?php foreach ($orders as $o): ?>
<tr>
<td><?php echo $o['id']; ?></td>
<td><?php echo htmlspecialchars($o['customer_name']); ?></td>
<td>₱<?php echo number_format($o['total'], 2); ?></td>
<td><?php echo $o['status']; ?></td>
<td>
<form method="post" style="display:inline">
<input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
<button type="submit" name="generate_receipt">Download Receipt</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>

<p><a href="admin.php">Back to Admin Dashboard</a></p>
</body>
</html>
