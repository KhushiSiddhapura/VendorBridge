<?php

/**
 * VendorBridge — Mail Service
 *
 * Reusable email functions powered by PHPMailer.
 * Mirrors the toaster architecture: one include, one function call.
 *
 * Rules (same contract as toaster):
 *   - No DB logic
 *   - No session writes
 *   - No redirects
 *   - Returns true on success, false on failure
 *
 * Usage:
 *   require_once '../services/mailService.php';
 *   $sent = sendCredentialsMail($email, $firstname, $username, $tempPassword);
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ── Internal bootstrap ────────────────────────────────────────────────────────

/**
 * Creates and returns a pre-configured PHPMailer instance.
 * Not intended for direct use outside this file.
 *
 * @return PHPMailer|false  Returns false if config is missing.
 */
function _getMailer(): PHPMailer|false {

    $config = require __DIR__ . '/../config/mail.php';

    if (empty($config['username']) || empty($config['password'])) {
        error_log('[mailService] SMTP credentials are not configured.');
        return false;
    }

    $mail = new PHPMailer(true); // true = throw exceptions

    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port       = $config['port'];
    $mail->SMTPDebug  = $config['debug'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

/**
 * Shared HTML email wrapper — consistent header/footer branding.
 *
 * @param  string $body  Inner HTML content of the email.
 * @return string        Full HTML email string.
 */
function _wrapEmailTemplate(string $body): string {

    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body        { margin: 0; padding: 0; background: #f4f6f9; font-family: Arial, sans-serif; }
            .wrapper    { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
            .header     { background: #1a1a2e; padding: 28px 32px; }
            .header h1  { margin: 0; color: #ffffff; font-size: 22px; letter-spacing: 0.5px; }
            .header h1 span { color: #7c6af7; }
            .body       { padding: 32px; color: #333333; font-size: 15px; line-height: 1.6; }
            .body h2    { margin-top: 0; color: #1a1a2e; }
            .cred-box   { background: #f0eeff; border-left: 4px solid #7c6af7; padding: 16px 20px; border-radius: 4px; margin: 24px 0; }
            .cred-box p { margin: 6px 0; font-size: 14px; color: #444; }
            .cred-box strong { color: #1a1a2e; }
            .warning    { font-size: 13px; color: #888; margin-top: 24px; }
            .footer     { background: #f4f6f9; padding: 20px 32px; font-size: 12px; color: #aaa; text-align: center; border-top: 1px solid #ebebeb; }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="header">
                <h1><span>Vendor</span>Bridge</h1>
            </div>
            <div class="body">
                {$body}
            </div>
            <div class="footer">
                &copy; VendorBridge &mdash; Vendor Management System &bull; Do not reply to this email.
            </div>
        </div>
    </body>
    </html>
    HTML;
}


// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Sends login credentials to a newly created user.
 * Called by auth/register.php after a successful INSERT.
 *
 * @param  string $email             Recipient email address.
 * @param  string $firstname         Recipient's first name (used in greeting).
 * @param  string $username          Auto-generated username.
 * @param  string $temporaryPassword Plain-text temporary password (before hashing).
 * @return bool                      true on success, false on failure.
 */
function sendCredentialsMail(
    string $email,
    string $firstname,
    string $username,
    string $temporaryPassword
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = 'Your VendorBridge Account Credentials';

        $safeFirst    = htmlspecialchars($firstname, ENT_QUOTES);
        $safeUser     = htmlspecialchars($username, ENT_QUOTES);
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES);

        $body = <<<HTML
        <h2>Welcome to VendorBridge, {$safeFirst}!</h2>
        <p>Your account has been created. Use the credentials below to sign in.</p>

        <div class="cred-box">
            <p><strong>Username:</strong> {$safeUser}</p>
            <p><strong>Temporary Password:</strong> {$safePassword}</p>
        </div>

        <p>Please log in and change your password immediately.</p>

        <p class="warning">
            If you did not request this account, please contact your system administrator.
        </p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Welcome {$safeFirst}! Username: {$safeUser} | Temporary Password: {$safePassword}. Please log in and change your password immediately.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendCredentialsMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Sends a password reset link/token to the user.
 *
 * @param  string $email      Recipient email address.
 * @param  string $firstname  Recipient's first name.
 * @param  string $resetLink  Full URL of the reset page (include token).
 * @return bool
 */
function sendPasswordResetMail(
    string $email,
    string $firstname,
    string $resetLink
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = 'Reset Your VendorBridge Password';

        $safeFirst = htmlspecialchars($firstname, ENT_QUOTES);
        $safeLink  = htmlspecialchars($resetLink, ENT_QUOTES);

        $body = <<<HTML
        <h2>Password Reset Request</h2>
        <p>Hi {$safeFirst}, we received a request to reset your password.</p>
        <p>
            <a href="{$safeLink}"
               style="display:inline-block;padding:12px 24px;background:#7c6af7;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;">
                Reset Password
            </a>
        </p>
        <p class="warning">This link expires in 1 hour. If you did not request a reset, ignore this email.</p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Hi {$safeFirst}, reset your password here: {$safeLink}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendPasswordResetMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notifies a vendor that their account has been approved.
 *
 * @param  string $email      Recipient email address.
 * @param  string $firstname  Recipient's first name.
 * @return bool
 */
function sendVendorApprovedMail(
    string $email,
    string $firstname
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = 'Your VendorBridge Vendor Account Has Been Approved';

        $safeFirst = htmlspecialchars($firstname, ENT_QUOTES);

        $body = <<<HTML
        <h2>You're Approved, {$safeFirst}!</h2>
        <p>Your vendor account on VendorBridge has been reviewed and <strong>approved</strong>.</p>
        <p>You now have full access to the vendor portal. Log in to get started.</p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Hi {$safeFirst}, your VendorBridge vendor account has been approved. Log in to get started.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendVendorApprovedMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notifies a vendor that their account has been rejected.
 *
 * @param  string $email      Recipient email address.
 * @param  string $firstname  Recipient's first name.
 * @param  string $reason     Optional rejection reason shown to the vendor.
 * @return bool
 */
function sendVendorRejectedMail(
    string $email,
    string $firstname,
    string $reason = ''
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = 'VendorBridge — Vendor Application Update';

        $safeFirst  = htmlspecialchars($firstname, ENT_QUOTES);
        $reasonHtml = $reason
            ? '<p><strong>Reason:</strong> ' . htmlspecialchars($reason, ENT_QUOTES) . '</p>'
            : '';

        $body = <<<HTML
        <h2>Application Status Update</h2>
        <p>Hi {$safeFirst}, after reviewing your vendor application, we are unable to approve it at this time.</p>
        {$reasonHtml}
        <p>Please contact your system administrator if you believe this is an error.</p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Hi {$safeFirst}, your VendorBridge vendor application was not approved. " . ($reason ? "Reason: {$reason}" : '');

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendVendorRejectedMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Confirms to a user that their password has been changed successfully.
 *
 * @param  string $email      Recipient email address.
 * @param  string $firstname  Recipient's first name.
 * @return bool
 */
function sendPasswordChangedMail(
    string $email,
    string $firstname
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = 'Your VendorBridge Password Was Changed';

        $safeFirst = htmlspecialchars($firstname, ENT_QUOTES);

        $body = <<<HTML
        <h2>Password Changed</h2>
        <p>Hi {$safeFirst}, your VendorBridge account password was successfully changed.</p>
        <p class="warning">
            If you did not make this change, contact your system administrator immediately.
        </p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Hi {$safeFirst}, your VendorBridge password was changed. If this wasn't you, contact your administrator immediately.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendPasswordChangedMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}


/**
 * Sends RFQ notification to an assigned vendor.
 *
 * @param  string $email       Vendor's email address.
 * @param  string $firstname   Vendor's first name.
 * @param  string $rfqNumber   Generated RFQ number.
 * @param  string $title       RFQ title.
 * @param  string $category    RFQ category.
 * @param  string $deadline    Submission deadline.
 * @param  string $description RFQ description.
 * @param  array  $items       Line item names.
 * @param  array  $quantities  Line item quantities.
 * @param  array  $units       Line item units.
 * @return bool
 */
function sendRFQMail(
    string $email,
    string $firstname,
    string $rfqNumber,
    string $title,
    string $category,
    string $deadline,
    string $description,
    array  $items,
    array  $quantities,
    array  $units
): bool {

    $mail = _getMailer();
    if (!$mail) return false;

    try {

        $mail->addAddress($email);
        $mail->Subject = "New RFQ Assigned — {$rfqNumber}";

        $safeFirst    = htmlspecialchars($firstname,   ENT_QUOTES);
        $safeNumber   = htmlspecialchars($rfqNumber,   ENT_QUOTES);
        $safeTitle    = htmlspecialchars($title,       ENT_QUOTES);
        $safeCategory = htmlspecialchars($category,    ENT_QUOTES);
        $safeDeadline = htmlspecialchars($deadline,    ENT_QUOTES);
        $safeDesc     = $description
                          ? '<p><strong>Description:</strong> ' . htmlspecialchars($description, ENT_QUOTES) . '</p>'
                          : '';

        // Build line items table rows
        $itemRows = '';
        foreach ($items as $i => $itemName) {
            $safeItem = htmlspecialchars($itemName,          ENT_QUOTES);
            $safeQty  = htmlspecialchars($quantities[$i] ?? '', ENT_QUOTES);
            $safeUnit = htmlspecialchars($units[$i]      ?? '', ENT_QUOTES);
            $itemRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #ebebeb;'>{$safeItem}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #ebebeb;text-align:center;'>{$safeQty}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #ebebeb;text-align:center;'>{$safeUnit}</td>
            </tr>";
        }

        $body = <<<HTML
        <h2>New RFQ Assigned to You</h2>
        <p>Hi {$safeFirst}, a new Request for Quotation has been issued and assigned to you.</p>

        <div class="cred-box">
            <p><strong>RFQ Number:</strong> {$safeNumber}</p>
            <p><strong>Title:</strong> {$safeTitle}</p>
            <p><strong>Category:</strong> {$safeCategory}</p>
            <p><strong>Submission Deadline:</strong> {$safeDeadline}</p>
        </div>

        {$safeDesc}

        <h3 style="color:#1a1a2e;margin-top:28px;">Line Items</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f0eeff;">
                    <th style="padding:10px 12px;text-align:left;color:#1a1a2e;">Item</th>
                    <th style="padding:10px 12px;text-align:center;color:#1a1a2e;">Qty</th>
                    <th style="padding:10px 12px;text-align:center;color:#1a1a2e;">Unit</th>
                </tr>
            </thead>
            <tbody>
                {$itemRows}
            </tbody>
        </table>

        <p style="margin-top:28px;">Please log in to VendorBridge to submit your quotation before the deadline.</p>

        <p class="warning">
            This RFQ was assigned to you by your procurement team. Do not reply to this email.
        </p>
        HTML;

        $mail->Body    = _wrapEmailTemplate($body);
        $mail->AltBody = "Hi {$safeFirst}, you have been assigned RFQ {$safeNumber} - {$safeTitle}. Deadline: {$safeDeadline}. Please log in to VendorBridge to submit your quotation.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[mailService] sendRFQMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}