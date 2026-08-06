<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/integrations/ClickUpClient.php';
require_once __DIR__ . '/includes/integrations/SmtpMailer.php';
header('Content-Type: text/plain; charset=utf-8');

// Temporary diagnostic page. Delete it after testing.
echo "C2C integration diagnostics\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'enabled' : 'MISSING') . "\n";
echo "cURL: " . (extension_loaded('curl') ? 'enabled' : 'MISSING') . "\n";
echo "SMTP: " . SMTP_HOST . ':' . SMTP_PORT . '/' . SMTP_ENCRYPTION . "\n";
echo "SMTP user: " . SMTP_USERNAME . "\n";
echo "Mail to: " . MAIL_TO_ADDRESS . "\n";
echo "ClickUp list: " . CLICKUP_LIST_ID . "\n\n";

try {
    (new SmtpMailer())->send(MAIL_TO_ADDRESS, MAIL_TO_NAME, 'C2C SMTP test ' . date('c'), '<p>C2C SMTP test succeeded.</p>', 'C2C SMTP test succeeded.');
    echo "EMAIL: SUCCESS\n";
} catch (Throwable $e) {
    echo "EMAIL: FAILED\n" . $e->getMessage() . "\n";
}

echo "\n";
try {
    $client = new ClickUpClient(CLICKUP_API_TOKEN, CLICKUP_LIST_ID);
    $lead = [
        'mode'=>'consult','full_name'=>'C2C Test Lead','project_type'=>'Integration Test','route_tag'=>'consult_first',
        'email'=>MAIL_TO_ADDRESS,'phone'=>'(716) 555-0123','address'=>'','city_zip'=>'Western New York',
        'start_window'=>'Testing','budget'=>'Testing','decision_maker'=>'Yes','source'=>'Diagnostic',
        'notes'=>'Delete this test task after verification.','created_at'=>date(DATE_ATOM),
        'lead_id'=>'TEST-' . date('YmdHis'),'page_url'=>url('integration-test.php'),'uploads'=>[]
    ];
    $task = $client->createLeadTask($lead);
    echo "CLICKUP: SUCCESS\n" . ($task['url'] ?: $task['id']) . "\n";
} catch (Throwable $e) {
    echo "CLICKUP: FAILED\n" . $e->getMessage() . "\n";
}
