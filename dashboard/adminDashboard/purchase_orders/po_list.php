<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

// Fetch all POs sorted by newest
$sql = "SELECT po.*, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname 
        FROM purchase_orders po 
        JOIN rfqs r ON po.rfq_id = r.id 
        JOIN quotations q ON po.quotation_id = q.id 
        JOIN users u ON q.vendor_id = u.id 
        ORDER BY po.created_at DESC";
$po_query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Purchase Orders Directory</title>
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
        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-dispatched { background-color: #ecfdf5; color: #059669; }
        .badge-cancelled { background-color: #fee2e2; color: #dc2626; }
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
                    <li><a href="../dashboard/adminDashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
                    <li><a href="../vendors/vendors.php"><span class="nav-icon">🤝</span> Vendors</a></li>
                    <li><a href="../RFQ/rfq_list.php"><span class="nav-icon">📝</span> RFQ's</a></li>
                    <li><a href="../quotations/quotations.php"><span class="nav-icon">📁</span> Quotations</a></li>
                    <li><a href="../approvals/approvals.php"><span class="nav-icon">✅</span> Approvals</a></li>
                    <li class="active"><a href="./po_list.php"><span class="nav-icon">📦</span> Purchase orders</a></li>
                    <li><a href="../invoices/invoices.php"><span class="nav-icon">🧾</span> Invoices</a></li>
                    <li><a href="../reports/reports.php"><span class="nav-icon">📈</span> Reports</a></li>
                    <li><a href="../activity/activity.php"><span class="nav-icon">🔔</span> Activity</a></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="../../../register/register.php"><span class="nav-icon">👤</span> Add Account</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main class="app-content">
            <section class="content-header">
                <h1 class="welcome-title">Purchase Orders</h1>
                <p class="welcome-subtitle">Directory of issued purchase orders</p>
            </section>

            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>RFQ Reference</th>
                        <th>Vendor Name</th>
                        <th>Amount</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($po_query && mysqli_num_rows($po_query) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($po_query)): 
                            $status_class = 'badge-pending';
                            if ($row['status'] === 'Dispatched') $status_class = 'badge-dispatched';
                            if ($row['status'] === 'Cancelled') $status_class = 'badge-cancelled';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['po_number']) ?></strong></td>
                                <td>
                                    <span><?= htmlspecialchars($row['rfq_number']) ?></span><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($row['rfq_title']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                <td><strong>₹ <?= number_format($row['grand_total']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                                <td>
                                    <a href="../invoices/invoice_details.php?po_id=<?= $row['id'] ?>" class="btn btn-action" style="text-decoration:none; display:inline-block; font-size:0.85rem; padding:6px 12px;">View Invoice</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                No Purchase Orders generated in system database.
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
