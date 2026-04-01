<?php
/**
 * Email Mailer using SMTP
 * Works with Gmail and other SMTP providers without external dependencies.
 */

class EmailMailer {
    private $config;
    private $error = '';

    public function __construct() {
        $this->config = include __DIR__ . '/../config/email-config.php';
    }

    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $htmlBody) {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error = 'Invalid recipient email';
            return false;
        }

        if (!$this->isConfigValid()) {
            error_log('SMTP config error: ' . $this->error);
            return false;
        }

        $result = $this->sendViaSMTP($to, $subject, $htmlBody);
        if (!$result) {
            error_log('SMTP send failed: ' . $this->error);
        }

        return $result;
    }

    /**
     * Validate required configuration values.
     */
    private function isConfigValid() {
        $requiredKeys = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_email', 'smtp_password'];
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || trim((string) $this->config[$key]) === '') {
                $this->error = 'Missing SMTP configuration key: ' . $key;
                return false;
            }
        }

        $port = (int) $this->config['smtp_port'];
        if ($port < 1 || $port > 65535) {
            $this->error = 'Invalid SMTP port';
            return false;
        }

        $secure = strtolower(trim((string) $this->config['smtp_secure']));
        if (!in_array($secure, ['ssl', 'tls'], true)) {
            $this->error = 'SMTP security must be ssl or tls';
            return false;
        }

        if (!filter_var((string) $this->config['smtp_email'], FILTER_VALIDATE_EMAIL)) {
            $this->error = 'Invalid SMTP email address';
            return false;
        }

        return true;
    }

    /**
     * Send an SMTP command and assert one of expected response codes.
     */
    private function sendCommand($socket, $command, array $expectedCodes) {
        fwrite($socket, $command . "\r\n");
        $response = '';

        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            // Multi-line SMTP responses continue while the 4th char is '-'.
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            $this->error = 'No response received for command: ' . $command;
            return false;
        }

        foreach ($expectedCodes as $code) {
            if (strpos($response, (string) $code) === 0) {
                return true;
            }
        }

        $this->error = 'SMTP command failed [' . $command . ']: ' . trim($response);
        return false;
    }

    /**
     * Send via direct SMTP connection.
     */
    private function sendViaSMTP($to, $subject, $htmlBody) {
        $host = (string) $this->config['smtp_host'];
        $port = (int) $this->config['smtp_port'];
        $secure = strtolower(trim((string) $this->config['smtp_secure']));
        $smtpEmail = trim((string) $this->config['smtp_email']);
        // Gmail app passwords are often copied with spaces; strip them safely.
        $smtpPassword = str_replace(' ', '', trim((string) $this->config['smtp_password']));
        $fromEmail = trim((string) ($this->config['from_email'] ?? $smtpEmail));
        $fromName = trim((string) ($this->config['from_name'] ?? 'School ERP System'));

        $transportHost = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host;
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($transportHost, $port, $errno, $errstr, 30);
        if (!$socket) {
            $this->error = "Cannot connect to SMTP server {$host}:{$port} ({$errno}) {$errstr}";
            return false;
        }

        stream_set_timeout($socket, 30);

        // Read initial greeting (220)
        $greeting = fgets($socket, 1024);
        if ($greeting === false || strpos($greeting, '220') !== 0) {
            fclose($socket);
            $this->error = 'SMTP greeting failed: ' . trim((string) $greeting);
            return false;
        }

        if (!$this->sendCommand($socket, 'EHLO localhost', [250])) {
            fclose($socket);
            return false;
        }

        if ($secure === 'tls') {
            if (!$this->sendCommand($socket, 'STARTTLS', [220])) {
                fclose($socket);
                return false;
            }

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                $this->error = 'Failed to enable TLS encryption';
                return false;
            }

            if (!$this->sendCommand($socket, 'EHLO localhost', [250])) {
                fclose($socket);
                return false;
            }
        }

        if (!$this->sendCommand($socket, 'AUTH LOGIN', [334])) {
            fclose($socket);
            return false;
        }

        if (!$this->sendCommand($socket, base64_encode($smtpEmail), [334])) {
            fclose($socket);
            return false;
        }

        if (!$this->sendCommand($socket, base64_encode($smtpPassword), [235])) {
            fclose($socket);
            $this->error = 'SMTP authentication failed. Check app password and Gmail security settings.';
            return false;
        }

        if (!$this->sendCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250])) {
            fclose($socket);
            return false;
        }

        if (!$this->sendCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251])) {
            fclose($socket);
            return false;
        }

        if (!$this->sendCommand($socket, 'DATA', [354])) {
            fclose($socket);
            return false;
        }

        $message = "From: {$fromName} <{$fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= 'Subject: ' . $subject . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n.\r\n";

        fwrite($socket, $message);
        $dataResponse = fgets($socket, 1024);
        if ($dataResponse === false || strpos($dataResponse, '250') !== 0) {
            fclose($socket);
            $this->error = 'SMTP message send failed: ' . trim((string) $dataResponse);
            return false;
        }

        $this->sendCommand($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    }

    public function getError() {
        return $this->error;
    }
}
