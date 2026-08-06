<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

final class SmtpMailer
{
    /** @param array<int,array{path:string,name:string,mime:string}> $attachments */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text,
        string $replyToEmail = '',
        string $replyToName = '',
        array $attachments = []
    ): void {
        if (!$this->isConfigured()) {
            throw new RuntimeException('SMTP credentials or recipient address are not configured.');
        }

        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Composer vendor/autoload.php was not found in the website root.');
        }
        require_once $autoload;

        $smtpDebug = '';
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->Port = SMTP_PORT;
            $mail->Timeout = 30;
            $mail->Timelimit = 30;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->SMTPAutoTLS = true;
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = static function (string $message, int $level) use (&$smtpDebug): void {
                $smtpDebug .= '[' . $level . '] ' . trim($message) . "\n";
            };

            $encryption = strtolower((string) SMTP_ENCRYPTION);
            if ($encryption === 'ssl' || $encryption === 'smtps') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
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
                if (is_file($attachment['path'])) {
                    $mail->addAttachment($attachment['path'], $attachment['name']);
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
        } catch (MailException $e) {
            $debug = trim($smtpDebug);
            throw new RuntimeException(
                'SMTP delivery failed: ' . $e->getMessage() . ($debug !== '' ? ' | SMTP trace: ' . $debug : ''),
                0,
                $e
            );
        }
    }

    public function isConfigured(): bool
    {
        return SMTP_ENABLED
            && SMTP_HOST !== ''
            && SMTP_USERNAME !== ''
            && SMTP_PASSWORD !== ''
            && filter_var(MAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL)
            && !str_contains(SMTP_PASSWORD, 'REPLACE_WITH');
    }
}
