<?php
/**
 * Email Helper - Password Reset with SMTP
 */

require_once __DIR__ . '/mailer.php';

function sendPasswordResetEmail($emailAddress, $userName, $resetLink) {
  $emailConfig = include __DIR__ . '/../config/email-config.php';
  
  $fromName = $emailConfig['from_name'] ?? 'School ERP System';
  
  $subject = 'Password Reset Request - ' . $fromName;
  
  $htmlBody = "
    <html><body>
      <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
        <div style=\"background: linear-gradient(135deg, #1e6ceb 0%, #1555d1 100%); padding: 30px; text-align: center; color: white; border-radius: 8px 8px 0 0;\">
          <h1 style=\"margin: 0; font-size: 24px;\">" . htmlspecialchars($fromName) . "</h1>
        </div>
        <div style=\"background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;\">
          <h2 style=\"color: #1e3a8a; margin-top: 0;\">Password Reset Request</h2>
          <p style=\"color: #64748b; line-height: 1.6;\">Hello " . htmlspecialchars($userName) . ",</p>
          <p style=\"color: #64748b; line-height: 1.6;\">You requested to reset your password. Click the button below to proceed:</p>
          <div style=\"text-align: center; margin: 30px 0;\">
            <a href=\"" . htmlspecialchars($resetLink) . "\" style=\"background: #1e6ceb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;\">Reset Password</a>
          </div>
          <p style=\"color: #95a7c1; font-size: 12px; margin-top: 20px;\">Or copy this link: " . htmlspecialchars($resetLink) . "</p>
          <p style=\"color: #95a7c1; font-size: 12px; margin-top: 20px;\">This link expires in 30 minutes. If you didn't request this, please ignore this email and your password will remain unchanged.</p>
        </div>
      </div>
    </body></html>
  ";

  $mailer = new EmailMailer();
  $sent = $mailer->send($emailAddress, $subject, $htmlBody);
  if (!$sent) {
    error_log('Password reset email failed for ' . $emailAddress . ': ' . $mailer->getError());
  }

  return $sent;
}
