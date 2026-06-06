<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

$rfq_id = isset($_GET['rfq_id']) ? (int)$_GET['rfq_id'] : 0;
$rfq = null;
$quotes = [];
$lowest_quote_id = null;

if ($rfq_id > 0) {
    // Fetch RFQ details
    $rfq_res = mysqli_query($conn, "SELECT * FROM rfqs WHERE id = $rfq_id");
    if ($rfq_res && mysqli_num_rows($rfq_res) > 0) {
        $rfq = mysqli_fetch_assoc($rfq_res);
        
        // Fetch quotes
        $quotes_res = mysqli_query($conn, "SELECT q.*, u.firstname, u.lastname FROM quotations q JOIN users u ON q.vendor_id = u.id WHERE q.rfq_id = $rfq_id ORDER BY q.grand_total ASC");
        while ($row = mysqli_fetch_assoc($quotes_res)) {
            $quotes[] = $row;
        }
        
        // Highlight lowest grand total
        if (!empty($quotes)) {
            $lowest_quote_id = $quotes[0]['id']; // Since sorted ASC, first is lowest
        }
    }
} else {
    // Fetch all RFQs with their quote count
    $rfqs_list = mysqli_query($conn, "SELECT r.*, COUNT(q.id) as quote_count FROM rfqs r LEFT JOIN quotations q ON r.id = q.rfq_id GROUP BY r.id ORDER BY r.created_at DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal quotation comparison. Compare received vendor quotations on pricing, GST, ratings, and payment terms side-by-side.">
    <title>VendorBridge - Quotation Comparison</title>
    <link rel="stylesheet" href="quotations.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .rfq-select-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: var(--panel-bg);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--panel-border);
        }
        .rfq-select-table th, .rfq-select-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--panel-border);
        }
        .rfq-select-table th {
            background-color: #f8fafc;
            font-weight: 600;
        }
    </style>
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

        <!-- Main Content Panel -->
        <main class="app-content">
            
            <?php if ($rfq && !empty($quotes)): ?>
                <!-- Welcome Header -->
                <section class="content-header">
                    <h1 class="welcome-title">Quotation Comparison</h1>
                    <p class="welcome-subtitle">RFQ: <?= htmlspecialchars($rfq['rfq_number']) ?> - <?= htmlspecialchars($rfq['title']) ?> (<?= count($quotes) ?> quotations received)</p>
                </section>

                <!-- Comparison Table Panel Card -->
                <section class="comparison-section">
                    <div class="comparison-card">
                        <div class="table-responsive">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th class="col-criteria">Criteria</th>
                                        <?php foreach ($quotes as $q): 
                                            $is_lowest = ($q['id'] == $lowest_quote_id);
                                            $highlight_class = $is_lowest ? 'highlighted-vendor' : '';
                                        ?>
                                            <th class="col-vendor <?= $highlight_class ?>">
                                                <div class="vendor-header-wrap">
                                                    <?php if ($is_lowest): ?>
                                                        <span class="badge-lowest">Lowest</span>
                                                    <?php endif; ?>
                                                    <span class="vendor-name"><?= htmlspecialchars($q['firstname'] . ' ' . $q['lastname']) ?></span>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="row-label">Grand Total</td>
                                        <?php foreach ($quotes as $q): ?>
                                            <td class="cell-val <?= ($q['id'] == $lowest_quote_id) ? 'highlighted-vendor' : '' ?>">₹ <?= number_format($q['grand_total']) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <td class="row-label">GST %</td>
                                        <?php foreach ($quotes as $q): ?>
                                            <td class="cell-val <?= ($q['id'] == $lowest_quote_id) ? 'highlighted-vendor' : '' ?>"><?= htmlspecialchars($q['gst_percent']) ?>%</td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <td class="row-label">Delivery (days)</td>
                                        <?php foreach ($quotes as $q): ?>
                                            <td class="cell-val <?= ($q['id'] == $lowest_quote_id) ? 'highlighted-vendor' : '' ?>"><?= htmlspecialchars($q['delivery_days']) ?> days</td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <td class="row-label">Rating</td>
                                        <?php foreach ($quotes as $q): ?>
                                            <td class="cell-val <?= ($q['id'] == $lowest_quote_id) ? 'highlighted-vendor' : '' ?>">
                                                <span class="score-pill">4.5/5</span>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <td class="row-label">Payment terms</td>
                                        <?php foreach ($quotes as $q): ?>
                                            <td class="cell-val <?= ($q['id'] == $lowest_quote_id) ? 'highlighted-vendor' : '' ?>"><?= htmlspecialchars($q['payment_terms']) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr class="actions-row-cell">
                                        <td></td>
                                        <?php foreach ($quotes as $q): 
                                            $is_lowest = ($q['id'] == $lowest_quote_id);
                                        ?>
                                            <td class="cell-action <?= $is_lowest ? 'highlighted-vendor' : '' ?>">
                                                <button class="btn <?= $is_lowest ? 'btn-approve' : 'btn-select' ?>" onclick="window.location.href='../approvals/l1_review.php?quote_id=<?= $q['id'] ?>'">Select & Approve</button>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Responsive Cards for Tablet/Mobile View -->
                    <div class="comparison-cards-mobile">
                        <?php foreach ($quotes as $q):
                            $is_lowest = ($q['id'] == $lowest_quote_id);
                            $card_class = $is_lowest ? 'highlighted-vendor-card' : '';
                        ?>
                            <div class="vendor-card-item <?= $card_class ?>">
                                <div class="vendor-card-header">
                                    <?php if ($is_lowest): ?>
                                        <span class="badge-lowest">Lowest</span>
                                    <?php endif; ?>
                                    <h3 class="vendor-card-title"><?= htmlspecialchars($q['firstname'] . ' ' . $q['lastname']) ?></h3>
                                </div>
                                <div class="vendor-card-body">
                                    <div class="vendor-card-row">
                                        <span class="row-key">Grand Total</span>
                                        <span class="row-value <?= $is_lowest ? 'val-lowest' : '' ?>">₹ <?= number_format($q['grand_total']) ?></span>
                                    </div>
                                    <div class="vendor-card-row">
                                        <span class="row-key">GST %</span>
                                        <span class="row-value"><?= htmlspecialchars($q['gst_percent']) ?>%</span>
                                    </div>
                                    <div class="vendor-card-row">
                                        <span class="row-key">Delivery</span>
                                        <span class="row-value"><?= htmlspecialchars($q['delivery_days']) ?> days</span>
                                    </div>
                                    <div class="vendor-card-row">
                                        <span class="row-key">Rating</span>
                                        <span class="row-value"><span class="score-pill">4.5/5</span></span>
                                    </div>
                                    <div class="vendor-card-row">
                                        <span class="row-key">Payment terms</span>
                                        <span class="row-value"><?= htmlspecialchars($q['payment_terms']) ?></span>
                                    </div>
                                </div>
                                <div class="vendor-card-footer">
                                    <button class="btn <?= $is_lowest ? 'btn-approve' : 'btn-select' ?>" onclick="window.location.href='../approvals/l1_review.php?quote_id=<?= $q['id'] ?>'">Select & Approve</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Legend Information -->
                    <div class="comparison-legend-container">
                        <p class="comparison-legend">Green = lowest price, selecting vendor initiates the approval workflow.</p>
                    </div>
                </section>
            
            <?php elseif ($rfq): ?>
                <section class="content-header">
                    <h1 class="welcome-title">Quotation Comparison</h1>
                    <p class="welcome-subtitle">RFQ: <?= htmlspecialchars($rfq['rfq_number']) ?> - <?= htmlspecialchars($rfq['title']) ?></p>
                </section>
                <div style="background-color: var(--panel-bg); border: 1px solid var(--panel-border); padding: 40px; text-align: center; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 20px;">No quotations have been received for this RFQ yet.</p>
                    <a href="./quotations.php" class="btn btn-outline" style="text-decoration: none;">Back to RFQ List</a>
                </div>
                
            <?php else: ?>
                <!-- List of RFQs with Quote Counts -->
                <section class="content-header">
                    <h1 class="welcome-title">RFQ Quotation Directory</h1>
                    <p class="welcome-subtitle">Select an RFQ to compare received supplier quotations</p>
                </section>
                
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="rfq-select-table">
                            <thead>
                                <tr>
                                    <th>RFQ Number</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Deadline</th>
                                    <th>Quotes Received</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rfqs_list && mysqli_num_rows($rfqs_list) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($rfqs_list)): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['category']) ?></td>
                                            <td><?= date('M d, Y', strtotime($row['submission_deadline'])) ?></td>
                                            <td>
                                                <span class="score-pill"><?= $row['quote_count'] ?> Quotes</span>
                                            </td>
                                            <td>
                                                <?php if ($row['quote_count'] > 0): ?>
                                                    <a href="quotations.php?rfq_id=<?= $row['id'] ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block; font-size: 0.85rem; padding: 6px 12px;">Compare Quotes</a>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-style: italic; font-size:0.9rem;">No quotes</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                            No RFQs logged in the database.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

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
            }
        });
    </script>
</body>
</html>
