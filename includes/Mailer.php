<?php
/**
 * Mailer — lightweight SMTP mailer (no external libraries required)
 *
 * Uses PHP stream_socket_client with STARTTLS/SSL support.
 * Credentials are read from config constants defined in env.php / config.php.
 *
 * Usage:
 *   Mailer::sendTokenConfirmation($tokenDetails);
 */

class Mailer
{
    // ── SMTP conversation helpers ─────────────────────────────────────────

    /**
     * Open an SMTP socket and return it, or throw on failure.
     */
    private static function smtpConnect(): mixed
    {
        $host   = SMTP_HOST;
        $port   = (int) SMTP_PORT;
        $secure = strtolower(SMTP_SECURE); // 'tls' or 'ssl'

        $errno  = 0;
        $errstr = '';
        $timeout = 15;

        if ($secure === 'ssl') {
            $sockAddr = "ssl://{$host}:{$port}";
        } else {
            $sockAddr = "tcp://{$host}:{$port}";
        }

        $sock = stream_socket_client($sockAddr, $errno, $errstr, $timeout);
        if (!$sock) {
            throw new \RuntimeException("SMTP connect failed ({$errno}): {$errstr}");
        }

        stream_set_timeout($sock, $timeout);
        return $sock;
    }

    /**
     * Read one SMTP response line and check its code.
     */
    private static function smtpRead($sock, int $expect): string
    {
        $data = '';
        while (!feof($sock)) {
            $line = fgets($sock, 515);
            if ($line === false) break;
            $data .= $line;
            // Multi-line responses end when the 4th char is a space
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $code = (int) substr($data, 0, 3);
        if ($code !== $expect) {
            throw new \RuntimeException("SMTP expected {$expect}, got: " . trim($data));
        }
        return $data;
    }

    /**
     * Send a command and expect a response code.
     */
    private static function smtpCmd($sock, string $cmd, int $expect): string
    {
        fwrite($sock, $cmd . "\r\n");
        return self::smtpRead($sock, $expect);
    }

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Send a queue token confirmation email.
     *
     * @param array $token  Row from getToken() + formatted fields
     * @return bool         true on success, false if email disabled / no recipient
     */
    public static function sendTokenConfirmation(array $token): bool
    {
        if (!EMAIL_ENABLED) return false;

        $to = trim($token['customer_email'] ?? '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

        $name        = htmlspecialchars($token['customer_name']   ?? 'Valued Customer', ENT_QUOTES);
        $tokenNum    = htmlspecialchars($token['token_number']     ?? '—');
        $service     = htmlspecialchars($token['service_name']     ?? '—');
        $priority    = ucwords(str_replace('_', ' ', $token['priority_type'] ?? 'regular'));
        $queuePos    = (int)($token['queue_position']              ?? 0);
        $waitMin     = (int)($token['estimated_wait_time']         ?? 0);
        $issuedAt    = $token['issued_at_formatted']               ?? date('h:i A');
        $issuedDate  = $token['issued_date_formatted']             ?? date('M d, Y');
        $statusUrl   = rtrim(defined('BASE_URL') ? BASE_URL : '', '/')
                       . '/customer/token-status.php?token_number=' . urlencode($token['token_number'] ?? '');

        // QR code via free API
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($statusUrl);

        // ── HTML body ─────────────────────────────────────────────────────
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Queue Token — {$tokenNum}</title>
<style>
  body{margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Arial,sans-serif;color:#1f2937}
  .wrap{max-width:540px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)}
  .header{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);padding:32px 24px;text-align:center;color:#fff}
  .header h1{margin:0 0 4px;font-size:22px;font-weight:700;letter-spacing:.5px}
  .header p{margin:0;font-size:13px;opacity:.85}
  .token-box{background:#eff6ff;border:2px solid #3b82f6;border-radius:12px;margin:28px 24px 0;padding:20px;text-align:center}
  .token-box .label{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#3b82f6;margin-bottom:6px}
  .token-box .number{font-size:48px;font-weight:800;color:#1e40af;letter-spacing:4px;line-height:1}
  .body{padding:24px}
  .row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px}
  .row:last-child{border-bottom:none}
  .row .key{color:#6b7280;font-weight:500}
  .row .val{color:#111827;font-weight:600;text-align:right}
  .badge{display:inline-block;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600}
  .badge-blue{background:#dbeafe;color:#1d4ed8}
  .badge-green{background:#d1fae5;color:#065f46}
  .queue-highlight{background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin:16px 0;text-align:center;font-size:14px;color:#92400e}
  .queue-highlight strong{font-size:22px;color:#b45309}
  .cta{display:block;margin:20px 0;text-align:center}
  .cta a{display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:.3px}
  .qr-section{text-align:center;padding:8px 24px 4px}
  .qr-section p{font-size:12px;color:#6b7280;margin:8px 0 0}
  .footer{background:#f9fafb;padding:18px 24px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb}
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>Port Queuing Management System</h1>
    <p>Queue Token Confirmation</p>
  </div>

  <div class="token-box">
    <div class="label">Your Token Number</div>
    <div class="number">{$tokenNum}</div>
  </div>

  <div class="body">
    <p style="margin:0 0 16px;font-size:15px">Hello, <strong>{$name}</strong> 👋<br>
    Your queue token has been successfully issued. Please keep this email for reference.</p>

    <div class="row"><span class="key">Service</span>   <span class="val">{$service}</span></div>
    <div class="row"><span class="key">Priority</span>  <span class="val"><span class="badge badge-blue">{$priority}</span></span></div>
    <div class="row"><span class="key">Issued On</span> <span class="val">{$issuedDate} at {$issuedAt}</span></div>

    <div class="queue-highlight">
      You are <strong>#{$queuePos}</strong> in queue &nbsp;·&nbsp; Est. wait: <strong>~{$waitMin} min</strong>
    </div>

    <p style="font-size:13px;color:#6b7280;margin:0 0 12px">
      Track your position in real-time by clicking the button below or scanning the QR code.
    </p>

    <div class="cta"><a href="{$statusUrl}">📍 Track My Token Status</a></div>
  </div>

  <div class="qr-section">
    <img src="{$qrUrl}" alt="QR Code" width="130" height="130" style="border-radius:8px;border:1px solid #e5e7eb">
    <p>Scan to view token status</p>
  </div>

  <div class="footer">
    This email was sent by the Port Queuing Management System.<br>
    If you did not request this token, please disregard this email.
  </div>

</div>
</body>
</html>
HTML;

        // ── Plain-text fallback ───────────────────────────────────────────
        $text = "PORT QUEUING MANAGEMENT SYSTEM — Token Confirmation\n"
              . str_repeat('=', 50) . "\n\n"
              . "Hello {$name},\n\n"
              . "Your queue token has been issued:\n\n"
              . "  Token Number : {$tokenNum}\n"
              . "  Service      : {$service}\n"
              . "  Priority     : {$priority}\n"
              . "  Queue Pos.   : #{$queuePos}\n"
              . "  Est. Wait    : ~{$waitMin} min\n"
              . "  Issued At    : {$issuedDate} {$issuedAt}\n\n"
              . "Track your status: {$statusUrl}\n\n"
              . "If you did not request this token, please ignore this message.\n";

        $subject = "Your Queue Token: {$tokenNum} — Port Queuing Management System";

        try {
            self::sendMail($to, $name, $subject, $html, $text);
            return true;
        } catch (\Throwable $e) {
            // Log but never crash the token flow
            error_log('[Mailer] sendTokenConfirmation failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Core SMTP send ────────────────────────────────────────────────────

    /**
     * Send a multipart HTML/plain-text email via SMTP.
     *
     * @throws \RuntimeException on SMTP protocol errors
     */
    public static function sendMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): void {
        $fromEmail = SMTP_USER;
        $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : APP_NAME;

        $boundary = 'PQMS_' . bin2hex(random_bytes(8));

        // ── Build RFC 2822 message ─────────────────────────────────────
        $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject)   . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName)  . '?=';
        $encodedToName   = '=?UTF-8?B?' . base64_encode($toName)    . '?=';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "From: {$encodedFromName} <{$fromEmail}>\r\n";
        $headers .= "To: {$encodedToName} <{$toEmail}>\r\n";
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "X-Mailer: PQMS-Mailer/1.0\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textBody)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $rawMessage = $headers . "\r\n" . $body;
        // Dot-stuff: lines starting with . must be doubled
        $rawMessage = str_replace("\r\n.", "\r\n..", $rawMessage);

        // ── Open socket ───────────────────────────────────────────────
        $sock = self::smtpConnect();

        try {
            self::smtpRead($sock, 220);                          // greeting
            self::smtpCmd($sock, "EHLO " . gethostname(), 250); // EHLO

            // STARTTLS if tls mode
            if (strtolower(SMTP_SECURE) === 'tls') {
                self::smtpCmd($sock, "STARTTLS", 220);
                stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::smtpCmd($sock, "EHLO " . gethostname(), 250); // re-EHLO after TLS
            }

            // AUTH LOGIN
            self::smtpCmd($sock, "AUTH LOGIN", 334);
            self::smtpCmd($sock, base64_encode(SMTP_USER), 334);
            self::smtpCmd($sock, base64_encode(SMTP_PASS), 235);

            // Envelope
            self::smtpCmd($sock, "MAIL FROM:<{$fromEmail}>", 250);
            self::smtpCmd($sock, "RCPT TO:<{$toEmail}>",    250);
            self::smtpCmd($sock, "DATA",                     354);

            fwrite($sock, $rawMessage . "\r\n.\r\n");
            self::smtpRead($sock, 250);

            self::smtpCmd($sock, "QUIT", 221);
        } finally {
            fclose($sock);
        }
    }
}
