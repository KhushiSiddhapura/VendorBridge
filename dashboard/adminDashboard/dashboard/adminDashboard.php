<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

// Fetch active RFQs count
$active_rfqs_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM rfqs WHERE status = 'Published'");
$active_rfqs = mysqli_fetch_assoc($active_rfqs_res)['cnt'] ?? 0;

// Fetch pending approvals count
$pending_app_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status = 'Submitted' OR status = 'L1_Reviewed'");
$pending_app = mysqli_fetch_assoc($pending_app_res)['cnt'] ?? 0;

// Fetch POs this month
$po_month_res = mysqli_query($conn, "SELECT SUM(grand_total) as total FROM purchase_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$po_month = mysqli_fetch_assoc($po_month_res)['total'] ?? 0;
$po_month_formatted = '₹ ' . ($po_month >= 100000 ? number_format($po_month / 100000, 1) . 'L' : number_format($po_month));

// Fetch pending payments
$pending_pay_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM invoices WHERE status = 'Unpaid'");
$pending_pay = mysqli_fetch_assoc($pending_pay_res)['cnt'] ?? 0;

// Fetch recent POs
$recent_pos = mysqli_query($conn, "SELECT po.po_number, u.firstname, u.lastname, po.grand_total, po.status FROM purchase_orders po JOIN quotations q ON po.quotation_id = q.id JOIN users u ON q.vendor_id = u.id ORDER BY po.created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal main dashboard. Track vendors, purchase orders, quotations, and active RFQs.">
    <title>VendorBridge - Dashboard portal</title>
    <link rel="stylesheet" href="adminDashboard.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
</head>
<body>

    <!-- Mobile Menu Backdrop -->
    <div class="menu-backdrop" id="menuBackdrop"></div>

    <!-- Header Bar -->
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
        
        <?php
        $role_class = 'color-g';
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') $role_class = 'color-a';
            elseif ($_SESSION['role'] === 'manager') $role_class = 'color-m';
            elseif ($_SESSION['role'] === 'procurement_officer') $role_class = 'color-s';
        }
        $logout_path = 'auth/logout.php';
        $current_uri = $_SERVER['SCRIPT_NAME'];
        if (strpos($current_uri, '/dashboard/adminDashboard/') !== false) {
            $logout_path = '../../../auth/logout.php';
        } elseif (strpos($current_uri, '/dashboard/') !== false) {
            $logout_path = '../../auth/logout.php';
        } elseif (strpos($current_uri, '/register/') !== false || strpos($current_uri, '/login/') !== false) {
            $logout_path = '../auth/logout.php';
        }
        ?>
        <div class="header-profile-group">
            <?php if (isset($_SESSION['role'])): ?>
                <span class="avatar-initial <?= $role_class ?>" title="Profile: <?= htmlspecialchars($_SESSION['role']) ?>"><?= strtoupper(substr($_SESSION['role'], 0, 1)) ?></span>
                <div class="user-avatar-circle" title="User Profile: <?= htmlspecialchars($_SESSION['username']) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>
                <a href="<?= $logout_path ?>" title="Logout" style="text-decoration: none;">
                    <div class="user-avatar-circle" style="border-color: #ef4444; color: #ef4444; margin-left: 0.25rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- App Main Layout -->
    <div class="app-layout">
        
        <!-- Sidebar Navigation -->
                                        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <?php
                    $script = $_SERVER['SCRIPT_NAME'];
                    $active_dash           = (strpos($script, '/dashboard/adminDashboard/dashboard/') !== false) ? 'active' : '';
                    $active_vendors        = (strpos($script, '/dashboard/adminDashboard/vendors/') !== false) ? 'active' : '';
                    $active_rfq            = (strpos($script, '/dashboard/adminDashboard/RFQ/') !== false) ? 'active' : '';
                    $active_quotes         = (strpos($script, '/dashboard/adminDashboard/quotations/') !== false) ? 'active' : '';
                    $active_approvals      = (strpos($script, '/dashboard/adminDashboard/approvals/') !== false && strpos($script, 'user_approvals.php') === false) ? 'active' : '';
                    $active_user_approvals = (strpos($script, '/dashboard/adminDashboard/approvals/user_approvals.php') !== false) ? 'active' : '';
                    $active_po             = (strpos($script, '/dashboard/adminDashboard/purchase_orders/') !== false) ? 'active' : '';
                    $active_invoices       = (strpos($script, '/dashboard/adminDashboard/invoices/') !== false) ? 'active' : '';
                    $active_reports        = (strpos($script, '/dashboard/adminDashboard/reports/') !== false) ? 'active' : '';
                    $active_activity       = (strpos($script, '/dashboard/adminDashboard/activity/') !== false) ? 'active' : '';
                    $active_register       = (strpos($script, '/register/') !== false) ? 'active' : '';
                    $root = getProjectRoot();
                    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
                    ?>
                    <?php if ($is_admin): ?>
                        <!-- Admin sees ONLY User Approvals + Add Account -->
                        <li class="<?= $active_user_approvals ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/approvals/user_approvals.php">
                                <span class="nav-icon">👥</span>
                                User Approvals
                            </a>
                        </li>
                        <li class="<?= $active_register ?>">
                            <a href="<?= $root ?>register/register.php">
                                <span class="nav-icon">👤</span>
                                Add Account
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Procurement Officer / other roles see full nav -->
                        <li class="<?= $active_dash ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/dashboard/adminDashboard.php">
                                <span class="nav-icon">📊</span>
                                Dashboard
                            </a>
                        </li>
                        <li class="<?= $active_vendors ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/vendors/vendors.php">
                                <span class="nav-icon">🤝</span>
                                Vendors
                            </a>
                        </li>
                        <li class="<?= $active_rfq ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/RFQ/rfq_list.php">
                                <span class="nav-icon">📝</span>
                                RFQ's
                            </a>
                        </li>
                        <li class="<?= $active_quotes ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/quotations/quotations.php">
                                <span class="nav-icon">📁</span>
                                Quotations
                            </a>
                        </li>
                        <li class="<?= $active_approvals ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/approvals/approvals.php">
                                <span class="nav-icon">✅</span>
                                Approvals
                            </a>
                        </li>
                        <li class="<?= $active_po ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/purchase_orders/po_list.php">
                                <span class="nav-icon">📦</span>
                                Purchase Orders
                            </a>
                        </li>
                        <li class="<?= $active_invoices ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/invoices/invoices.php">
                                <span class="nav-icon">🧾</span>
                                Invoices
                            </a>
                        </li>
                        <li class="<?= $active_reports ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/reports/reports.php">
                                <span class="nav-icon">📈</span>
                                Reports
                            </a>
                        </li>
                        <li class="<?= $active_activity ?>">
                            <a href="<?= $root ?>dashboard/adminDashboard/activity/activity.php">
                                <span class="nav-icon">🔔</span>
                                Activity
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Panel -->
        <main class="app-content">
            
            <!-- Welcome Header -->
            <section class="content-header">
                <h1 class="welcome-title">Dashboard</h1>
                <p class="welcome-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> - Today's Overview</p>
            </section>

            <!-- KPI Metric Cards Grid -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-value"><?= $active_rfqs ?></div>
                    <div class="kpi-label">Active RFQ's</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value"><?= $pending_app ?></div>
                    <div class="kpi-label">Pending Approvals</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value"><?= $po_month_formatted ?></div>
                    <div class="kpi-label">PO's this month</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value"><?= $pending_pay ?></div>
                    <div class="kpi-label">Pending Payments</div>
                </div>
            </section>

            <!-- Middle Split Panel Section -->
            <div class="dashboard-split">
                
                <!-- Recent Purchase Orders Card -->
                <section class="split-card orders-card">
                    <h2 class="card-title">Recent Purchase Orders</h2>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>PO#</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_pos && mysqli_num_rows($recent_pos) > 0): ?>
                                    <?php while ($po = mysqli_fetch_assoc($recent_pos)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($po['po_number']) ?></td>
                                            <td><?= htmlspecialchars($po['firstname'] . ' ' . $po['lastname']) ?></td>
                                            <td>₹ <?= number_format($po['grand_total']) ?></td>
                                            <td>
                                                <span class="status-pill <?= strtolower(htmlspecialchars($po['status'])) ?>">
                                                    <?= htmlspecialchars($po['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No purchase orders generated yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Spending Trends Graphic Card -->
                <section class="split-card trends-card">
                    <h2 class="card-title">Spending Trends last 6 months</h2>
                    <div class="trends-visual-container">
                        <!-- Left Side: Circular slice/Pie Chart and legend -->
                        <div class="trends-left">
                            <div class="pie-chart-container">
                                <!-- SVG Pie Chart -->
                                <svg width="100" height="100" viewBox="0 0 36 36" class="pie-chart">
                                    <circle class="pie-bg" cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#f1f5f9" stroke-width="4.2"></circle>
                                    <circle class="pie-segment segment-primary" cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#2563eb" stroke-width="4.2" stroke-dasharray="65 35" stroke-dashoffset="25"></circle>
                                    <circle class="pie-segment segment-secondary" cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#60a5fa" stroke-width="4.2" stroke-dasharray="25 75" stroke-dashoffset="90"></circle>
                                    <circle class="pie-segment segment-accent" cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#34d399" stroke-width="4.2" stroke-dasharray="10 90" stroke-dashoffset="100"></circle>
                                </svg>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item"><span class="bullet p-blue"></span> PO Orders</div>
                                <div class="legend-item"><span class="bullet p-light-blue"></span> RFQ Sourcing</div>
                                <div class="legend-item"><span class="bullet p-green"></span> Invoices</div>
                            </div>
                        </div>

                        <!-- Right Side: Graphic Trends Display -->
                        <div class="trends-right">
                            <!-- SVG Line Graph Sourcing Graph -->
                            <div class="line-graph-container">
                                <svg viewBox="0 0 160 60" class="line-graph" width="100%" height="60">
                                    <path d="M 10 50 Q 35 25, 60 38 T 110 18 T 150 25" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round"></path>
                                    <circle cx="10" cy="50" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                    <circle cx="35" cy="30" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                    <circle cx="60" cy="38" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                    <circle cx="85" cy="22" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                    <circle cx="110" cy="18" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                    <circle cx="150" cy="25" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="2"></circle>
                                </svg>
                            </div>
                            
                            <!-- Custom Bar Chart Visualization -->
                            <div class="bar-chart-container">
                                <div class="chart-bar-col">
                                    <div class="bar-fill" style="height: 60%;"></div>
                                </div>
                                <div class="chart-bar-col">
                                    <div class="bar-fill" style="height: 80%;"></div>
                                </div>
                                <div class="chart-bar-col">
                                    <div class="bar-fill" style="height: 45%;"></div>
                                </div>
                                <div class="chart-bar-col">
                                    <div class="bar-fill" style="height: 95%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Bottom Actions Area -->
            <footer class="content-actions">
                <hr class="actions-divider">
                <div class="actions-row">
                    <button class="btn btn-primary" id="btnNewRFQ">
                        <span class="btn-icon">+</span> new RFQ
                    </button>
                    <button class="btn btn-outline" id="btnAddVendor">
                        Add Vendor
                    </button>
                    <button class="btn btn-outline" id="btnViewInvoices">
                        View Invoices
                    </button>
                </div>
            </footer>

        </main>
    </div>

    <!-- Toggle Navigation Script for Mobile Hamburger Menu -->
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

            // Button redirects
            const btnNewRFQ = document.getElementById('btnNewRFQ');
            if (btnNewRFQ) {
                btnNewRFQ.addEventListener('click', () => {
                    window.location.href = '../RFQ/rfq.php';
                });
            }
            const btnAddVendor = document.getElementById('btnAddVendor');
            if (btnAddVendor) {
                btnAddVendor.addEventListener('click', () => {
                    window.location.href = '../../../register/register.php';
                });
            }
            const btnViewInvoices = document.getElementById('btnViewInvoices');
            if (btnViewInvoices) {
                btnViewInvoices.addEventListener('click', () => {
                    window.location.href = '../invoices/invoices.php';
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
