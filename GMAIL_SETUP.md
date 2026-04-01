# Gmail SMTP Setup Guide

## Configuration File Location
**File:** `config/email-config.php`

## Step 1: Get Gmail App Password

### For Gmail Users:

1. Go to: https://myaccount.google.com/
2. Click **Security** in left menu
3. Enable **2-Step Verification** (if not already enabled)
4. Go back to **Security**
5. Find **App passwords** near the bottom
6. Select **Mail** and **Windows Computer** (or your device)
7. Copy the 16-character password generated

### Example:
```
Gmail generates: abcd efgh ijkl mnop
```

## Step 2: Configure in School ERP

Open: `config/email-config.php`

Replace these values:

```php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    
    'smtp_email' => 'your-email@gmail.com',           // Your Gmail address
    'smtp_password' => 'abcd efgh ijkl mnop',         // 16-char App Password
    
    'from_email' => 'your-email@gmail.com',           // Same as above
    'from_name' => 'School ERP System',               // Display name
];
```

## Step 3: Test Password Reset

1. Go to: **Forgot Password** page
2. Enter any user's email
3. Check if email was received
4. If successful, it works! ✓

## Troubleshooting

### "SMTP authentication failed"
- ✓ Check you used 16-char App Password (not regular password)
- ✓ Verify 2-Step Verification is **enabled**
- ✓ Check you copied the password correctly
- ✓ Try logging out of Gmail and back in

### "Cannot connect to SMTP"
- ✓ Check firewall allows port 465
- ✓ Try changing `smtp_secure` from `ssl` to `tls` and port from `465` to `587`
- ✓ Check internet connection

### Email not sent but no error
- Check your Gmail **Sent Mail**
- Check spam/junk folder on recipient's email
- Review server error logs

## Alternative: Other Email Providers

### Office 365/Outlook:
```php
'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
```

### SendGrid:
```php
'smtp_host' => 'smtp.sendgrid.net',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_email' => 'apikey',
'smtp_password' => 'SG.xxxxx...',
```

## Security Notes

⚠️ **IMPORTANT:**
- Never share your App Password
- Keep `config/email-config.php` private
- Use `.gitignore` if in version control:
```
config/email-config.php
```

That's it! Your password reset emails will now be sent via Gmail SMTP. 📧
