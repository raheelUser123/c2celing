<?php
declare(strict_types=1);

// Leave blank to auto-detect the live domain and any installation subfolder.
define('SITE_URL', '');

define('BUSINESS_PHONE', '(716) 555-0123');
define('BUSINESS_PHONE_RAW', '+17165550123');
define('BUSINESS_EMAIL', 'info@ceiling2cellar.com');
define('BUSINESS_ADDRESS', 'Western New York');

// Hostinger SMTP
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'info@ceiling2cellar.com');
define('SMTP_PASSWORD', 'Celing123#@!');
define('MAIL_FROM_ADDRESS', 'info@ceiling2cellar.com');
define('MAIL_FROM_NAME', 'Ceiling 2 Cellar Website');
define('MAIL_TO_ADDRESS', 'REPLACE_WITH_LEAD_INBOX');
define('MAIL_TO_NAME', 'Ceiling 2 Cellar Team');
define('MAIL_REPLY_TO', 'info@ceiling2cellar.com');

// ClickUp
define('CLICKUP_ENABLED', true);
define('CLICKUP_API_TOKEN', 'pk_87315537_KLOCR5UYJQE40QBQCZZ06WXGOR2GGVUS');
define('CLICKUP_LIST_ID', '901108518949');
define('CLICKUP_DEFAULT_ASSIGNEE_ID', '');
define('CLICKUP_DEFAULT_TAG', 'c2c-website-lead');
define('CLICKUP_STATUS', '');
define('CLICKUP_PRIORITY', 3);
define('CLICKUP_FIELD_SERVICE', '');
define('CLICKUP_FIELD_BUDGET', '');
define('CLICKUP_FIELD_SOURCE', '');
define('CLICKUP_FIELD_LEAD_TYPE', '');

define('APP_ENV', 'production');
define('DEBUG_MODE', false);
