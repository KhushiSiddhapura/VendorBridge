<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge registration portal. Create vendor and procurement officer accounts.">
    <title>VendorBridge - Register</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="../toaster/toaster.css">
    <script src="../toaster/toaster.js"></script>
</head>

<body>

    <div class="menu-backdrop" id="menuBackdrop"></div>

    <header class="dash-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="btnMenuToggle" aria-label="Toggle Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-logo">
                <span>Vendor</span>Bridge
            </div>
        </div>
        <div class="header-profile-group">
            <span class="avatar-initial color-m" title="Manager">M</span>
            <span class="avatar-initial color-a" title="Admin">A</span>
            <span class="avatar-initial color-s" title="Supervisor">S</span>
            <span class="avatar-initial color-g" title="Guest">G</span>
            <div class="user-avatar-circle" title="User Profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </div>
        </div>
    </header>

    <div class="app-layout">

        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="landingpage.html">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="active">
                        <a href="../dashboard/adminDashboard/vendors/vendors.php">
                            <span class="nav-icon">🤝</span>
                            Vendors
                        </a>
                    </li>
                    <li>
                        <a href="#rfqs">
                            <span class="nav-icon">📝</span>
                            RFQ's
                        </a>
                    </li>
                    <li>
                        <a href="#quotations">
                            <span class="nav-icon">📁</span>
                            Quotations
                        </a>
                    </li>
                    <li>
                        <a href="#approvals">
                            <span class="nav-icon">✅</span>
                            Approvals
                        </a>
                    </li>
                    <li>
                        <a href="#purchase-orders">
                            <span class="nav-icon">📦</span>
                            Purchase orders
                        </a>
                    </li>
                    <li>
                        <a href="#invoices">
                            <span class="nav-icon">🧾</span>
                            Invoices
                        </a>
                    </li>
                    <li>
                        <a href="#reports">
                            <span class="nav-icon">📈</span>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="#activity">
                            <span class="nav-icon">🔔</span>
                            Activity
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="app-content">

            <section class="content-header">
                <div class="title-row">
                    <div class="title-left">
                        <h1 class="welcome-title">Add New Account</h1>
                        <p class="welcome-subtitle">Register a new vendor or internal procurement team member</p>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-card">
                    <form id="vmsRegisterForm" method="POST" action="../auth/register.php">
                        
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label class="form-label" for="vmsFirstName">First Name</label>
                                <div class="input-container">
                                    <input type="text" id="vmsFirstName" name="firstname" class="form-control" placeholder="e.g. Aarav" required autocomplete="given-name">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vmsLastName">Last Name</label>
                                <div class="input-container">
                                    <input type="text" id="vmsLastName" name="lastname" class="form-control" placeholder="e.g. Sharma" required autocomplete="family-name">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vmsEmail">Email Address</label>
                                <div class="input-container">
                                    <input type="email" id="vmsEmail" name="email" class="form-control" placeholder="example@company.com" required autocomplete="email">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vmsPhone">Phone Number</label>
                                <div class="input-container">
                                    <input type="tel" id="vmsPhone" name="phone" class="form-control" placeholder="+91 9876543210" required autocomplete="tel">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" /></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vmsRole">System Role</label>
                                <div class="select-wrapper">
                                    <select id="vmsRole" name="role" class="form-control" required>
                                        <option value="vendor" selected>Vendor</option>
                                        <option value="admin">Admin</option>
                                        <option value="manager">Manager</option>
                                        <option value="procurement_officer">Procurement Officer</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vmsCountry">Country</label>
                                <div class="input-container">
                                    <input type="text" id="vmsCountry" name="country" class="form-control" placeholder="India" required>
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group grid-span-2">
                                <label class="form-label" for="vmsInfo">Business Details & Info</label>
                                <textarea id="vmsInfo" name="description" class="form-control text-area-control" rows="4" placeholder="Enter GSTIN, PAN, primary services, or critical onboarding structural logs..."></textarea>
                            </div>

                        </div>

                        <div class="form-actions-row">
                            <button type="button" class="btn btn-action" onclick="window.location.href='vendors.php'">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="registerBtn">Create Account</button>
                        </div>

                    </form>
                </div>
            </section>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnMenuToggle = document.getElementById('btnMenuToggle');
            const menuBackdrop = document.getElementById('menuBackdrop');
            const body = document.body;

            if (btnMenuToggle && menuBackdrop) {
                const toggleMenu = (e) => {
                    e.preventDefault();
                    body.classList.toggle('menu-open');
                };

                btnMenuToggle.addEventListener('click', toggleMenu);
                menuBackdrop.addEventListener('click', toggleMenu);

                const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        body.classList.remove('menu-open');
                    });
                });
            }
        });
    </script>

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