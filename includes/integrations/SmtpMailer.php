<?php
declare(strict_types=1);

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

        $host = SMTP_ENCRYPTION === 'ssl' ? 'ssl://' . SMTP_HOST : SMTP_HOST;
        $socket = @stream_socket_client(
            $host . ':' . SMTP_PORT,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );
        if (!is_resource($socket)) {
            throw new RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($socket, 20);

        $this->expect($socket, [220]);
        $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $this->command($socket, 'EHLO ' . $hostname, [250]);

        if (SMTP_ENCRYPTION === 'tls') {
            $this->command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }
            $this->command($socket, 'EHLO ' . $hostname, [250]);
        }

        $this->command($socket, 'AUTH LOGIN', [334]);
        $this->command($socket, base64_encode(SMTP_USERNAME), [334], false);
        $this->command($socket, base64_encode(SMTP_PASSWORD), [235], false);
        $this->command($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>', [250]);
        $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        $this->command($socket, 'DATA', [354]);

        $message = $this->buildMessage(
            $toEmail,
            $toName,
            $subject,
            $html,
            $text,
            $replyToEmail,
            $replyToName,
            $attachments
        );
        fwrite($socket, $this->dotStuff($message) . "\r\n.\r\n");
        $this->expect($socket, [250]);
        $this->command($socket, 'QUIT', [221]);
        fclose($socket);
    }

    public function isConfigured(): bool
    {
        return SMTP_ENABLED
            && SMTP_HOST !== ''
            && SMTP_USERNAME !== ''
            && SMTP_PASSWORD !== ''
            && !str_contains(SMTP_HOST, 'your-email-provider')
            && !str_contains(SMTP_PASSWORD, 'REPLACE_WITH');
    }

    private function buildMessage(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text,
        string $replyToEmail,
        string $replyToName,
        array $attachments
    ): string {
        $mixedBoundary = 'mixed_' . bin2hex(random_bytes(12));
        $altBoundary = 'alt_' . bin2hex(random_bytes(12));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatAddress(MAIL_FROM_ADDRESS, MAIL_FROM_NAME),
            'To: ' . $this->formatAddress($toEmail, $toName),
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>',
            'MIME-Version: 1.0',
        ];
        if ($replyToEmail !== '') {
            $headers[] = 'Reply-To: ' . $this->formatAddress($replyToEmail, $replyToName);
        }

        if ($attachments !== []) {
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';
            $body = '--' . $mixedBoundary . "\r\n";
            $body .= 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . "\r\n\r\n";
            $body .= $this->alternativeParts($altBoundary, $text, $html);
            foreach ($attachments as $attachment) {
                if (!is_file($attachment['path'])) continue;
                $encoded = chunk_split(base64_encode((string) file_get_contents($attachment['path'])));
                $name = str_replace(['"', "\r", "\n"], '', $attachment['name']);
                $body .= '--' . $mixedBoundary . "\r\n";
                $body .= 'Content-Type: ' . $attachment['mime'] . '; name="' . $name . '"' . "\r\n";
                $body .= 'Content-Transfer-Encoding: base64' . "\r\n";
                $body .= 'Content-Disposition: attachment; filename="' . $name . '"' . "\r\n\r\n";
                $body .= $encoded . "\r\n";
            }
            $body .= '--' . $mixedBoundary . "--\r\n";
        } else {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
            $body = $this->alternativeParts($altBoundary, $text, $html);
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function alternativeParts(string $boundary, string $text, string $html): string
    {
        return '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($text) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($html) . "\r\n"
            . '--' . $boundary . "--\r\n";
    }

    private function command($socket, string $command, array $expected, bool $logSafe = true): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    private function expect($socket, array $expected): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }

    private function formatAddress(string $email, string $name): string
    {
        return $name !== '' ? $this->encodeHeader($name) . ' <' . $email . '>' : '<' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function dotStuff(string $message): string
    {
        return preg_replace('/(^|\r\n)\./', '$1..', $message) ?? $message;
    }
}
