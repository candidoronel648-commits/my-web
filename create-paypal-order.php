<?php
// create-paypal-order.php
// NOTE: Run this on server over HTTPS. Use sandbox credentials for testing.

$input = json_decode(file_get_contents('php://input'), true);
$total = $input['total'] ?? 0;

$clientId = 'PAYPAL_CLIENT_ID';
$secret = 'PAYPAL_SECRET';
$base = 'https://api-m.sandbox.paypal.com';

// get access token
$ch = curl_init("$base/v1/oauth2/token");
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$tok = json_decode($res, true)['access_token'] ?? null;

$body = [
  'intent' => 'CAPTURE',
  'purchase_units' => [[
    'amount' => ['currency_code' => 'PHP', 'value' => number_format((float)$total, 2, '.', '')]
  ]]
];

$ch = curl_init("$base/v2/checkout/orders");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "Authorization: Bearer $tok"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
$res = curl_exec($ch);
curl_close($ch);
header('Content-Type: application/json');
echo $res;
