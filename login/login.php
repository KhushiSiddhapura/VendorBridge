<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge secure login portal">
    <title>VendorBridge - Login</title>

    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="../toaster/toaster.css">
    <script src="../toaster/toaster.js"></script>

</head>
<body>

    <main class="vms-container">

        <section class="glass-panel login-panel">

            <div class="split-container">

                <!-- Left Side -->
                <div class="pane-left">

                    <header class="vms-header">
                        <h1><span>Vendor</span>Bridge</h1>
                        <p class="header-desc">
                            Vendor Management System
                        </p>
                    </header>

                    <div class="photo-upload-container">

                        <div class="photo-circle" id="photoCircle">

                            <svg id="photoPlaceholderIcon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">

                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>

                            </svg>

                            <span id="photoPlaceholderText">
                                Secure Login
                            </span>

                            <img
                                id="photoPreview"
                                class="photo-preview"
                                src=""
                                alt="Preview"
                            >

                        </div>

                    </div>

                </div>

                <!-- Right Side -->
                <div class="pane-right">

                    <h2 class="form-title">
                        Login Account
                    </h2>

                    <p class="form-subtitle">
                        Enter your username/email and password
                    </p>

                    <form
                        id="vmsLoginForm"
                        method="POST"
                        action="../auth/login.php"
                    >

                        <!-- Username / Email -->
                        <div class="form-group">

                            <label
                                class="form-label"
                                for="vmsLogin"
                            >
                                Username or Email
                            </label>

                            <div class="input-container">

                                <input
                                    type="text"
                                    id="vmsLogin"
                                    name="login"
                                    class="form-control"
                                    placeholder="Username or Email"
                                    required
                                    autocomplete="username"
                                >

                                <div class="input-icon">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round">

                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>

                                    </svg>

                                </div>

                            </div>

                        </div>

                        <!-- Password -->
                        <div class="form-group">

                            <label
                                class="form-label"
                                for="vmsPassword"
                            >
                                Password
                            </label>

                            <div class="input-container">

                                <input
                                    type="password"
                                    id="vmsPassword"
                                    name="password"
                                    class="form-control"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >

                                <div class="input-icon">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round">

                                        <rect
                                            x="3"
                                            y="11"
                                            width="18"
                                            height="11"
                                            rx="2"
                                            ry="2"
                                        />

                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>

                                    </svg>

                                </div>

                            </div>

                        </div>

                        <!-- Submit -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="loginBtn"
                        >

                            Sign In

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round">

                                <line x1="5" y1="12" x2="19" y2="12"/>

                                <polyline points="12 5 19 12 12 19"/>

                            </svg>

                        </button>

                    </form>

                    <footer class="form-footer">
                        Need an account?
                        Contact your system administrator.
                    </footer>

                </div>

            </div>

        </section>

    </main>

    <?php if(isset($_SESSION['toast'])): ?>
    <script>
    showToast(
        <?= json_encode($_SESSION['toast']['message']) ?>,
        <?= json_encode($_SESSION['toast']['type']) ?>
    );
    </script>
    <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>


</body>
</html>