<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php'; // your PDO $pdo connection

use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\Checkout\Session;

// Load .env variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // won’t crash if .env is missing

// Get Stripe API key from .env
$apiKey = $_ENV['STRIPE_API_KEY'] ?? null;

if (!$apiKey) {
    die(json_encode(['error' => 'Stripe API key not set in .env']));
}

// Set Stripe API key
Stripe::setApiKey($apiKey);

// Get cart from session
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// Fetch item details from database
$ids = array_keys($cart);
$items = [];

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare Stripe line items
$line_items = [];

foreach ($items as $item) {
    $qty = (int) $cart[$item['id']];
    $unit_amount = (int) ($item['price'] * 100); // convert PHP pesos to cents

    $line_items[] = [
        'price_data' => [
            'currency' => 'php',
            'product_data' => [
                'name' => $item['name'],
            ],
            'unit_amount' => $unit_amount,
        ],
        'quantity' => $qty,
    ];
}

// Create Stripe Checkout Session
try {
    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'http://localhost/restaurant_management_system/order_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/restaurant_management_system/cart.php',
    ]);

    echo json_encode(['id' => $session->id]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
