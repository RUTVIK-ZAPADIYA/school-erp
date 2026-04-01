<?php
/**
 * Email Configuration with SMTP
 * Add your Gmail credentials here
 */

return [
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',  // 'ssl' for port 465, 'tls' for port 587
    
    // Gmail Credentials
    'smtp_email' => 'horizonamailer@gmail.com',        // Your Gmail address
    'smtp_password' => 'tstw yjsm lqhc boyr',   // Gmail App Password (16 chars)
    
    // Sender Info
    'from_email' => 'horizonamailer@gmail.com',
    'from_name' => 'School ERP System',
];
