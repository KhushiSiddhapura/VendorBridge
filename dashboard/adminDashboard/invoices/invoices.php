<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

// Fetch all Invoices
$sql = "SELECT i.*, po.po_number, u.firstname, u.lastname 
        FROM invoices i 
        JOIN purchase_orders po ON i.po_id = po.id 
        JOIN quotations q ON po.quotation_id = q.id 
        JOIN users u ON q.vendor_id = u.id 
        ORDER BY i.created_at DESC";
$invoices_query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Invoices Directory</title>
    <link rel="stylesheet" href="../RFQ/rfq.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .rfq-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
            background: var(--panel-bg);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--panel-border);
        }
        .rfq-table th {
            background-color: #f8fafc;
            color: var(--text-title);
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid var(--panel-border);
        }
        .rfq-table td {
            padding: 1rem;
            color: var(--text-body);
            font-size: 0.9rem;
            border-bottom: 1px solid var(--panel-border);
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-unpaid { background-color: #fef3c7; color: #d97706; }
        .badge-paid { background-color: #ecfdf5; color: #059669; }
        .badge-overdue { background-color: #fee2e2; color: #dc2626; }
    </style>
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

    <div class="app-layout">
                                <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <?php
                    $script = $_SERVER['SCRIPT_NAME'];
                    $active_dash = (strpos($script, '/dashboard/adminDashboard/dashboard/') !== false) ? 'active' : '';
                    $active_vendors = (strpos($script, '/dashboard/adminDashboard/vendors/') !== false) ? 'active' : '';
                    $active_rfq = (strpos($script, '/dashboard/adminDashboard/RFQ/') !== false) ? 'active' : '';
                    $active_quotes = (strpos($script, '/dashboard/adminDashboard/quotations/') !== false) ? 'active' : '';
                    $active_approvals = (strpos($script, '/dashboard/adminDashboard/approvals/') !== false && strpos($script, 'user_approvals.php') === false) ? 'active' : '';
                    $active_user_approvals = (strpos($script, '/dashboard/adminDashboard/approvals/user_approvals.php') !== false) ? 'active' : '';
                    $active_po = (strpos($script, '/dashboard/adminDashboard/purchase_orders/') !== false) ? 'active' : '';
                    $active_invoices = (strpos($script, '/dashboard/adminDashboard/invoices/') !== false) ? 'active' : '';
                    $active_reports = (strpos($script, '/dashboard/adminDashboard/reports/') !== false) ? 'active' : '';
                    $active_activity = (strpos($script, '/dashboard/adminDashboard/activity/') !== false) ? 'active' : '';
                    $active_register = (strpos($script, '/register/') !== false) ? 'active' : '';
                    
                    $root = getProjectRoot();
                    ?>
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
                            Purchase orders
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
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main class="app-content">
            <section class="content-header">
                <h1 class="welcome-title">Invoices</h1>
                <p class="welcome-subtitle">Overview of client billing and invoice statuses</p>
            </section>

            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>PO Reference</th>
                        <th>Vendor Name</th>
                        <th>Subtotal</th>
                        <th>Tax (GST 18%)</th>
                        <th>Grand Total</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($invoices_query && mysqli_num_rows($invoices_query) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($invoices_query)): 
                            $status_class = 'badge-unpaid';
                            if ($row['status'] === 'Paid') $status_class = 'badge-paid';
                            if ($row['status'] === 'Overdue') $status_class = 'badge-overdue';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['invoice_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['po_number']) ?></td>
                                <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                <td>₹ <?= number_format($row['subtotal']) ?></td>
                                <td>₹ <?= number_format($row['tax_amount']) ?></td>
                                <td><strong>₹ <?= number_format($row['grand_total']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                                <td>
                                    <a href="invoice_details.php?id=<?= $row['id'] ?>" class="btn btn-action" style="text-decoration:none; display:inline-block; font-size:0.85rem; padding:6px 12px;">Open Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                No Invoices found in the database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
            }
        });
    </script>
</body>
</html>
