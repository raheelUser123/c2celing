<?php
/** Copy this file to config.local.php and replace every placeholder. */

define('SITE_URL', 'https://ceiling2cellar.com');
define('BUSINESS_PHONE', '(716) 555-0123');
define('BUSINESS_PHONE_RAW', '+17165550123');
define('BUSINESS_EMAIL', 'hello@ceiling2cellar.com');
define('BUSINESS_ADDRESS', 'Western New York');

// SMTP
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.your-email-provider.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // tls, ssl, or none
define('SMTP_USERNAME', 'forms@ceiling2cellar.com');
define('SMTP_PASSWORD', 'REPLACE_WITH_SMTP_PASSWORD');
define('MAIL_FROM_ADDRESS', 'forms@ceiling2cellar.com');
define('MAIL_FROM_NAME', 'Ceiling 2 Cellar Website');
define('MAIL_TO_ADDRESS', 'leads@ceiling2cellar.com');
define('MAIL_TO_NAME', 'Ceiling 2 Cellar Team');
define('MAIL_REPLY_TO', 'hello@ceiling2cellar.com');

// ClickUp
define('CLICKUP_ENABLED', true);
define('CLICKUP_API_TOKEN', 'REPLACE_WITH_NEW_CLICKUP_API_TOKEN');
define('CLICKUP_LIST_ID', 'REPLACE_WITH_C2C_CLICKUP_LIST_ID');
define('CLICKUP_DEFAULT_ASSIGNEE_ID', '');
define('CLICKUP_DEFAULT_TAG', 'c2c-website-lead');
define('CLICKUP_STATUS', ''); // Example: new lead. Leave blank to use List default.
define('CLICKUP_PRIORITY', 3); // 1 urgent, 2 high, 3 normal, 4 low

// Optional List custom field IDs. Values are sent only when an ID is provided.
define('CLICKUP_FIELD_SERVICE', '');
define('CLICKUP_FIELD_BUDGET', '');
define('CLICKUP_FIELD_SOURCE', '');
define('CLICKUP_FIELD_LEAD_TYPE', '');

// Development
define('APP_ENV', 'production');
define('DEBUG_MODE', false);
