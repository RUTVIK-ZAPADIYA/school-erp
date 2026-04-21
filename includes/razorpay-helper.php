<?php

if (!function_exists('razorpay_load_config')) {
    function razorpay_load_config()
    {
        static $config = null;

        if (is_array($config)) {
            return $config;
        }

        $configPath = __DIR__ . '/../config/razorpay-config.php';
        $loadedConfig = file_exists($configPath) ? require $configPath : [];

        $config = [
            'key_id' => trim((string) ($loadedConfig['key_id'] ?? '')),
            'key_secret' => trim((string) ($loadedConfig['key_secret'] ?? '')),
            'currency' => trim((string) ($loadedConfig['currency'] ?? 'INR')),
            'company_name' => trim((string) ($loadedConfig['company_name'] ?? 'School ERP')),
            'payment_description_prefix' => trim((string) ($loadedConfig['payment_description_prefix'] ?? 'School Fee Payment')),
        ];

        if ($config['currency'] === '') {
            $config['currency'] = 'INR';
        }

        if ($config['company_name'] === '') {
            $config['company_name'] = 'School ERP';
        }

        if ($config['payment_description_prefix'] === '') {
            $config['payment_description_prefix'] = 'School Fee Payment';
        }

        return $config;
    }
}

if (!function_exists('razorpay_is_configured')) {
    function razorpay_is_configured()
    {
        $config = razorpay_load_config();

        return $config['key_id'] !== '' && $config['key_secret'] !== '';
    }
}

if (!function_exists('razorpay_api_request')) {
    function razorpay_api_request($method, $path, $payload = null)
    {
        $config = razorpay_load_config();

        if (!razorpay_is_configured()) {
            return [
                'success' => false,
                'error' => 'Razorpay is not configured. Please set key_id and key_secret.',
                'http_code' => 0,
            ];
        }

        $methodUpper = strtoupper(trim((string) $method));
        $url = 'https://api.razorpay.com' . $path;
        $responseBody = false;
        $httpCode = 0;
        $transportError = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);

            if ($methodUpper === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                if ($payload !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                }
            }

            $responseBody = curl_exec($ch);
            $transportError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            if (!ini_get('allow_url_fopen')) {
                return [
                    'success' => false,
                    'error' => 'Neither cURL nor allow_url_fopen is enabled for Razorpay integration.',
                    'http_code' => 0,
                ];
            }

            $authorization = 'Authorization: Basic ' . base64_encode($config['key_id'] . ':' . $config['key_secret']);
            $headers = "Content-Type: application/json\r\n{$authorization}\r\n";
            $httpConfig = [
                'method' => $methodUpper,
                'header' => $headers,
                'timeout' => 20,
                'ignore_errors' => true,
            ];

            if ($methodUpper === 'POST' && $payload !== null) {
                $httpConfig['content'] = (string) json_encode($payload);
            }

            $context = stream_context_create([
                'http' => $httpConfig,
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $responseBody = @file_get_contents($url, false, $context);
            if ($responseBody === false) {
                $lastError = error_get_last();
                $transportError = trim((string) ($lastError['message'] ?? 'Unable to reach Razorpay API.'));
            }

            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', (string) $headerLine, $matches)) {
                        $httpCode = (int) ($matches[1] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($responseBody === false || $transportError !== '') {
            return [
                'success' => false,
                'error' => 'Unable to reach Razorpay API: ' . $transportError,
                'http_code' => $httpCode,
            ];
        }

        $response = json_decode((string) $responseBody, true);
        if (!is_array($response)) {
            return [
                'success' => false,
                'error' => 'Invalid response from Razorpay API.',
                'http_code' => $httpCode,
            ];
        }

        if ($httpCode >= 400 || isset($response['error'])) {
            $errorDescription = (string) ($response['error']['description'] ?? $response['error']['message'] ?? 'Razorpay request failed.');

            return [
                'success' => false,
                'error' => $errorDescription,
                'http_code' => $httpCode,
                'response' => $response,
            ];
        }

        return [
            'success' => true,
            'data' => $response,
            'http_code' => $httpCode,
        ];
    }
}

if (!function_exists('razorpay_create_order')) {
    function razorpay_create_order($amountInPaise, $receipt, array $notes = [])
    {
        $config = razorpay_load_config();

        if (!razorpay_is_configured()) {
            return [
                'success' => false,
                'error' => 'Razorpay is not configured. Please set key_id and key_secret.',
            ];
        }

        $payload = [
            'amount' => (int) $amountInPaise,
            'currency' => $config['currency'],
            'receipt' => substr((string) $receipt, 0, 40),
            'payment_capture' => 1,
        ];

        if (!empty($notes)) {
            $payload['notes'] = $notes;
        }

        $orderRequest = razorpay_api_request('POST', '/v1/orders', $payload);
        if (!(bool) ($orderRequest['success'] ?? false)) {
            return [
                'success' => false,
                'error' => (string) ($orderRequest['error'] ?? 'Razorpay order creation failed.'),
            ];
        }

        $response = (array) ($orderRequest['data'] ?? []);
        if (empty($response['id'])) {
            return [
                'success' => false,
                'error' => 'Razorpay order id was not returned.',
            ];
        }

        return [
            'success' => true,
            'order' => $response,
        ];
    }
}

if (!function_exists('razorpay_verify_payment_with_api')) {
    function razorpay_verify_payment_with_api($orderId, $paymentId)
    {
        $safePaymentId = rawurlencode((string) $paymentId);
        $paymentRequest = razorpay_api_request('GET', '/v1/payments/' . $safePaymentId);

        if (!(bool) ($paymentRequest['success'] ?? false)) {
            return [
                'success' => false,
                'error' => (string) ($paymentRequest['error'] ?? 'Unable to verify payment via Razorpay API.'),
            ];
        }

        $paymentData = (array) ($paymentRequest['data'] ?? []);
        $apiOrderId = trim((string) ($paymentData['order_id'] ?? ''));
        $apiPaymentId = trim((string) ($paymentData['id'] ?? ''));
        $apiStatus = strtolower(trim((string) ($paymentData['status'] ?? '')));

        if ($apiPaymentId === '' || $apiPaymentId !== (string) $paymentId) {
            return [
                'success' => false,
                'error' => 'Payment id mismatch in Razorpay verification.',
            ];
        }

        if ($apiOrderId === '' || $apiOrderId !== (string) $orderId) {
            return [
                'success' => false,
                'error' => 'Order id mismatch in Razorpay verification.',
            ];
        }

        if (!in_array($apiStatus, ['authorized', 'captured'], true)) {
            return [
                'success' => false,
                'error' => 'Payment is not completed yet. Current status: ' . ($apiStatus !== '' ? $apiStatus : 'unknown'),
            ];
        }

        return [
            'success' => true,
            'payment' => $paymentData,
        ];
    }
}

if (!function_exists('razorpay_verify_payment_signature')) {
    function razorpay_verify_payment_signature($orderId, $paymentId, $signature)
    {
        $config = razorpay_load_config();

        if (!razorpay_is_configured()) {
            return false;
        }

        $payload = (string) $orderId . '|' . (string) $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $config['key_secret']);

        return hash_equals($expectedSignature, (string) $signature);
    }
}
