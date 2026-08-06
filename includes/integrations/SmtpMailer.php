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
            throw new RuntimeException('SMTP credentials are not configured.');
        }

        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Composer vendor/autoload.php was not found in the website root.');
        }
        require_once $autoload;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->Port = SMTP_PORT;
            $mail->Timeout = 20;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->SMTPAutoTLS = true;

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
            if ($replyToEmail !== '') {
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
            throw new RuntimeException('SMTP delivery failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function isConfigured(): bool
    {
        return SMTP_ENABLED
            && SMTP_HOST !== ''
            && SMTP_USERNAME !== ''
            && SMTP_PASSWORD !== ''
            && MAIL_FROM_ADDRESS !== ''
            && !str_contains(SMTP_PASSWORD, 'REPLACE_WITH');
    }
}
