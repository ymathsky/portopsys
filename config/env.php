<?php
/**
 * Environment Configuration
 * 
 * IMPORTANT: Update these values for your hosting environment.
 * Do NOT commit this file to version control (add it to .gitignore).
 */

// ── Database Settings ─────────────────────────────────────────────────────
// Local (XAMPP): host=localhost, user=root, pass='', name=queue_system
// Hosting:       use the DB details from your hosting control panel (cPanel etc.)
define('DB_HOST',    'localhost');
define('DB_USER',    'root');       // ← change to your hosting DB username
define('DB_PASS',    '');           // ← change to your hosting DB password
define('DB_NAME',    'queue_system'); // ← change to your hosting DB name
define('DB_CHARSET', 'utf8mb4');

// ── Application Base URL ──────────────────────────────────────────────────
// Leave blank ('') to auto-detect from the current domain.
// Set explicitly if auto-detection is wrong, e.g.: 'https://portopsys.com'
define('APP_BASE_URL', '');

// ── Email / SMTP Settings ─────────────────────────────────────────────────
// Set EMAIL_ENABLED to true and fill in your cPanel email credentials.
// SMTP Host:  usually mail.yourdomain.com  (check cPanel → Email Accounts → Connect Devices)
// SMTP Port:  587 (STARTTLS) or 465 (SSL)
// SMTP User:  the full email address you created in cPanel (e.g. noreply@portopsys.com)
// SMTP Pass:  the password you set for that email account in cPanel
define('EMAIL_ENABLED',   true);
define('SMTP_HOST',       'mail.portopsys.com');
define('SMTP_PORT',       465);
define('SMTP_SECURE',     'ssl');
define('SMTP_USER',       'support@portopsys.com');
define('SMTP_PASS',       'Ym@thsky12101992');
define('SMTP_FROM_NAME',  'Port Queuing Management System');
