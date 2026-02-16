<?php

namespace App\Services\Payment;

interface PaymentProviderInterface
{
    /**
     * Initialize payment transaction.
     *
     * @param array $data
     * @return array
     */
    public function initializePayment(array $data): array;

    /**
     * Verify payment transaction.
     *
     * @param string $reference
     * @return array
     */
    public function verifyPayment(string $reference): array;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string;
}
