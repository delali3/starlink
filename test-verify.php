<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$paymentService = app(\App\Services\Payment\PaymentService::class);

echo "Verifying payment: GHL-LDRCJ8KKYUL3-1771249247\n\n";

$result = $paymentService->verifyPayment('GHL-LDRCJ8KKYUL3-1771249247');

echo "Result:\n";
print_r($result);
