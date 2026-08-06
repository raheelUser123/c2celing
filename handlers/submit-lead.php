<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/integrations/ClickUpClient.php';
require_once __DIR__ . '/../includes/integrations/SmtpMailer.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function log_event(string $type, array $context): void
{
    $dir = STORAGE_DIR . '/logs';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $entry = [
        'time' => date(DATE_ATOM),
        'type' => $type,
        'context' => $context,
    ];
    file_put_contents($dir . '/forms-' . date('Y-m') . '.log', json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function render_template(string $file, array $vars): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

function text_summary(array $lead, string $clickUpUrl = ''): string
{
    $lines = [
        'New Ceiling 2 Cellar website lead',
        '',
        'Lead ID: ' . $lead['lead_id'],
        'Lead Type: ' . ucfirst($lead['mode']),
        'Routing: ' . $lead['route_tag'],
        'Name: ' . $lead['full_name'],
        'Email: ' . $lead['email'],
        'Phone: ' . $lead['phone'],
        'Project: ' . $lead['project_type'],
        'Address: ' . ($lead['address'] ?: 'Not provided'),
        'City / ZIP: ' . $lead['city_zip'],
        'Target Start: ' . ($lead['start_window'] ?: 'Not provided'),
        'Budget: ' . ($lead['budget'] ?: 'Not provided'),
        'Decision Maker: ' . ($lead['decision_maker'] ?: 'Not provided'),
        'Source: ' . ($lead['source'] ?: 'Not provided'),
        'Notes: ' . ($lead['notes'] ?: 'Not provided'),
        'Submitted: ' . $lead['created_at'],
    ];
    if ($clickUpUrl !== '') $lines[] = 'ClickUp: ' . $clickUpUrl;
    return implode("\n", $lines);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

// Honeypot: bots commonly fill hidden fields.
if (trim((string) ($_POST['company_website'] ?? '')) !== '') {
    json_response(200, ['ok' => true, 'redirect' => url('thanks-consult.php')]);
}

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rateDir = STORAGE_DIR . '/rate-limits';
if (!is_dir($rateDir)) mkdir($rateDir, 0775, true);
$rateFile = $rateDir . '/' . hash('sha256', $ip) . '.txt';
$lastSubmit = is_file($rateFile) ? (int) file_get_contents($rateFile) : 0;
if ($lastSubmit > 0 && (time() - $lastSubmit) < FORM_RATE_LIMIT_SECONDS) {
    json_response(429, ['ok' => false, 'message' => 'Please wait a moment before submitting again.']);
}

$mode = (($_POST['mode'] ?? 'consult') === 'estimate') ? 'estimate' : 'consult';
$required = ['full_name', 'email', 'phone', 'city_zip', 'project_type'];
foreach ($required as $field) {
    if (trim((string) ($_POST[$field] ?? '')) === '') {
        json_response(422, ['ok' => false, 'message' => 'Please complete all required fields.']);
    }
}

$email = trim((string) $_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(422, ['ok' => false, 'message' => 'Please enter a valid email address.']);
}

$phoneDigits = preg_replace('/\D+/', '', (string) $_POST['phone']) ?? '';
if (strlen($phoneDigits) < 10) {
    json_response(422, ['ok' => false, 'message' => 'Please enter a valid phone number.']);
}

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
$leadId = 'C2C-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
$leadUploadDir = UPLOAD_DIR . '/' . $leadId;
$savedRelative = [];
$savedAbsolute = [];

if (isset($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
    $count = min(count($_FILES['photos']['name']), MAX_UPLOAD_FILES);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];
    for ($i = 0; $i < $count; $i++) {
        $error = (int) ($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) {
            json_response(422, ['ok' => false, 'message' => 'One of the uploaded files could not be received.']);
        }
        $size = (int) ($_FILES['photos']['size'][$i] ?? 0);
        if ($size <= 0 || $size > MAX_UPLOAD_MB * 1024 * 1024) {
            json_response(422, ['ok' => false, 'message' => 'Each upload must be under ' . MAX_UPLOAD_MB . ' MB.']);
        }
        $tmp = (string) ($_FILES['photos']['tmp_name'][$i] ?? '');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset($allowed[$mime])) {
            json_response(422, ['ok' => false, 'message' => 'Uploads must be JPG, PNG, or PDF files.']);
        }
        if (!is_dir($leadUploadDir)) mkdir($leadUploadDir, 0775, true);
        $original = pathinfo((string) $_FILES['photos']['name'][$i], PATHINFO_FILENAME);
        $safeBase = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $original), '-');
        if ($safeBase === '') $safeBase = 'project-file';
        $filename = ($i + 1) . '-' . strtolower($safeBase) . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
        $destination = $leadUploadDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $destination)) {
            json_response(500, ['ok' => false, 'message' => 'A project file could not be saved. Please try again.']);
        }
        $savedAbsolute[] = $destination;
        $savedRelative[] = $leadId . '/' . $filename;
    }
}

$startWindow = trim((string) ($_POST['start_window'] ?? ''));
$budget = trim((string) ($_POST['budget'] ?? ''));
$decisionMaker = trim((string) ($_POST['decision_maker'] ?? ''));
$routeTag = $mode === 'estimate' ? 'estimate_first' : 'consult_first';
if ($budget === 'Not sure' || in_array($decisionMaker, ['No', 'Not sure'], true) || $startWindow === 'Planning 6+ months') {
    $routeTag = 'consult_first';
}

$lead = [
    'lead_id' => $leadId,
    'created_at' => date(DATE_ATOM),
    'mode' => $mode,
    'route_tag' => $routeTag,
    'full_name' => trim((string) $_POST['full_name']),
    'email' => $email,
    'phone' => trim((string) $_POST['phone']),
    'city_zip' => trim((string) $_POST['city_zip']),
    'address' => trim((string) ($_POST['address'] ?? '')),
    'project_type' => trim((string) $_POST['project_type']),
    'start_window' => $startWindow,
    'budget' => $budget,
    'decision_maker' => $decisionMaker,
    'source' => trim((string) ($_POST['source'] ?? '')),
    'notes' => trim((string) ($_POST['notes'] ?? '')),
    'uploads' => $savedRelative,
    'page_url' => trim((string) ($_POST['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''))),
    'ip_hash' => hash('sha256', $ip),
    'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300),
];

file_put_contents(UPLOAD_DIR . '/leads.jsonl', json_encode($lead, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
file_put_contents($rateFile, (string) time(), LOCK_EX);

$clickUpTaskId = '';
$clickUpUrl = '';
$clickUpError = '';
if (CLICKUP_ENABLED) {
    try {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP cURL extension is not enabled.');
        }
        $clickUp = new ClickUpClient(CLICKUP_API_TOKEN, CLICKUP_LIST_ID);
        if (!$clickUp->isConfigured()) {
            throw new RuntimeException('ClickUp token or List ID is not configured.');
        }
        $task = $clickUp->createLeadTask($lead);
        $clickUpTaskId = $task['id'];
        $clickUpUrl = $task['url'];
        log_event('clickup_success', [
            'lead_id' => $leadId,
            'task_id' => $clickUpTaskId,
            'url' => $clickUpUrl,
            'warnings' => $task['warnings'] ?? [],
        ]);
        if ($savedAbsolute !== []) {
            try {
                $clickUp->attachFiles($clickUpTaskId, $savedAbsolute);
                log_event('clickup_attachments_success', ['lead_id' => $leadId, 'task_id' => $clickUpTaskId, 'count' => count($savedAbsolute)]);
            } catch (Throwable $attachmentError) {
                log_event('clickup_attachments_error', ['lead_id' => $leadId, 'task_id' => $clickUpTaskId, 'error' => $attachmentError->getMessage()]);
            }
        }
    } catch (Throwable $e) {
        $clickUpError = $e->getMessage();
        log_event('clickup_error', ['lead_id' => $leadId, 'error' => $clickUpError]);
    }
}

$emailErrors = [];
$mailer = new SmtpMailer();
try {
    $adminHtml = render_template(__DIR__ . '/../includes/email-templates/admin-lead.php', ['lead' => $lead, 'clickUpUrl' => $clickUpUrl]);
    $attachments = [];
    $totalAttachmentBytes = 0;
    foreach ($savedAbsolute as $path) {
        $size = filesize($path) ?: 0;
        if (($totalAttachmentBytes + $size) > 12 * 1024 * 1024) break;
        $attachments[] = [
            'path' => $path,
            'name' => basename($path),
            'mime' => mime_content_type($path) ?: 'application/octet-stream',
        ];
        $totalAttachmentBytes += $size;
    }
    $mailer->send(
        MAIL_TO_ADDRESS,
        MAIL_TO_NAME,
        ($mode === 'estimate' ? 'Estimate Request' : 'Consult Request') . ' - ' . $lead['full_name'],
        $adminHtml,
        text_summary($lead, $clickUpUrl),
        $lead['email'],
        $lead['full_name'],
        $attachments
    );
    log_event('admin_email_success', ['lead_id' => $leadId, 'to' => MAIL_TO_ADDRESS]);
} catch (Throwable $e) {
    $emailErrors[] = 'admin: ' . $e->getMessage();
    log_event('admin_email_error', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
}

try {
    $customerHtml = render_template(__DIR__ . '/../includes/email-templates/customer-confirmation.php', ['lead' => $lead]);
    $customerText = "Hi {$lead['full_name']},\n\nWe received your " . ($mode === 'estimate' ? 'estimate review' : 'consultation') . " request for {$lead['project_type']}. Our team will review the details and contact you about next steps.\n\nCeiling 2 Cellar Remodeling\n" . BUSINESS_PHONE;
    $mailer->send(
        $lead['email'],
        $lead['full_name'],
        'We received your Ceiling 2 Cellar request',
        $customerHtml,
        $customerText,
        MAIL_REPLY_TO,
        SITE_NAME
    );
    log_event('customer_email_success', ['lead_id' => $leadId, 'to' => $lead['email']]);
} catch (Throwable $e) {
    $emailErrors[] = 'customer: ' . $e->getMessage();
    log_event('customer_email_error', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
}

// A lead is never discarded. It is always saved locally first. In production,
// report integration failures so the visitor can retry and the team can inspect logs.
$integrationFailed = (CLICKUP_ENABLED && $clickUpTaskId === '') || (SMTP_ENABLED && count($emailErrors) === 2);
if ($integrationFailed && APP_ENV === 'production') {
    json_response(503, [
        'ok' => false,
        'message' => 'Your details were saved, but delivery is temporarily delayed. Please call ' . BUSINESS_PHONE . ' or try again in a few minutes.',
        'lead_id' => $leadId,
    ]);
}

json_response(200, [
    'ok' => true,
    'lead_id' => $leadId,
    'redirect' => url($mode === 'estimate' ? 'thanks-estimate.php' : 'thanks-consult.php'),
    'debug' => DEBUG_MODE ? [
        'clickup_task_id' => $clickUpTaskId,
        'clickup_error' => $clickUpError,
        'email_errors' => $emailErrors,
    ] : null,
]);
