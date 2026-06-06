<?php
session_start();
require_once '../config/connection.php';
require_once '../auth/session_helper.php';
requireRoles(['admin', 'procurement_officer', 'manager', 'vendor']);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $uid = (int)$_SESSION['id'];
    $res = mysqli_query($conn, "SELECT password FROM users WHERE id = $uid LIMIT 1");
    $row = mysqli_fetch_assoc($res);

    if (!password_verify($current, $row['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_pass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_pass !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $uid");
        $success = 'Password updated successfully!';
    }
}

// Determine back URL based on role
$role     = $_SESSION['role'] ?? '';
$back_url = '../login/login.php';
if ($role === 'admin')                $back_url = '../dashboard/adminDashboard/approvals/user_approvals.php';
elseif ($role === 'procurement_officer') $back_url = '../dashboard/adminDashboard/dashboard/adminDashboard.php';
elseif ($role === 'manager')          $back_url = '../dashboard/managerDashboard/managerDashboard.php';
elseif ($role === 'vendor')           $back_url = '../dashboard/vendorDashboard/vendorDashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Update Password</title>
    <link rel="stylesheet" href="../login/login.css">
    <link rel="stylesheet" href="../toaster/toaster.css">
    <script src="../toaster/toaster.js"></script>
    <style>
        .up-card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            margin: auto;
        }
        .up-logo { font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem; color: #0f172a; }
        .up-logo span { color: #2563eb; }
        .up-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.3rem; }
        .up-sub   { font-size: 0.88rem; color: #64748b; margin-bottom: 1.75rem; }
        .up-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.1rem; }
        .up-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .up-input {
            padding: 0.8rem 1rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            font-family: inherit;
        }
        .up-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .up-btn {
            width: 100%; padding: 0.88rem;
            background: #2563eb; color: #fff;
            border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.15s;
            font-family: inherit;
        }
        .up-btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .up-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.88rem; margin-bottom: 1rem; }
        .up-success { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.88rem; margin-bottom: 1rem; }
        .up-back    { display: block; text-align: center; margin-top: 1rem; color: #2563eb; font-size: 0.85rem; font-weight: 600; text-decoration: none; }
        .up-back:hover { text-decoration: underline; }
        .up-user-chip {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #f1f5f9; border-radius: 50px;
            padding: 0.35rem 0.85rem;
            font-size: 0.82rem; font-weight: 600; color: #334155;
            margin-bottom: 1.5rem;
        }
        .vms-container { display: flex; min-height: 100vh; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 2rem; }
    </style>
</head>
<body>
<main class="vms-container">
    <div class="up-card">
        <div class="up-logo"><span>Vendor</span>Bridge</div>

        <h1 class="up-title">Update Password</h1>
        <p class="up-sub">Change your account password. You'll need to enter your current password to confirm.</p>

        <div class="up-user-chip">
            👤 Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        </div>

        <?php if ($error): ?>
            <div class="up-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="up-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="update_password.php">
            <div class="up-group">
                <label class="up-label" for="upCurrent">Current Password</label>
                <input type="password" id="upCurrent" name="current_password" class="up-input" placeholder="Enter current password" required>
            </div>
            <div class="up-group">
                <label class="up-label" for="upNew">New Password</label>
                <input type="password" id="upNew" name="new_password" class="up-input" placeholder="Min. 6 characters" required>
            </div>
            <div class="up-group">
                <label class="up-label" for="upConfirm">Confirm New Password</label>
                <input type="password" id="upConfirm" name="confirm_password" class="up-input" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="up-btn" id="btnUpdatePass">Update Password →</button>
        </form>

        <a href="<?= htmlspecialchars($back_url) ?>" class="up-back">← Back to Dashboard</a>
    </div>
</main>
</body>
</html>
