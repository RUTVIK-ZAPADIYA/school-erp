# Email Configuration Guide

## Overview
The Email Configuration page allows administrators to set up SMTP (Simple Mail Transfer Protocol) settings for the School ERP System to send automated emails such as password resets, email verifications, and system notifications.

## Location
- **Admin Panel:** Settings → Email Config
- **File:** `admin/email-config.php`

## Configuration Steps

### 1. Enable Email Notifications
Check the "Enable Email Notifications" checkbox to activate email sending functionality.

### 2. SMTP Server Configuration

#### SMTP Host
- **Description:** The address of your email provider's SMTP server
- **Common Examples:**
  - Gmail: `smtp.gmail.com`
  - Outlook/Office 365: `smtp.office365.com`
  - SendGrid: `smtp.sendgrid.net`
  - Mailtrap: `smtp.mailtrap.io`
  - Custom Server: Your server address

#### SMTP Port
- **Description:** The port number for SMTP communication
- **Common Ports:**
  - **587** (TLS - Recommended): Standard secure connection
  - **465** (SSL): Legacy secure connection
  - **25** (Unencrypted): Not recommended for security reasons
- **Valid Range:** 1-65535

### 3. SMTP Credentials

#### SMTP Username
- Usually your email address or account username
- **Example:** `your-email@gmail.com`

#### SMTP Password
- Your email account password or app-specific password
- **Important Security Notes:**
  - For Gmail: Use an app-specific password (requires 2FA enabled)
  - Never share your password
  - Passwords are encrypted in the database

## Email Provider Setup Instructions

### Gmail Setup
1. Enable 2-Step Verification in your Google Account
2. Generate an "App password" for Mail
3. Use the generated password (16 characters) as the SMTP Password
4. **SMTP Host:** smtp.gmail.com
5. **SMTP Port:** 587
6. **Username:** Your Gmail address

### Outlook/Office 365 Setup
1. Go to account.microsoft.com
2. Click "Security" and manage authentication
3. Use your email and password
4. **SMTP Host:** smtp.office365.com
5. **SMTP Port:** 587

### SendGrid Setup
1. Create a SendGrid account
2. Create an API key
3. **SMTP Host:** smtp.sendgrid.net
4. **SMTP Port:** 587
5. **Username:** apikey
6. **Password:** Your API key

### Mailtrap Setup (Development/Testing)
1. Create a Mailtrap account (free tier available)
2. Get credentials from your inbox
3. **SMTP Host:** smtp.mailtrap.io
4. **SMTP Port:** 2525 (or 587)
5. Copy username and password from Mailtrap dashboard

## Sender Information

### From Email Address
- Email address that appears as the sender
- **Recommendation:** Use a no-reply email like `noreply@school.com`
- Must be a valid email format

### From Name
- Display name shown to recipients
- **Example:** "School ERP System" or "ABC School Notifications"

## Testing Your Configuration

1. Navigate to the "Send Test Email" section
2. Enter your test email address
3. Click "Send Test Email"
4. Check your inbox (and spam folder) for the test message
5. If successful, your SMTP configuration is working correctly

## Features Using Email Configuration

Once configured, the system will send emails for:
- **Password Reset Requests:** When users request password recovery
- **Email Verification:** When new users register their accounts
- **Welcome Messages:** When new users are added to the system
- **System Notifications:** For important school announcements

## Email Functions for Developers

The system includes helper functions in `admin/email-helpers.php`:

```php
// Send custom email
sendEmail($connection, $to, $subject, $htmlBody, $textBody, $attachments);

// Send password reset email
sendPasswordResetEmail($connection, $to, $userName, $resetLink);

// Send email verification
sendVerificationEmail($connection, $to, $userName, $verificationLink);

// Send welcome email
sendWelcomeEmail($connection, $to, $userName, $role);
```

## Troubleshooting

### Email Not Sending?
1. **Check SMTP Settings:**
   - Verify SMTP Host and Port are correct
   - Confirm Username and Password
   - Test in Settings → Email Config → Send Test Email

2. **Check Server Logs:**
   - Email logs are stored in `logs/email.log`
   - Check for error messages

3. **Verify Email Notifications are Enabled:**
   - Go to Email Config page
   - Ensure checkbox is checked

4. **Check Firewall/Security:**
   - Outbound SMTP port may be blocked by ISP
   - Try port 587 (TLS) instead of port 465
   - Contact your hosting provider if still blocked

5. **Gmail Users:**
   - Ensure 2-Step Verification is enabled
   - Check if the "App password" is correct (not regular password)
   - "Less secure app access" should be OFF (use app passwords instead)

### Emails Going to Spam?
1. Update the "From Name" and "From Email" to match your domain
2. Ensure SPF, DKIM, and DMARC records are configured
3. Test with different email addresses
4. Add headers to improve email deliverability

### Certificate/SSL Errors
1. Update PHP's CA certificate bundle (php.ini)
2. Try port 587 (TLS) instead of port 465 (SSL)
3. Contact your hosting provider for SSL support

## Database Schema

### Email Settings Table
```sql
CREATE TABLE email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
```

### Fields Stored
- `smtp_enabled` - Enable/disable flag (0 or 1)
- `smtp_host` - SMTP server address
- `smtp_port` - SMTP port number
- `smtp_username` - SMTP authentication username
- `smtp_password` - SMTP authentication password
- `from_email` - Sender email address
- `from_name` - Sender display name

## Security Recommendations

1. **Use Strong Passwords:** Ensure your SMTP credentials are strong
2. **Limit Access:** Only administrators should configure email settings
3. **Regular Updates:** Keep PHP and server software updated
4. **Monitor Logs:** Review email.log regularly for issues
5. **Test Regularly:** Send test emails to verify configuration
6. **Backup Settings:** Keep a record of your SMTP configuration
7. **Use App Passwords:** For Gmail, always use app-specific passwords, not your main account password

## Support

For additional help:
- Check the test email functionality for connection issues
- Review email logs at `logs/email.log`
- Contact your email provider's support team
- Consult the School ERP System documentation
