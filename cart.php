<?php
session_start();
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/db.php';

use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\Checkout\Session;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$stripeKey = $_ENV['STRIPE_API_KEY'] ?? null;
if (!$stripeKey) die('Stripe key not set.');

// Clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

// Handle GCash / Bank transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'])) {
    $method = $_POST['method'];
    $total = $_POST['total'];
    $cart_snapshot = $_POST['cart_snapshot'] ?? json_encode($_SESSION['cart']);
    
    $receiptPath = '';
    if ($method === 'bank' && isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $receiptPath = 'uploads/' . basename($_FILES['receipt']['name']);
        move_uploaded_file($_FILES['receipt']['tmp_name'], $receiptPath);
    }
    
    $stmt = $pdo->prepare("INSERT INTO orders (cart_data,total,method,status,receipt) VALUES (?,?,?,?,?)");
    $stmt->execute([json_encode($cart_snapshot), $total, $method, 'pending', $receiptPath]);
    
    $_SESSION['cart'] = [];
    header("Location: order_success.php?method=$method");
    exit;
}

// Handle Stripe checkout
if (isset($_POST['pay_stripe'])) {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) die('Cart empty.');

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $line_items = [];
    foreach ($items as $item) {
        $qty = $cart[$item['id']];
        $line_items[] = [
            'price_data' => [
                'currency' => 'php',
                'product_data' => ['name' => $item['name']],
                'unit_amount' => intval($item['price']*100)
            ],
            'quantity' => $qty
        ];
    }

    Stripe::setApiKey($stripeKey);
    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'http://localhost/restaurant_management_system/order_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/restaurant_management_system/cart.php'
    ]);

    header("Location: ".$session->url);
    exit;
}

// Fetch cart items
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $it) {
        $qty = $cart[$it['id']];
        $total += $qty * $it['price'];
    }
}

// GCash deep-link
$gcashNumber = '09519445821';
$gcashLink = "gcash://pay?phone=$gcashNumber&amount=$total&note=Food%20Order";
$gcashQRText = "GCASH-PAY|$gcashNumber|$total|Food Order";

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Cart</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=PHP"></script>
<link rel="stylesheet" href="cart.css">
</head>
<body>
<h1>Your Cart</h1>

<?php if (!$items): ?>
<p>Cart is empty. <a href="index.php">Go to menu</a></p>
<?php else: ?>
<table border="1" cellpadding="6">
<tr><th>Item</th><th>Price</th><th>Qty</th><th>Line</th></tr>
<?php foreach ($items as $it): $qty=$cart[$it['id']]; ?>
<tr>
<td><?=htmlspecialchars($it['name'])?></td>
<td>₱<?=number_format($it['price'],2)?></td>
<td><?=$qty?></td>
<td>₱<?=number_format($qty*$it['price'],2)?></td>
</tr>
<?php endforeach; ?>
<tr><td colspan="3"><strong>Total</strong></td><td><strong>₱<?=number_format($total,2)?></strong></td></tr>
</table>

<p>
<a href="index.php">Continue shopping</a> |
<a href="cart.php?clear=1">Clear cart</a>
</p>

<h3>Checkout</h3>
<div class="payment-methods">
<p>Select payment method:</p>
<div class="pm-buttons">
<button class="pm-btn" data-method="gcash">GCash</button>
<button class="pm-btn" data-method="paypal">PayPal</button>
<button class="pm-btn" data-method="card">Credit / Debit Card</button>
<button class="pm-btn" data-method="manual">Bank Transfer</button>
</div>
</div>

<div id="pm-panels">
<!-- GCash Panel -->
<div class="pm-panel" id="panel-gcash">
<h4>Pay with GCash</h4>
<div style="display:flex; gap:16px; flex-wrap:wrap;">
<div id="gcash-qr"></div>
<div>
<p><strong>Number:</strong> <?=$gcashNumber?></p>
<p><strong>Amount:</strong> ₱<?=number_format($total,2)?></p>
<a href="<?=$gcashLink?>" class="action-btn">Open GCash & Pay</a>
<form method="post" style="margin-top:12px;">
<input type="hidden" name="method" value="gcash">
<input type="hidden" name="total" value="<?=$total?>">
<input type="hidden" name="cart_snapshot" value='<?=htmlspecialchars(json_encode($cart))?>'>
<button type="submit" class="action-btn secondary">I already paid</button>
</form>
</div>
</div>
</div>

<!-- PayPal Panel -->
<div class="pm-panel" id="panel-paypal">
<h4>Pay with PayPal (sandbox)</h4>
<div id="paypal-button-container"></div>
</div>

<!-- Stripe Panel -->
<div class="pm-panel" id="panel-card">
<h4>Pay by Card (Stripe)</h4>
<form method="post">
<input type="hidden" name="pay_stripe" value="1">
<button type="submit" class="action-btn">Pay by Card</button>
</form>
</div>

<!-- Bank Transfer Panel -->
<div class="pm-panel" id="panel-manual">
<h4>Bank Transfer</h4>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="method" value="bank">
<input type="hidden" name="total" value="<?=$total?>">
<input type="hidden" name="cart_snapshot" value='<?=htmlspecialchars(json_encode($cart))?>'>
<label>Upload receipt: <input type="file" name="receipt" required></label><br>
<button type="submit" class="action-btn">Submit Payment Proof</button>
</form>
</div>
</div>

<script>
const pmButtons = document.querySelectorAll('.pm-btn');
const panels = document.querySelectorAll('.pm-panel');
pmButtons.forEach(b => {
    b.addEventListener('click', ()=> {
        panels.forEach(p=>p.style.display='none');
        document.getElementById('panel-'+b.getAttribute('data-method')).style.display='block';
    });
});

// Generate GCash QR code
new QRCode(document.getElementById('gcash-qr'), {
    text: `GCASH|<?=$gcashNumber?>|AMOUNT:<?=$total?>|NOTE:Food Order`,
    width:160, height:160
});

// PayPal Buttons
if (window.paypal) {
paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units:[{amount:{value:'<?=$total?>'}}]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details){
            alert('Payment completed by '+details.payer.name.given_name);
            window.location='order_success.php?method=paypal';
        });
    }
}).render('#paypal-button-container');
}
</script>

<?php endif; ?>
</body>
</html>
