<?php
declare(strict_types=1);

/**
 * Ceiling 2 Cellar server configuration.
 * Keep live passwords and API tokens in config.local.php only.
 */
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

/** Detect the public site URL on Apache/Hostinger, including a subfolder install. */
function detected_site_url(): string
{
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || $forwardedProto === 'https';
    $scheme = $https ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = trim(explode(',', $host)[0]);

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if (str_ends_with($directory, '/handlers') || str_ends_with($directory, '/includes')) {
        $directory = rtrim(str_replace('\\', '/', dirname($directory)), '/.');
    }
    $basePath = ($directory === '' || $directory === '/') ? '' : '/' . ltrim($directory, '/');
    return $scheme . '://' . $host . $basePath;
}

// Site basics. Leave SITE_URL blank to auto-detect localhost, live domain, or subfolder.
if (!defined('SITE_NAME')) define('SITE_NAME', env_value('SITE_NAME', 'Ceiling 2 Cellar Remodeling'));
if (!defined('SITE_URL')) define('SITE_URL', env_value('SITE_URL', ''));
if (!defined('BUSINESS_PHONE')) define('BUSINESS_PHONE', env_value('BUSINESS_PHONE', '(716) 555-0123'));
if (!defined('BUSINESS_PHONE_RAW')) define('BUSINESS_PHONE_RAW', env_value('BUSINESS_PHONE_RAW', '+17165550123'));
if (!defined('BUSINESS_EMAIL')) define('BUSINESS_EMAIL', env_value('BUSINESS_EMAIL', 'info@ceiling2cellar.com'));
if (!defined('SERVICE_AREA')) define('SERVICE_AREA', env_value('SERVICE_AREA', 'Western New York'));
if (!defined('BUSINESS_ADDRESS')) define('BUSINESS_ADDRESS', env_value('BUSINESS_ADDRESS', 'Western New York'));
if (!defined('FORM_RATE_LIMIT_SECONDS')) define('FORM_RATE_LIMIT_SECONDS', (int) env_value('FORM_RATE_LIMIT_SECONDS', '20'));
if (!defined('MAX_UPLOAD_MB')) define('MAX_UPLOAD_MB', (int) env_value('MAX_UPLOAD_MB', '8'));
if (!defined('MAX_UPLOAD_FILES')) define('MAX_UPLOAD_FILES', (int) env_value('MAX_UPLOAD_FILES', '8'));
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('STORAGE_DIR')) define('STORAGE_DIR', __DIR__ . '/storage');

// SMTP placeholders
if (!defined('SMTP_HOST')) define('SMTP_HOST', env_value('SMTP_HOST', 'smtp.hostinger.com'));
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int) env_value('SMTP_PORT', '587'));
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', env_value('SMTP_ENCRYPTION', 'tls'));
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', env_value('SMTP_USERNAME', 'info@ceiling2cellar.com'));
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', env_value('SMTP_PASSWORD', 'Celing123#@!'));
if (!defined('MAIL_FROM_ADDRESS')) define('MAIL_FROM_ADDRESS', env_value('MAIL_FROM_ADDRESS', ''));
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Ceiling 2 Cellar Website'));
if (!defined('MAIL_TO_ADDRESS')) define('MAIL_TO_ADDRESS', env_value('MAIL_TO_ADDRESS', ''));
if (!defined('MAIL_TO_NAME')) define('MAIL_TO_NAME', env_value('MAIL_TO_NAME', 'Ceiling 2 Cellar Team'));
if (!defined('MAIL_REPLY_TO')) define('MAIL_REPLY_TO', env_value('MAIL_REPLY_TO', MAIL_FROM_ADDRESS));
if (!defined('SMTP_ENABLED')) define('SMTP_ENABLED', env_value('SMTP_ENABLED', '1') === '1');

// ClickUp placeholders
if (!defined('CLICKUP_ENABLED')) define('CLICKUP_ENABLED', env_value('CLICKUP_ENABLED', '1') === '1');
if (!defined('CLICKUP_API_TOKEN')) define('CLICKUP_API_TOKEN', env_value('CLICKUP_API_TOKEN', 'pk_87315537_KLOCR5UYJQE40QBQCZZ06WXGOR2GGVUS'));
if (!defined('CLICKUP_LIST_ID')) define('CLICKUP_LIST_ID', env_value('CLICKUP_LIST_ID', '901108518949'));
if (!defined('CLICKUP_DEFAULT_ASSIGNEE_ID')) define('CLICKUP_DEFAULT_ASSIGNEE_ID', env_value('CLICKUP_DEFAULT_ASSIGNEE_ID', ''));
if (!defined('CLICKUP_DEFAULT_TAG')) define('CLICKUP_DEFAULT_TAG', env_value('CLICKUP_DEFAULT_TAG', 'c2c-website-lead'));
if (!defined('CLICKUP_STATUS')) define('CLICKUP_STATUS', env_value('CLICKUP_STATUS', ''));
if (!defined('CLICKUP_PRIORITY')) define('CLICKUP_PRIORITY', (int) env_value('CLICKUP_PRIORITY', '3'));
if (!defined('CLICKUP_FIELD_SERVICE')) define('CLICKUP_FIELD_SERVICE', env_value('CLICKUP_FIELD_SERVICE', ''));
if (!defined('CLICKUP_FIELD_BUDGET')) define('CLICKUP_FIELD_BUDGET', env_value('CLICKUP_FIELD_BUDGET', ''));
if (!defined('CLICKUP_FIELD_SOURCE')) define('CLICKUP_FIELD_SOURCE', env_value('CLICKUP_FIELD_SOURCE', ''));
if (!defined('CLICKUP_FIELD_LEAD_TYPE')) define('CLICKUP_FIELD_LEAD_TYPE', env_value('CLICKUP_FIELD_LEAD_TYPE', ''));

if (!defined('APP_ENV')) define('APP_ENV', env_value('APP_ENV', 'production'));
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', env_value('DEBUG_MODE', '0') === '1');
date_default_timezone_set(env_value('APP_TIMEZONE', 'America/New_York'));
