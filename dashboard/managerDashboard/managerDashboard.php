<?php
require_once '../../auth/session_helper.php';
require_once '../../config/connection.php';
requireRoles(['manager']);

// Fetch KPI statistics
$pending_count_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status = 'L1_Reviewed'");
$pending_count = mysqli_fetch_assoc($pending_count_res)['cnt'] ?? 0;

$approved_count_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status IN ('Approved', 'PO_Generated')");
$approved_count = mysqli_fetch_assoc($approved_count_res)['cnt'] ?? 0;

// Fetch pending approvals
$pending_query = mysqli_query($conn, "
    SELECT q.*, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname 
    FROM quotations q 
    JOIN rfqs r ON q.rfq_id = r.id 
    JOIN users u ON q.vendor_id = u.id 
    WHERE q.status = 'L1_Reviewed' 
    ORDER BY q.created_at ASC
");

// Fetch completed approvals
$completed_query = mysqli_query($conn, "
    SELECT q.*, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname 
    FROM quotations q 
    JOIN rfqs r ON q.rfq_id = r.id 
    JOIN users u ON q.vendor_id = u.id 
    WHERE q.status IN ('Approved', 'PO_Generated', 'Rejected') 
    ORDER BY q.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Manager Portal</title>
    <link rel="stylesheet" href="managerDashboard.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .split-card {
            flex: 1;
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 24px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .orders-table th, .orders-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--panel-border);
        }
        .orders-table th {
            background-color: #f8fafc;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-approved { background-color: #ecfdf5; color: #059669; }
        .badge-rejected { background-color: #fee2e2; color: #dc2626; }
        .badge-po { background-color: #e0f2fe; color: #0369a1; }
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

    <div class="app-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="managerDashboard.php">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="../adminDashboard/approvals/approvals.php">
                            <span class="nav-icon">✅</span>
                            Approvals
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Panel -->
        <main class="app-content">
            
            <section class="content-header">
                <h1 class="welcome-title">Manager Dashboard</h1>
                <p class="welcome-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> - Approval Sourcing Overview</p>
            </section>

            <!-- KPI Cards Grid -->
            <section class="kpi-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="kpi-card">
                    <div class="kpi-value"><?= $pending_count ?></div>
                    <div class="kpi-label">Awaiting L2 Approvals</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value"><?= $approved_count ?></div>
                    <div class="kpi-label">Total Quotations Approved</div>
                </div>
            </section>

            <div class="dashboard-split">
                
                <!-- Pending Approvals -->
                <section class="split-card">
                    <h2 class="card-title">Pending L2 Sourcing Approvals</h2>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>RFQ #</th>
                                    <th>Vendor</th>
                                    <th>Quote Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_query && mysqli_num_rows($pending_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($pending_query)): ?>
                                        <tr>
                                            <td>
                                                <span><?= htmlspecialchars($row['rfq_number']) ?></span><br>
                                                <small style="color:var(--text-muted);"><?= htmlspecialchars($row['rfq_title']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                            <td><strong>₹ <?= number_format($row['grand_total']) ?></strong></td>
                                            <td>
                                                <button class="btn btn-primary" onclick="window.location.href='../adminDashboard/approvals/approvals.php?quote_id=<?= $row['id'] ?>'" style="font-size:0.8rem; padding:6px 12px;">Review & Approve</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No quotations pending L2 manager approval.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Approval Actions History -->
                <section class="split-card">
                    <h2 class="card-title">Completed Sourcing Actions</h2>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>RFQ #</th>
                                    <th>Vendor</th>
                                    <th>Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($completed_query && mysqli_num_rows($completed_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($completed_query)): 
                                        $badge_class = 'badge-approved';
                                        $status_text = 'Approved';
                                        if ($row['status'] === 'Rejected') {
                                            $badge_class = 'badge-rejected';
                                            $status_text = 'Rejected';
                                        } elseif ($row['status'] === 'PO_Generated') {
                                            $badge_class = 'badge-po';
                                            $status_text = 'PO Generated';
                                        }
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                            <td>₹ <?= number_format($row['grand_total']) ?></td>
                                            <td><span class="badge <?= $badge_class ?>"><?= $status_text ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No history logged yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>

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
