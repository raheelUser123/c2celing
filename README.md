# Ceiling 2 Cellar - Custom PHP Website

Standalone PHP/HTML website. It does not require WordPress.

## Local setup (XAMPP)

1. Copy `c2c-custom-php` to `C:\xampp\htdocs\`.
2. Start Apache.
3. Open `http://localhost/c2c-custom-php/`.
4. Enable the PHP extensions `curl`, `openssl`, and `fileinfo` in `php.ini`.
5. Copy `config.local.example.php` to `config.local.php`.
6. Enter SMTP and ClickUp credentials in `config.local.php`.

## Required private keys

```php
define('CLICKUP_API_TOKEN', 'REPLACE_WITH_NEW_CLICKUP_API_TOKEN');
define('CLICKUP_LIST_ID', 'REPLACE_WITH_C2C_CLICKUP_LIST_ID');
define('SMTP_HOST', 'smtp.your-email-provider.com');
define('SMTP_USERNAME', 'forms@ceiling2cellar.com');
define('SMTP_PASSWORD', 'REPLACE_WITH_SMTP_PASSWORD');
```

Never add `config.local.php` to Git or a public download. A previously exposed ClickUp token should be revoked before use.

## Lead workflow

Every consultation and estimate form uses `handlers/submit-lead.php`.

1. Server-side validation and rate limiting
2. Secure upload validation for JPG, PNG, and PDF
3. Local lead backup in `uploads/leads.jsonl`
4. ClickUp task creation
5. ClickUp attachment upload
6. HTML admin email with Reply-To set to the customer
7. HTML customer confirmation email
8. Redirect to the matching thank-you page

Failures are written to `storage/logs/forms-YYYY-MM.log`. In production, the visitor receives a clear delivery-delay message when ClickUp or both emails fail. The lead remains stored locally for recovery.

## ClickUp behavior

Tasks are created in the configured List using ClickUp API v2. Task names follow:

- `Consult Lead - Customer Name - Project Type`
- `Estimate Lead - Customer Name - Project Type`

Default tags include `website-lead`, the lead type, routing type, and `c2c-website-lead`. Optional custom field IDs can be added in `config.local.php`.

## SMTP

The project includes a dependency-free SMTP client supporting TLS, SSL, and authenticated SMTP. On XAMPP, regular PHP `mail()` is not used.

## Test safely

Set these values during local testing:

```php
define('APP_ENV', 'development');
define('DEBUG_MODE', true);
```

A successful response can then show ClickUp/email integration errors in the browser network response while still preserving the local lead backup. Return both values to production settings before launch.

## Production checklist

- Replace all placeholder keys and contact details
- Use HTTPS and the live `SITE_URL`
- Confirm Apache allows `.htaccess`
- Confirm `storage/` and `uploads/leads.jsonl` are inaccessible publicly
- Submit a real consultation test
- Submit a real estimate test with photos
- Confirm ClickUp tasks and attachments
- Confirm admin and customer emails
- Review the form log for errors
