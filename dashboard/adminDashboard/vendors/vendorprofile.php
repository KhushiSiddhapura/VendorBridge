<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

$vendor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch vendor info
$vendor_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $vendor_id AND role = 'vendor' LIMIT 1");
if (!$vendor_res || mysqli_num_rows($vendor_res) === 0) {
    die("Vendor not found.");
}
$vendor = mysqli_fetch_assoc($vendor_res);

// Fetch quotations submitted by this vendor
$quotes_query = mysqli_query($conn, "
    SELECT q.*, r.rfq_number, r.title as rfq_title 
    FROM quotations q 
    JOIN rfqs r ON q.rfq_id = r.id 
    WHERE q.vendor_id = $vendor_id 
    ORDER BY q.created_at DESC
");

// Fetch purchase orders received by this vendor
$po_query = mysqli_query($conn, "
    SELECT po.*, r.rfq_number, r.title as rfq_title 
    FROM purchase_orders po 
    JOIN quotations q ON po.quotation_id = q.id 
    JOIN rfqs r ON po.rfq_id = r.id 
    WHERE q.vendor_id = $vendor_id 
    ORDER BY po.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Profile - <?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?></title>
    <link rel="stylesheet" href="vendors.css">
    <style>
        .profile-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .profile-card {
            background-color: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01);
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #ffffff;
            font-size: 2.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px var(--primary-glow);
        }
        .profile-details {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .detail-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-title);
        }
        .detail-value-desc {
            grid-column: span 2;
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--text-body);
            background: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--panel-border);
        }
        .history-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .history-card {
            background-color: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01);
        }
        .history-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 0.5rem;
        }
        .list-table {
            width: 100%;
            border-collapse: collapse;
        }
        .list-table th, .list-table td {
            padding: 0.75rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--panel-border);
        }
        .list-table th {
            text-align: left;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
        }
        .badge-status {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-sub { background-color: #e0f2fe; color: #0369a1; }
        .badge-l1 { background-color: #fef3c7; color: #d97706; }
        .badge-app { background-color: #ecfdf5; color: #059669; }
        .badge-rej { background-color: #fee2e2; color: #dc2626; }
        
        .badge-po-p { background-color: #fef3c7; color: #d97706; }
        .badge-po-d { background-color: #ecfdf5; color: #059669; }
        .badge-po-c { background-color: #f1f5f9; color: #64748b; }
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
                    $active_vendors = (strpos($script, '/dashboard/adminDashboard/vendors/') !== false || strpos($script, 'vendorprofile.php') !== false) ? 'active' : '';
                    $active_rfq = (strpos($script, '/dashboard/adminDashboard/RFQ/') !== false) ? 'active' : '';
                    $active_quotes = (strpos($script, '/dashboard/adminDashboard/quotations/') !== false) ? 'active' : '';
                    $active_approvals = (strpos($script, '/dashboard/adminDashboard/approvals/') !== false) ? 'active' : '';
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
                <div class="title-row">
                    <div class="title-left">
                        <h1 class="welcome-title">Supplier Profile</h1>
                        <p class="welcome-subtitle">Detailed information card for <?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?></p>
                    </div>
                    <button class="btn btn-outline" onclick="window.location.href='vendors.php'">
                        &larr; Back to Directory
                    </button>
                </div>
            </section>

            <div class="profile-container">
                <!-- Profile details card -->
                <div class="profile-card">
                    <div class="profile-avatar-large">
                        <?= strtoupper(substr($vendor['firstname'], 0, 1)) ?>
                    </div>
                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="detail-label">Supplier Name</span>
                            <span class="detail-value"><?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Username</span>
                            <span class="detail-value">@<?= htmlspecialchars($vendor['username']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value"><?= htmlspecialchars($vendor['email']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone Number</span>
                            <span class="detail-value"><?= htmlspecialchars($vendor['phone']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Country</span>
                            <span class="detail-value"><?= htmlspecialchars($vendor['country']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Onboarding Status</span>
                            <span class="detail-value" style="color: #d97706;">Pending Verification</span>
                        </div>
                        <div class="detail-value-desc">
                            <span class="detail-label" style="display:block; margin-bottom: 0.5rem;">Business Description & GSTIN details</span>
                            <?= !empty($vendor['description']) ? nl2br(htmlspecialchars($vendor['description'])) : '<span style="color:var(--text-muted); font-style:italic;">No description recorded yet.</span>' ?>
                        </div>
                    </div>
                </div>

                <!-- History split grid -->
                <div class="history-grid">
                    <!-- Historical Quotations -->
                    <div class="history-card">
                        <h2 class="history-title">Submitted Sourcing Quotations</h2>
                        <div class="table-responsive">
                            <table class="list-table">
                                <thead>
                                    <tr>
                                        <th>RFQ Number</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($quotes_query && mysqli_num_rows($quotes_query) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($quotes_query)): 
                                            $quote_class = 'badge-sub';
                                            if ($row['status'] === 'L1_Reviewed') $quote_class = 'badge-l1';
                                            elseif ($row['status'] === 'Approved') $quote_class = 'badge-app';
                                            elseif ($row['status'] === 'Rejected') $quote_class = 'badge-rej';
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['rfq_number']) ?></strong><br>
                                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($row['rfq_title']) ?></small>
                                                </td>
                                                <td><strong>₹ <?= number_format($row['grand_total']) ?></strong></td>
                                                <td><span class="badge-status <?= $quote_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center; padding:1.5rem 0; color:var(--text-muted);">No quotes submitted yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Awarded Purchase Orders -->
                    <div class="history-card">
                        <h2 class="history-title">Awarded Purchase Orders</h2>
                        <div class="table-responsive">
                            <table class="list-table">
                                <thead>
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($po_query && mysqli_num_rows($po_query) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($po_query)): 
                                            $po_class = 'badge-po-p';
                                            if ($row['status'] === 'Dispatched') $po_class = 'badge-po-d';
                                            elseif ($row['status'] === 'Cancelled') $po_class = 'badge-po-c';
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['po_number']) ?></strong><br>
                                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($row['rfq_title']) ?></small>
                                                </td>
                                                <td><strong>₹ <?= number_format($row['grand_total']) ?></strong></td>
                                                <td><span class="badge-status <?= $po_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center; padding:1.5rem 0; color:var(--text-muted);">No POs awarded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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
