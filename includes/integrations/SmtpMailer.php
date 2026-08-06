<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

final class SmtpMailer
{
    /** @param array<int,array{path:string,name:string,mime:string}> $attachments */
    public function send(string $toEmail, string $toName, string $subject, string $html, string $text, string $replyToEmail = '', string $replyToName = '', array $attachments = []): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('SMTP is not fully configured. Check SMTP username, password, from address and recipient.');
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid destination email address: ' . $toEmail);
        }

        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) throw new RuntimeException('vendor/autoload.php is missing from the website root.');
        require_once $autoload;

        $attempts = [[SMTP_HOST, SMTP_PORT, strtolower((string)SMTP_ENCRYPTION)]];
        // Hostinger commonly supports both combinations. Retry only when the
        // configured 465/SSL connection itself fails.
        if ((int)SMTP_PORT === 465 && strtolower((string)SMTP_ENCRYPTION) === 'ssl') {
            $attempts[] = [SMTP_HOST, 587, 'tls'];
        }

        $errors = [];
        foreach ($attempts as [$host, $port, $encryption]) {
            try {
                $this->sendAttempt($host, (int)$port, $encryption, $toEmail, $toName, $subject, $html, $text, $replyToEmail, $replyToName, $attachments);
                return;
            } catch (Throwable $e) {
                $errors[] = $host . ':' . $port . '/' . $encryption . ' - ' . $e->getMessage();
            }
        }
        throw new RuntimeException('SMTP delivery failed. ' . implode(' | ', $errors));
    }

    private function sendAttempt(string $host, int $port, string $encryption, string $toEmail, string $toName, string $subject, string $html, string $text, string $replyToEmail, string $replyToName, array $attachments): void
    {
        $trace = '';
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->Port = $port;
            $mail->Timeout = 25;
            $mail->Timelimit = 25;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = static function (string $message, int $level) use (&$trace): void {
                $clean = preg_replace('/(AUTH|PASS|Password:).*$/i', '$1 [hidden]', trim($message));
                $trace .= '[' . $level . '] ' . $clean . "\n";
            };

            if ($encryption === 'ssl' || $encryption === 'smtps') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, $replyToName);
            }
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && is_file($attachment['path'])) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
                }
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
        } catch (MailException $e) {
            throw new RuntimeException($e->getMessage() . ($trace !== '' ? ' | Trace: ' . trim($trace) : ''), 0, $e);
        }
    }

    public function isConfigured(): bool
    {
        return SMTP_ENABLED && SMTP_HOST !== '' && SMTP_USERNAME !== '' && SMTP_PASSWORD !== ''
            && filter_var(MAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL)
            && !str_contains(SMTP_PASSWORD, 'REPLACE_WITH');
    }
}
