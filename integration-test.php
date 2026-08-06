<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/integrations/ClickUpClient.php';
require_once __DIR__ . '/includes/integrations/SmtpMailer.php';

header('Content-Type: text/plain; charset=utf-8');
$expected = hash('sha256', SMTP_USERNAME . '|' . CLICKUP_LIST_ID . '|c2c-test');
$key = (string) ($_GET['key'] ?? '');
if (!hash_equals($expected, $key)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

echo "C2C integration diagnostics\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'enabled' : 'missing') . "\n";
echo "cURL: " . (extension_loaded('curl') ? 'enabled' : 'missing') . "\n";
echo "SMTP host: " . SMTP_HOST . ':' . SMTP_PORT . ' (' . SMTP_ENCRYPTION . ")\n";
echo "SMTP username: " . SMTP_USERNAME . "\n";
echo "Mail recipient: " . MAIL_TO_ADDRESS . "\n";

try {
    $mailer = new SmtpMailer();
    $mailer->send(
        MAIL_TO_ADDRESS,
        MAIL_TO_NAME,
        'C2C SMTP diagnostic ' . date('Y-m-d H:i:s'),
        '<p>This is a C2C SMTP diagnostic email.</p>',
        'This is a C2C SMTP diagnostic email.'
    );
    echo "SMTP: SUCCESS\n";
} catch (Throwable $e) {
    echo "SMTP: FAILED\n" . $e->getMessage() . "\n";
}

try {
    $clickUp = new ClickUpClient(CLICKUP_API_TOKEN, CLICKUP_LIST_ID);
    $lead = [
        'mode'=>'consult','full_name'=>'C2C Integration Test','project_type'=>'Diagnostic',
        'route_tag'=>'consult_first','email'=>MAIL_TO_ADDRESS,'phone'=>'N/A','address'=>'',
        'city_zip'=>'Western New York','start_window'=>'','budget'=>'','decision_maker'=>'',
        'source'=>'Integration test','notes'=>'Safe diagnostic task. Delete after verification.',
        'created_at'=>date(DATE_ATOM),'lead_id'=>'TEST-' . date('YmdHis'),'page_url'=>url('integration-test.php'),'uploads'=>[]
    ];
    $task = $clickUp->createLeadTask($lead);
    echo "CLICKUP: SUCCESS\nTask: " . ($task['url'] ?: $task['id']) . "\n";
} catch (Throwable $e) {
    echo "CLICKUP: FAILED\n" . $e->getMessage() . "\n";
}
