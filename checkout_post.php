<?php
session_start();
require 'db.php'; // your PDO connection

$name = $_POST['name'] ?? 'Guest';
$email = $_POST['email'] ?? '';
$method = $_POST['method'] ?? 'manual';
$total = $_POST['total'] ?? 0;
$cart_snapshot = $_POST['cart_snapshot'] ?? '';
$receiptPath = null;

// handle optional file upload (for manual/gcash)
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['receipt']['tmp_name'];
    $fn = basename($_FILES['receipt']['name']);
    $dest = __DIR__ . '/uploads/receipts/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $fn);
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    move_uploaded_file($tmp, $dest);
    $receiptPath = $dest;
}

// Optional: if you have a logged-in user
$user_id = $_SESSION['user_id'] ?? null;

// default order status
$status = 'pending';

$stmt = $pdo->prepare("
    INSERT INTO orders (user_id, customer_name, email, method, total, cart_json, receipt_path, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([$user_id, $name, $email, $method, $total, $cart_snapshot, $receiptPath, $status]);

// clear cart
$_SESSION['cart'] = [];

// redirect to success page
header('Location: order_success.php?order=' . $pdo->lastInsertId());
exit;
