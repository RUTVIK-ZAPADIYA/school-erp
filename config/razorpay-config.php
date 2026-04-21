<?php
/**
 * Razorpay configuration.
 *
 * Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in your environment for production.
 */
require_once __DIR__ . '/../includes/env_loader.php';
school_erp_load_env(__DIR__ . '/../.env');

return [
    'key_id' => getenv('RAZORPAY_KEY_ID') ?: '',
    'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '',
    'currency' => 'INR',
    'company_name' => 'School ERP',
    'payment_description_prefix' => 'School Fee Payment',
    'upi_flow' => strtolower(trim((string) (getenv('RAZORPAY_UPI_FLOW') ?: 'collect'))),
    'prefill_contact' => trim((string) (getenv('RAZORPAY_PREFILL_CONTACT') ?: '')),
    'prefill_email' => trim((string) (getenv('RAZORPAY_PREFILL_EMAIL') ?: '')),
];
