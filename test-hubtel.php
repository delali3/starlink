<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing Hubtel Payment API...\n\n";

$clientId = $_ENV['HUBTEL_CLIENT_ID'];
$clientSecret = $_ENV['HUBTEL_CLIENT_SECRET'];
$merchantAccountNumber = $_ENV['HUBTEL_MERCHANT_ACCOUNT_NUMBER'];
$apiUrl = $_ENV['HUBTEL_API_URL'];

echo "Client ID: {$clientId}\n";
echo "Merchant Account: {$merchantAccountNumber}\n";
echo "API URL: {$apiUrl}\n";
echo "Auth Bearer: " . substr($clientSecret, 0, 20) . "...\n";
echo "Auth Basic: " . base64_encode($clientId . ':' . $clientSecret) . "\n\n";

$payload = [
    'totalAmount' => 3.00,
    'description' => 'Test Subscription Payment',
    'callbackUrl' => 'https://webhook.site/test',
    'returnUrl' => 'http://localhost:8000/payments/verify',
    'merchantAccountNumber' => $merchantAccountNumber,
    'cancellationUrl' => 'http://localhost:8000/payments/cancelled',
    'clientReference' => 'TEST-' . time(),
];

echo "Request Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Test 1: Bearer Token
echo "=== TEST 1: Bearer Token ===\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $clientSecret,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: {$error}\n";
} else {
    echo "HTTP Status Code: {$httpCode}\n";
    echo "Response: " . ($response ?: '(empty)') . "\n\n";
}
curl_close($ch);

// Test 2: Basic Auth
echo "=== TEST 2: Basic Auth (clientId:clientSecret) ===\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: {$error}\n";
} else {
    echo "HTTP Status Code: {$httpCode}\n";
    echo "Response:\n";
    echo $response . "\n\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "Parsed Response:\n";
        print_r($data);
    }
}

curl_close($ch);
