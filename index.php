<?php
// index.php
session_start();
require 'db.php';

// simple cart in session
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id = intval($_POST['id']);
    $q = intval($_POST['qty']) ?: 1;
    if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
    $_SESSION['cart'][$id] += $q;
    header('Location: index.php');
    exit;
}

// fetch menu items
$stmt = $pdo->query("SELECT * FROM menu_items WHERE available = 1");
$items = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Menu</title>
<link rel="stylesheet" href="index.css">
</head>
<body>
<h1>Menu</h1>
<a href="cart.php">View Cart (<?php echo array_sum($_SESSION['cart']); ?>)</a>
<a href="admin.php">Admin log in</a>
<ul>
<?php foreach($items as $it): ?>
  <li>
    <strong><?php echo htmlspecialchars($it['name']); ?></strong>
    - ₱<?php echo number_format($it['price'],2); ?><br>
    <?php echo nl2br(htmlspecialchars($it['description'])); ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
      <input type="number" name="qty" value="1" min="1" style="width:60px">
      <button name="add" type="submit">Add to cart</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
</body>
</html>