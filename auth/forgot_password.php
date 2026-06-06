<?php
session_start();
require_once '../config/connection.php';

$step     = $_GET['step'] ?? 'email';
$error    = '';
$success  = '';

// ── Step 1: verify email & generate token ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email') {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $res   = mysqli_query($conn, "SELECT id, firstname FROM users WHERE email = '$email' LIMIT 1");

    if ($res && mysqli_num_rows($res) === 1) {
        $user  = mysqli_fetch_assoc($res);
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $uid   = $user['id'];

        // Store token in DB (reuse description-like column or use a simple tokens table approach)
        // We store it in a hidden session for simplicity (single-server, no-email required)
        $_SESSION['reset_token']  = $token;
        $_SESSION['reset_uid']    = $uid;
        $_SESSION['reset_expiry'] = $expiry;

        // Redirect to step 2
        header("Location: forgot_password.php?step=reset&t=" . urlencode($token));
        exit();
    } else {
        $error = 'No account found with that email address.';
    }
}

// ── Step 2: verify token & reset password ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_SESSION['reset_token']) || $token !== $_SESSION['reset_token']) {
        $error = 'Invalid or expired reset token. Please start over.';
    } else {
        $uid    = (int)$_SESSION['reset_uid'];
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $uid");
        unset($_SESSION['reset_token'], $_SESSION['reset_uid'], $_SESSION['reset_expiry']);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Password reset successfully! Please login.'];
        header('Location: ../login/login.php');
        exit();
    }
}

$token_get = urlencode($_GET['t'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Forgot Password</title>
    <link rel="stylesheet" href="../login/login.css">
    <link rel="stylesheet" href="../toaster/toaster.css">
    <script src="../toaster/toaster.js"></script>
    <style>
        .fp-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            margin: auto;
        }
        .fp-logo { font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem; color: #0f172a; }
        .fp-logo span { color: #2563eb; }
        .fp-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.4rem; }
        .fp-sub   { font-size: 0.88rem; color: #64748b; margin-bottom: 1.75rem; }
        .fp-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.1rem; }
        .fp-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .fp-input {
            padding: 0.8rem 1rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            font-family: inherit;
        }
        .fp-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .fp-btn {
            width: 100%; padding: 0.88rem;
            background: #2563eb; color: #fff;
            border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.15s;
            font-family: inherit;
        }
        .fp-btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .fp-error { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.88rem; margin-bottom: 1rem; }
        .fp-back  { display: block; text-align: center; margin-top: 1rem; color: #2563eb; font-size: 0.85rem; font-weight: 600; text-decoration: none; }
        .fp-back:hover { text-decoration: underline; }
        .vms-container { display: flex; min-height: 100vh; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 2rem; }
    </style>
</head>
<body>
<main class="vms-container">
    <div class="fp-card">
        <div class="fp-logo"><span>Vendor</span>Bridge</div>

        <?php if ($step === 'email'): ?>
            <!-- Step 1: Enter Email -->
            <h1 class="fp-title">Reset your password</h1>
            <p class="fp-sub">Enter the email address linked to your account and we'll set up your password reset.</p>

            <?php if ($error): ?>
                <div class="fp-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php?step=email">
                <div class="fp-group">
                    <label class="fp-label" for="fpEmail">Email Address</label>
                    <input type="email" id="fpEmail" name="email" class="fp-input" placeholder="you@example.com" required autocomplete="email">
                </div>
                <button type="submit" class="fp-btn" id="btnResetEmail">Continue →</button>
            </form>
            <a href="../login/login.php" class="fp-back">← Back to Login</a>

        <?php elseif ($step === 'reset'): ?>
            <!-- Step 2: Set New Password -->
            <h1 class="fp-title">Set new password</h1>
            <p class="fp-sub">Choose a strong password for your account.</p>

            <?php if ($error): ?>
                <div class="fp-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php?step=reset">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['t'] ?? '') ?>">
                <div class="fp-group">
                    <label class="fp-label" for="fpPassword">New Password</label>
                    <input type="password" id="fpPassword" name="password" class="fp-input" placeholder="Min. 6 characters" required>
                </div>
                <div class="fp-group">
                    <label class="fp-label" for="fpConfirm">Confirm Password</label>
                    <input type="password" id="fpConfirm" name="confirm" class="fp-input" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="fp-btn" id="btnSetPassword">Reset Password</button>
            </form>
            <a href="forgot_password.php?step=email" class="fp-back">← Start over</a>
        <?php endif; ?>
    </div>
</main>

<?php if (isset($_SESSION['toast'])): ?>
<script>showToast(<?= json_encode($_SESSION['toast']['message']) ?>, <?= json_encode($_SESSION['toast']['type']) ?>);</script>
<?php unset($_SESSION['toast']); ?>
<?php endif; ?>
</body>
</html>
