<?php
session_start();
require 'db.php';

$cart = $_SESSION['cart'] ?? [];

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

// fetch items in cart
$ids = array_keys($cart);
$items = [];
$total = 0.0;
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();
    foreach ($items as $it) {
        $qty = $cart[$it['id']];
        $total += $qty * $it['price'];
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Cart & Checkout</title>
<link rel="stylesheet" href="cart.css">
<script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<h1>Your Cart</h1>

<?php if (!$items): ?>
  <p>Cart is empty. <a href="index.php">Go to menu</a></p>
<?php else: ?>
  <table border="1" cellpadding="6">
    <tr><th>Item</th><th>Price</th><th>Qty</th><th>Line</th></tr>
    <?php foreach ($items as $it): $qty = $cart[$it['id']]; ?>
      <tr>
        <td><?php echo htmlspecialchars($it['name']); ?></td>
        <td>₱<?php echo number_format($it['price'],2); ?></td>
        <td><?php echo $qty; ?></td>
        <td>₱<?php echo number_format($qty*$it['price'],2); ?></td>
      </tr>
    <?php endforeach; ?>
    <tr><td colspan="3"><strong>Total</strong></td><td><strong>₱<?php echo number_format($total,2); ?></strong></td></tr>
  </table>

  <p>
    <a href="index.php">Continue shopping</a> |
    <a href="cart.php?clear=1">Clear cart</a>
  </p>

  <h3>Checkout</h3>
  <form id="checkoutForm" method="post">
    <label>Your Name: <input name="name" required></label><br>
    <label>Email: <input name="email" type="email" required></label><br>
    <label>Payment Method:
      <select id="paymentMethod" name="method">
        <option value="gcash">GCash</option>
        <option value="paypal">PayPal</option>
        <option value="card">Credit/Debit Card</option>
      </select>
    </label><br>
    <input type="hidden" name="total" value="<?php echo $total; ?>">
    <button type="submit">Pay Now</button>
  </form>

  <script>
  const form = document.getElementById('checkoutForm');
  form.addEventListener('submit', function(e){
      e.preventDefault();
      const method = document.getElementById('paymentMethod').value;
      const formData = new FormData(form);
      
      if(method === 'gcash'){
          // Redirect to a simulated GCash payment page or QR
          alert('Redirecting to GCash app or QR (simulated)');
          window.location.href = 'gcash_payment.php?name=' + encodeURIComponent(formData.get('name')) + '&total=' + encodeURIComponent(formData.get('total'));
      } else if(method === 'paypal'){
          // Redirect to PayPal checkout page
          alert('Redirecting to PayPal (simulated)');
          window.location.href = 'paypal_payment.php?name=' + encodeURIComponent(formData.get('name')) + '&total=' + encodeURIComponent(formData.get('total'));
      } else if(method === 'card'){
          // Stripe payment
          fetch('create_card_session.php', {
              method: 'POST',
              body: formData
          }).then(res => res.json())
          .then(data => {
              if(data.id){
                  var stripe = Stripe('pk_test_your_publishable_key'); // Replace with your key
                  stripe.redirectToCheckout({ sessionId: data.id });
              } else {
                  alert(data.error);
              }
          });
      }
  });
 

fetch('create_card_session.php')
.then(res => res.json())
.then(data => {
    if(data.id){
        var stripe = Stripe('pk_test_your_publishable_key'); // Replace with your publishable key
        stripe.redirectToCheckout({ sessionId: data.id });
    } else {
        alert(data.error);
    }
});

  </script>

<?php endif; ?>
</body>
</html>
