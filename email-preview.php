<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Email Preview — Token Confirmation</title>
<style>
  /* Page chrome */
  *{box-sizing:border-box}
  body{margin:0;padding:0;background:#e5e7eb;font-family:'Segoe UI',Arial,sans-serif}
  .preview-bar{background:#1e293b;color:#f8fafc;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.3)}
  .preview-bar h2{margin:0;font-size:14px;font-weight:600;letter-spacing:.3px;display:flex;align-items:center;gap:8px}
  .preview-bar h2 .dot{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .meta{font-size:12px;color:#94a3b8;display:flex;gap:16px;flex-wrap:wrap}
  .meta span{display:flex;align-items:center;gap:4px}
  .meta .label{color:#cbd5e1}
  .page-body{padding:32px 16px 64px}

  /* ── Email template ──────────────────────────────────────── */
  body{background:#e5e7eb}
  .wrap{max-width:540px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.12)}

  /* Header */
  .em-header{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);padding:36px 28px 28px;text-align:center;color:#fff}
  .em-header .logo{font-size:28px;margin-bottom:8px}
  .em-header h1{margin:0 0 4px;font-size:20px;font-weight:700;letter-spacing:.4px;line-height:1.3}
  .em-header p{margin:0;font-size:13px;opacity:.80;letter-spacing:.3px;text-transform:uppercase}

  /* Token box */
  .token-box{background:#eff6ff;border:2px solid #3b82f6;border-radius:14px;margin:28px 24px 0;padding:22px 16px;text-align:center}
  .token-box .tb-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#3b82f6;margin-bottom:8px}
  .token-box .tb-number{font-size:54px;font-weight:800;color:#1e40af;letter-spacing:6px;line-height:1}
  .token-box .tb-sub{margin-top:6px;font-size:12px;color:#6b7280}

  /* Body */
  .em-body{padding:24px 24px 8px}
  .em-body .greeting{font-size:15px;color:#1f2937;margin:0 0 20px;line-height:1.6}

  .info-table{width:100%;border-collapse:collapse;margin-bottom:16px}
  .info-table tr td{padding:10px 0;font-size:14px;border-bottom:1px solid #f3f4f6}
  .info-table tr:last-child td{border-bottom:none}
  .info-table .td-key{color:#6b7280;font-weight:500;width:40%}
  .info-table .td-val{color:#111827;font-weight:600;text-align:right}

  .badge{display:inline-block;padding:3px 12px;border-radius:99px;font-size:12px;font-weight:600}
  .badge-blue{background:#dbeafe;color:#1d4ed8}
  .badge-green{background:#d1fae5;color:#065f46}
  .badge-amber{background:#fef3c7;color:#92400e}
  .badge-red{background:#fee2e2;color:#991b1b}

  /* Queue highlight */
  .queue-highlight{background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:16px;margin:20px 0;text-align:center}
  .queue-highlight .qh-title{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#92400e;margin-bottom:6px}
  .queue-highlight .qh-main{font-size:15px;color:#78350f;display:flex;justify-content:center;align-items:center;gap:24px;flex-wrap:wrap}
  .queue-highlight .qh-stat{text-align:center}
  .queue-highlight .qh-stat .num{font-size:32px;font-weight:800;color:#b45309;display:block;line-height:1}
  .queue-highlight .qh-stat .lbl{font-size:12px;color:#92400e;margin-top:2px}

  /* CTA */
  .cta-wrap{text-align:center;margin:24px 0 16px}
  .cta-btn{display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff !important;text-decoration:none;padding:14px 36px;border-radius:12px;font-size:15px;font-weight:600;letter-spacing:.3px;box-shadow:0 4px 14px rgba(59,130,246,.4)}

  /* Reminder box */
  .reminder{background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px 16px;margin:16px 0;font-size:13px;color:#166534}
  .reminder strong{display:block;margin-bottom:4px;font-size:13px}

  /* QR */
  .qr-section{text-align:center;padding:12px 24px 8px}
  .qr-section img{border-radius:10px;border:1px solid #e5e7eb}
  .qr-section p{font-size:12px;color:#6b7280;margin:8px 0 0}

  /* Divider */
  .divider{height:1px;background:#f3f4f6;margin:0 24px}

  /* Footer */
  .em-footer{background:#f9fafb;padding:20px 24px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;line-height:1.7}
  .em-footer a{color:#6b7280;text-decoration:underline}
</style>
</head>
<body>

<!-- Preview toolbar -->
<div class="preview-bar">
  <h2><span class="dot"></span> Email Preview — Token Confirmation</h2>
  <div class="meta">
    <span><span class="label">To:</span> juan.delacruz@example.com</span>
    <span><span class="label">From:</span> support@portopsys.com</span>
    <span><span class="label">Subject:</span> Your Queue Token: A-042 — Port Queuing Management System</span>
  </div>
</div>

<div class="page-body">
<?php
// ── Sample data (mirrors what generate-token.php passes) ──────────────────
$tokenNum   = 'A-042';
$name       = 'Juan dela Cruz';
$service    = 'Regular Passenger Boarding';
$priority   = 'Regular';
$queuePos   = 7;
$waitMin    = 35;
$issuedAt   = '09:14 AM';
$issuedDate = 'March 2, 2026';
$statusUrl  = 'http://portopsys.com/customer/token-status.php?token_number=A-042';
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($statusUrl);
?>

<div class="wrap">

  <!-- Header -->
  <div class="em-header">
    <div class="logo">⚓</div>
    <h1>Port Queuing Management System</h1>
    <p>Queue Token Confirmation</p>
  </div>

  <!-- Token number box -->
  <div class="token-box">
    <div class="tb-label">Your Token Number</div>
    <div class="tb-number"><?= $tokenNum ?></div>
    <div class="tb-sub">Issued <?= $issuedDate ?> &nbsp;·&nbsp; <?= $issuedAt ?></div>
  </div>

  <!-- Body -->
  <div class="em-body">
    <p class="greeting">
      Hello, <strong><?= $name ?></strong> 👋<br>
      Your queue token has been <strong>successfully issued</strong>. Please keep this email for reference and present your token number at the boarding counter.
    </p>

    <!-- Details table -->
    <table class="info-table">
      <tr>
        <td class="td-key">Service</td>
        <td class="td-val"><?= $service ?></td>
      </tr>
      <tr>
        <td class="td-key">Priority</td>
        <td class="td-val"><span class="badge badge-blue"><?= $priority ?></span></td>
      </tr>
      <tr>
        <td class="td-key">Issued On</td>
        <td class="td-val"><?= $issuedDate ?> at <?= $issuedAt ?></td>
      </tr>
      <tr>
        <td class="td-key">Token Status</td>
        <td class="td-val"><span class="badge badge-green">Active</span></td>
      </tr>
    </table>

    <!-- Queue position highlight -->
    <div class="queue-highlight">
      <div class="qh-title">Your Queue Position</div>
      <div class="qh-main">
        <div class="qh-stat">
          <span class="num">#<?= $queuePos ?></span>
          <span class="lbl">Position in Queue</span>
        </div>
        <div style="font-size:24px;color:#fbbf24">·</div>
        <div class="qh-stat">
          <span class="num">~<?= $waitMin ?></span>
          <span class="lbl">Minutes Est. Wait</span>
        </div>
      </div>
    </div>

    <!-- Reminder -->
    <div class="reminder">
      <strong>📌 What to do next:</strong>
      Please proceed to the terminal and wait for your token to be called. You can track your live queue position using the button below.
    </div>

    <p style="font-size:13px;color:#6b7280;margin:0 0 4px;text-align:center">
      Click below to track your token position in real-time:
    </p>

    <!-- CTA Button -->
    <div class="cta-wrap">
      <a href="<?= htmlspecialchars($statusUrl) ?>" class="cta-btn">📍 Track My Token Status</a>
    </div>
  </div>

  <div class="divider"></div>

  <!-- QR Code -->
  <div class="qr-section">
    <img src="<?= $qrUrl ?>" alt="QR Code" width="140" height="140">
    <p>Or scan this QR code with your phone camera</p>
  </div>

  <div class="divider"></div>

  <!-- Footer -->
  <div class="em-footer">
    <p style="margin:0 0 6px">
      This email was sent by <strong>Port Queuing Management System</strong><br>
      <a href="http://portopsys.com">portopsys.com</a> &nbsp;·&nbsp;
      <a href="mailto:support@portopsys.com">support@portopsys.com</a>
    </p>
    <p style="margin:0;font-size:11px">
      If you did not request this token, please disregard this email.
    </p>
  </div>

</div><!-- /.wrap -->
</div><!-- /.page-body -->
</body>
</html>
