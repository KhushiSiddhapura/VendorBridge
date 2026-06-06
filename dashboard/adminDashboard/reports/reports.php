<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

// 1. Calculate General Spent Summaries
$summary_res = mysqli_query($conn, "SELECT SUM(grand_total) as total_spend, AVG(grand_total) as avg_po, COUNT(id) as total_pos FROM purchase_orders WHERE status != 'Cancelled'");
$summary = mysqli_fetch_assoc($summary_res);
$total_spend = $summary['total_spend'] ?? 0;
$avg_po = $summary['avg_po'] ?? 0;
$total_pos = $summary['total_pos'] ?? 0;

// 2. Sourcing Categories breakdown
$cat_res = mysqli_query($conn, "SELECT r.category, SUM(po.grand_total) as category_total 
                               FROM purchase_orders po 
                               JOIN rfqs r ON po.rfq_id = r.id 
                               WHERE po.status != 'Cancelled' 
                               GROUP BY r.category");
$categories = [];
$cat_grand_total = 0;
while ($row = mysqli_fetch_assoc($cat_res)) {
    $categories[$row['category']] = (float)$row['category_total'];
    $cat_grand_total += (float)$row['category_total'];
}

// Ensure default categories exist to prevent empty array warnings
$default_cats = ['Furniture' => 0, 'IT' => 0, 'Constructions' => 0, 'Logistics' => 0];
foreach ($default_cats as $k => $v) {
    if (!isset($categories[$k])) {
        $categories[$k] = 0;
    }
}

// 3. Vendor Performance averages
$vendor_perf_res = mysqli_query($conn, "
    SELECT u.id, u.firstname, u.lastname, u.email,
           COUNT(po.id) as po_count, 
           SUM(po.grand_total) as total_po_val,
           AVG(q.delivery_days) as avg_delivery
    FROM users u
    LEFT JOIN quotations q ON u.id = q.vendor_id AND q.status = 'PO_Generated'
    LEFT JOIN purchase_orders po ON q.id = po.quotation_id
    WHERE u.role = 'vendor'
    GROUP BY u.id
    ORDER BY total_po_val DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Reports & Analytics</title>
    <link rel="stylesheet" href="../RFQ/rfq.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 1.5rem;
            margin-bottom: 24px;
        }
        .report-card {
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 24px;
        }
        .report-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-title);
            margin-bottom: 1rem;
        }
        .metric-cards-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .metric-card-mini {
            background: #f8fafc;
            border-radius: 6px;
            padding: 16px;
            border: 1px solid var(--panel-border);
            text-align: center;
        }
        .metric-val {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        .metric-lbl {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .pie-chart-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
        }
        .pie-legend {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .pie-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 6px 0;
            font-size: 0.85rem;
        }
        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .c-furniture { background-color: #2563eb; }
        .c-it { background-color: #60a5fa; }
        .c-constructions { background-color: #34d399; }
        .c-logistics { background-color: #fb7185; }
        
        .rfq-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            background: var(--panel-bg);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--panel-border);
        }
        .rfq-table th, .rfq-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--panel-border);
        }
        .rfq-table th {
            background-color: #f8fafc;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
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
                <h1 class="welcome-title">Reports & Analytics</h1>
                <p class="welcome-subtitle">Live analytics on spendings, categories, and vendor performance indicators</p>
            </section>

            <!-- Stat Cards mini -->
            <div class="metric-cards-row">
                <div class="metric-card-mini">
                    <div class="metric-val">₹ <?= number_format($total_spend) ?></div>
                    <div class="metric-lbl">Total Sourcing Spend</div>
                </div>
                <div class="metric-card-mini">
                    <div class="metric-val">₹ <?= number_format($avg_po) ?></div>
                    <div class="metric-lbl">Average PO Value</div>
                </div>
                <div class="metric-card-mini">
                    <div class="metric-val"><?= $total_pos ?></div>
                    <div class="metric-lbl">Total POs Issued</div>
                </div>
            </div>

            <div class="reports-grid">
                
                <!-- Category Breakdown Card -->
                <div class="report-card">
                    <h2 class="report-title">Spending Breakdown by Category</h2>
                    
                    <div class="pie-chart-wrap">
                        <svg width="120" height="120" viewBox="0 0 36 36" class="pie-chart" style="transform: rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#f1f5f9" stroke-width="4.2"></circle>
                            
                            <?php
                            // Calculate dash arrays
                            $total_cat = array_sum($categories);
                            if ($total_cat == 0) $total_cat = 1;
                            
                            $pct_furniture = ($categories['Furniture'] / $total_cat) * 100;
                            $pct_it = ($categories['IT'] / $total_cat) * 100;
                            $pct_constructions = ($categories['Constructions'] / $total_cat) * 100;
                            $pct_logistics = ($categories['Logistics'] / $total_cat) * 100;
                            
                            $offset = 0;
                            ?>
                            
                            <!-- Furniture -->
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#2563eb" stroke-width="4.2" 
                                    stroke-dasharray="<?= $pct_furniture ?> <?= 100 - $pct_furniture ?>" 
                                    stroke-dashoffset="<?= -$offset ?>"></circle>
                            <?php $offset += $pct_furniture; ?>
                            
                            <!-- IT -->
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#60a5fa" stroke-width="4.2" 
                                    stroke-dasharray="<?= $pct_it ?> <?= 100 - $pct_it ?>" 
                                    stroke-dashoffset="<?= -$offset ?>"></circle>
                            <?php $offset += $pct_it; ?>
                            
                            <!-- Constructions -->
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#34d399" stroke-width="4.2" 
                                    stroke-dasharray="<?= $pct_constructions ?> <?= 100 - $pct_constructions ?>" 
                                    stroke-dashoffset="<?= -$offset ?>"></circle>
                            <?php $offset += $pct_constructions; ?>
                            
                            <!-- Logistics -->
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#fb7185" stroke-width="4.2" 
                                    stroke-dasharray="<?= $pct_logistics ?> <?= 100 - $pct_logistics ?>" 
                                    stroke-dashoffset="<?= -$offset ?>"></circle>
                        </svg>
                        
                        <ul class="pie-legend">
                            <li class="pie-legend-item">
                                <span class="color-dot c-furniture"></span> Furniture (₹<?= number_format($categories['Furniture']) ?>)
                            </li>
                            <li class="pie-legend-item">
                                <span class="color-dot c-it"></span> IT (₹<?= number_format($categories['IT']) ?>)
                            </li>
                            <li class="pie-legend-item">
                                <span class="color-dot c-constructions"></span> Construction (₹<?= number_format($categories['Constructions']) ?>)
                            </li>
                            <li class="pie-legend-item">
                                <span class="color-dot c-logistics"></span> Logistics (₹<?= number_format($categories['Logistics']) ?>)
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Monthly Trends last 6 months -->
                <div class="report-card">
                    <h2 class="report-title">Sourcing Operations Volume</h2>
                    <div style="height: 120px; display: flex; align-items: flex-end; justify-content: space-around; padding-top: 10px; border-bottom: 2px solid #e2e8f0;">
                        <!-- Mocking bars corresponding to months -->
                        <div style="text-align: center; width: 40px;">
                            <div style="background: #2563eb; height: 35px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size:0.75rem; color: var(--text-muted);">Jan</span>
                        </div>
                        <div style="text-align: center; width: 40px;">
                            <div style="background: #2563eb; height: 50px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size:0.75rem; color: var(--text-muted);">Feb</span>
                        </div>
                        <div style="text-align: center; width: 40px;">
                            <div style="background: #2563eb; height: 40px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size:0.75rem; color: var(--text-muted);">Mar</span>
                        </div>
                        <div style="text-align: center; width: 40px;">
                            <div style="background: #2563eb; height: 75px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size:0.75rem; color: var(--text-muted);">Apr</span>
                        </div>
                        <div style="text-align: center; width: 40px;">
                            <div style="background: #2563eb; height: 90px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size:0.75rem; color: var(--text-muted);">May</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Vendor performance directory -->
            <div class="report-card" style="margin-bottom: 40px;">
                <h2 class="report-title">Supplier Performance Index</h2>
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th>Email Address</th>
                            <th>POs Completed</th>
                            <th>Total Business Value</th>
                            <th>Avg. Delivery Time</th>
                            <th>Status Index</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vendor_perf_res && mysqli_num_rows($vendor_perf_res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($vendor_perf_res)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= (int)$row['po_count'] ?> Orders</td>
                                    <td><strong>₹ <?= number_format((float)$row['total_po_val']) ?></strong></td>
                                    <td><?= $row['avg_delivery'] !== null ? number_format((float)$row['avg_delivery'], 1) . ' Days' : 'N/A' ?></td>
                                    <td>
                                        <span class="score-pill" style="background:#ecfdf5; color:#059669; font-weight:600;">Active Supplier</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No suppliers logged in database.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
