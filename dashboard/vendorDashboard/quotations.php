<?php
require_once '../../auth/session_helper.php';
require_once '../../config/connection.php';
requireRoles(['vendor']);

$vendor_id = $_SESSION['id'];

// 1. Fetch pending RFQ assignments (not yet quoted)
$pending_rfqs = mysqli_query($conn, "
    SELECT r.* 
    FROM rfqs r 
    WHERE r.status = 'Published' 
      AND (JSON_CONTAINS(r.assigned_vendors, '$vendor_id') OR r.assigned_vendors LIKE '%$vendor_id%')
      AND NOT EXISTS (SELECT 1 FROM quotations q WHERE q.rfq_id = r.id AND q.vendor_id = $vendor_id)
    ORDER BY r.submission_deadline ASC
");

// 2. Fetch all submitted quotations
$submitted_quotes = mysqli_query($conn, "
    SELECT q.*, r.rfq_number, r.title as rfq_title 
    FROM quotations q 
    JOIN rfqs r ON q.rfq_id = r.id 
    WHERE q.vendor_id = $vendor_id 
    ORDER BY q.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - My Quotations</title>
    <link rel="stylesheet" href="vendorDashboard.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .split-card {
            flex: 1;
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 24px;
            margin-bottom: 24px;
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
        .status-pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pill.submitted { background-color: #e0f2fe; color: #0369a1; }
        .status-pill.l1_reviewed { background-color: #fef3c7; color: #d97706; }
        .status-pill.approved { background-color: #ecfdf5; color: #059669; }
        .status-pill.rejected { background-color: #fee2e2; color: #dc2626; }
        .status-pill.po_generated { background-color: #e0e7ff; color: #4338ca; }
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
                    <li>
                        <a href="vendorDashboard.php">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="active">
                        <a href="quotations.php">
                            <span class="nav-icon">📁</span>
                            Quotations
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Panel -->
        <main class="app-content">
            
            <section class="content-header">
                <h1 class="welcome-title">My Sourcing Proposals</h1>
                <p class="welcome-subtitle">Submit and track your quotations requested by RFQs</p>
            </section>

            <!-- Pending Sourcing Requests -->
            <section class="split-card">
                <h2 class="card-title">Pending Sourcing RFQ Requests</h2>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>RFQ #</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Deadline</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pending_rfqs && mysqli_num_rows($pending_rfqs) > 0): ?>
                                <?php while ($rfq = mysqli_fetch_assoc($pending_rfqs)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($rfq['rfq_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($rfq['title']) ?></td>
                                        <td><?= htmlspecialchars($rfq['category']) ?></td>
                                        <td><span style="color:#dc2626; font-weight:600;"><?= date('M d, Y', strtotime($rfq['submission_deadline'])) ?></span></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="window.location.href='submit_quotation.php?rfq_id=<?= $rfq['id'] ?>'" style="font-size:0.8rem; padding:6px 12px;">Submit Quote</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No pending RFQs waiting for quotation responses.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- History of Submitted Bids -->
            <section class="split-card">
                <h2 class="card-title">Submitted Quotation History</h2>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Quotation ID</th>
                                <th>RFQ Info</th>
                                <th>Bid Total</th>
                                <th>Delivery Timeline</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($submitted_quotes && mysqli_num_rows($submitted_quotes) > 0): ?>
                                <?php while ($quote = mysqli_fetch_assoc($submitted_quotes)): 
                                    $status_class = strtolower($quote['status']);
                                ?>
                                    <tr>
                                        <td><strong>#<?= $quote['id'] ?></strong></td>
                                        <td>
                                            <span><?= htmlspecialchars($quote['rfq_number']) ?></span><br>
                                            <small style="color:var(--text-muted);"><?= htmlspecialchars($quote['rfq_title']) ?></small>
                                        </td>
                                        <td><strong>₹ <?= number_format($quote['grand_total']) ?></strong></td>
                                        <td><?= htmlspecialchars($quote['delivery_days']) ?> days</td>
                                        <td><span class="status-pill <?= $status_class ?>"><?= str_replace('_', ' ', htmlspecialchars($quote['status'])) ?></span></td>
                                        <td><?= date('M d, Y h:i A', strtotime($quote['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">You haven't submitted any quotations yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

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
