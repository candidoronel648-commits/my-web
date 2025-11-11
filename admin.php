<?php
session_start();
require 'db.php';

// Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order status
    if (isset($_POST['update_order'])) {
        $id = intval($_POST['order_id']);
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    // Add new menu item
    if (isset($_POST['add_item'])) {
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, price) VALUES (?, ?)");
        $stmt->execute([$name, $price]);
    }

    // Update existing menu item
    if (isset($_POST['update_item'])) {
        $id = intval($_POST['item_id']);
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);
    }

    // Delete menu item
    if (isset($_POST['delete_item'])) {
        $id = intval($_POST['item_id']);
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
    }

    header('Location: admin.php');
    exit;
}

// --- Fetch Orders ---
$stmt = $pdo->query("SELECT o.*, u.name AS customer 
  FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at ASC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Menu Items ---
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id ASC");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="admin.css">

</head>
<body>
<h1>Admin Dashboard</h1>

<h2>Orders</h2>
<table>
<tr>
<th>Order ID</th>
<th>Customer Name</th>
<th>Order Date</th>
<th>Total</th>
<th>Status</th>
<th>Send Receipt</th>
<th>Update Status</th>
<th>View</th>
</tr>
<?php foreach ($orders as $o): ?>
<tr>
<td><?php echo $o['id']; ?></td>
<td><?php echo htmlspecialchars($o['customer']); ?></td>
<td><?php echo $o['created_at']; ?></td>
<td>₱<?php echo number_format($o['total'], 2); ?></td>
<td><?php echo ucfirst($o['status']); ?></td>

<!-- Send Receipt -->
<td>
<form method="post" action="send_receipt.php" style="display:inline">
    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
    <button type="submit" name="send_receipt">Send Receipt</button>
</form>
</td>

<!-- Update Status -->
<td>
<form method="post" style="display:inline">
    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
    <select name="status">
        <option value="pending" <?php if($o['status']=='pending') echo 'selected'; ?>>Pending</option>
        <option value="preparing" <?php if($o['status']=='preparing') echo 'selected'; ?>>Preparing</option>
        <option value="delivered" <?php if($o['status']=='delivered') echo 'selected'; ?>>Delivered</option>
        <option value="cancelled" <?php if($o['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
    </select>
    <button type="submit" name="update_order">Update</button>
</form>
</td>

<!-- View Order -->
<td>
<a href="order_success.php?order=<?php echo $o['id']; ?>">View</a>
</td>
</tr>
<?php endforeach; ?>
</table>


<h2>Menu Management</h2>

<!-- Add new item -->
<h3>Add New Item</h3>
<form method="post">
<label>Name: <input name="name" required></label>
<label>Price: <input name="price" type="number" step="0.01" required></label>
<button name="add_item" type="submit">Add Item</button>
</form>

<!-- Edit / Delete existing items -->
<h3>Existing Items</h3>
<table>
<tr><th>ID</th><th>Name</th><th>Price</th><th>Action</th></tr>
<?php foreach ($menu_items as $item): ?>
<tr>
<td><?php echo $item['id']; ?></td>
<form method="post">
<td><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>"></td>
<td><input type="number" name="price" step="0.01" value="<?php echo $item['price']; ?>"></td>
<td>
<input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
<button type="submit" name="update_item">Update</button>
<button type="submit" name="delete_item" onclick="return confirm('Are you sure?')">Delete</button>
</td>
</form>
</tr>
<?php endforeach; ?>
</table>

<p><a href="logout.php">Logout</a></p>
</body>
</html>
