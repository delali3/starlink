<?php
/**
 * Test Frog SMS API directly
 * Run: php test-sms.php
 */

// Load environment variables
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['FROG_SMS_API_KEY'];
$username = $_ENV['FROG_SMS_USERNAME'];
$senderId = $_ENV['FROG_SMS_SENDER_ID'];
$testPhone = '0553602142'; // Change this to your test phone number

echo "Testing Frog SMS API...\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "Username: {$username}\n";
echo "Sender ID: {$senderId}\n";
echo "Test Phone: {$testPhone}\n";
echo "\n";

$postData = [
    'senderid' => $senderId,
    'destinations' => [[
        'destination' => $testPhone,
        'msgid' => 'TEST-' . time()
    ]],
    'message' => 'Test SMS from GhLinks. Your code is: 123456',
    'smstype' => 'text'
];

$ch = curl_init('https://frogapi.wigal.com.gh/api/v3/sms/send');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'API-KEY: ' . $apiKey,
    'USERNAME: ' . $username
));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

echo "Sending SMS request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(curl_errno($ch)) {
    echo "CURL Error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Status Code: {$httpCode}\n";
    echo "Response: {$response}\n";
    
    $responseData = json_decode($response, true);
    if (isset($responseData['status']) && $responseData['status'] === 'ACCEPTD') {
        echo "\n✓ SUCCESS! SMS was accepted for delivery.\n";
        echo "Check your phone ({$testPhone}) for the message.\n";
    } else {
        echo "\n✗ FAILED! SMS was not accepted.\n";
        echo "Check your API credentials and sender ID approval status.\n";
    }
}
curl_close($ch);
