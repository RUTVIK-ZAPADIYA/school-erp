<?php
/**
 * Email Configuration with SMTP
 * Add your Gmail credentials here
 */

require_once __DIR__ . '/../includes/env_loader.php';
school_erp_load_env(__DIR__ . '/../.env');

return [
    // SMTP Server Settings
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: 465),
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'ssl',  // 'ssl' for port 465, 'tls' for port 587
    
    // Gmail Credentials
    'smtp_email' => getenv('SMTP_EMAIL') ?: 'horizonamailer@gmail.com',        // Your Gmail address
    'smtp_password' => getenv('SMTP_PASSWORD') ?: 'tstw yjsm lqhc boyr',   // Gmail App Password (16 chars)
    
    // Sender Info
    'from_email' => getenv('FROM_EMAIL') ?: 'horizonamailer@gmail.com',
    'from_name' => getenv('FROM_NAME') ?: 'School ERP System',
];
