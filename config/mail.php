<?php

/**
 * VendorBridge — Mail Configuration
 *
 * Centralised SMTP settings consumed by services/mailService.php.
 * Never commit real credentials. Use environment variables in production.
 */

return [

    // ── SMTP Server ───────────────────────────────────────────────────────────
    'host'       => $_ENV['MAIL_HOST']       ?? 'smtp.gmail.com',
    'port'       => (int) ($_ENV['MAIL_PORT'] ?? 587),          // 587 = TLS | 465 = SSL
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,

    // ── Credentials ───────────────────────────────────────────────────────────
    'username'   => $_ENV['MAIL_USERNAME']   ?? 'nidhish1132007@gmail.com',
    'password'   => $_ENV['MAIL_PASSWORD']   ?? 'witm dgpd uhsj lfnw',

    // ── Sender Identity ───────────────────────────────────────────────────────
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'no-reply@vendorbridge.com',
    'from_name'  => $_ENV['MAIL_FROM_NAME']  ?? 'VendorBridge',

    // ── Behaviour ─────────────────────────────────────────────────────────────
    'debug'      => (int) ($_ENV['MAIL_DEBUG'] ?? 0),
    // 0 = off | 1 = client messages | 2 = client + server (dev only)
];
