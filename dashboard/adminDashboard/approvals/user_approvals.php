<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin']); // Admin-only

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $target_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($target_id > 0 && in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $safe_status = mysqli_real_escape_string($conn, $new_status);

        $update_sql = "UPDATE users SET status = '$safe_status' WHERE id = $target_id AND role != 'admin'";
        if (mysqli_query($conn, $update_sql)) {
            // Log the action
            $admin_id = $_SESSION['id'];
            $log_type = ($action === 'approve') ? 'USER_APPROVED' : 'USER_REJECTED';
            $log_desc = mysqli_real_escape_string($conn, "User ID #$target_id {$new_status} by admin.");
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($admin_id, '$log_type', '$log_desc')");

            $_SESSION['toast'] = [
                'type'    => 'success',
                'message' => "User has been {$new_status} successfully."
            ];
        } else {
            $_SESSION['toast'] = [
                'type'    => 'fail',
                'message' => 'Database error: ' . mysqli_error($conn)
            ];
        }
    }

    header('Location: user_approvals.php');
    exit();
}

// Fetch filter tab
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

// Build WHERE clause
$where_role  = "role IN ('vendor', 'procurement_officer', 'manager')";
$where_status = ($filter !== 'all') ? " AND status = '$filter'" : '';

$users_res = mysqli_query($conn, "SELECT * FROM users WHERE $where_role $where_status ORDER BY id DESC");
$users = [];
while ($row = mysqli_fetch_assoc($users_res)) {
    $users[] = $row;
}

// Count per status for tabs
$counts = [];
foreach (['all' => '', 'pending' => "'pending'", 'approved' => "'approved'", 'rejected' => "'rejected'"] as $key => $val) {
    $cond = $val ? "AND status = $val" : '';
    $res  = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE $where_role $cond");
    $counts[$key] = mysqli_fetch_assoc($res)['cnt'];
}

$root = getProjectRoot();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge admin portal - Approve or reject vendor and staff accounts awaiting onboarding verification.">
    <title>VendorBridge - User Approvals</title>
    <link rel="stylesheet" href="approvals.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        /* ── Page-specific overrides ── */
        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .filter-tabs {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .filter-tab {
            padding: 0.45rem 1.1rem;
            font-family: var(--font-heading);
            font-size: 0.82rem;
            font-weight: 700;
            background-color: var(--panel-bg);
            border: 1.5px solid var(--panel-border);
            color: var(--text-muted);
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .filter-tab:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .filter-tab.active {
            background-color: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 4px 10px var(--primary-glow);
        }
        .filter-tab .tab-count {
            background: rgba(255,255,255,0.25);
            border-radius: 50px;
            padding: 0 6px;
            font-size: 0.72rem;
            font-weight: 800;
        }
        .filter-tab:not(.active) .tab-count {
            background: #f1f5f9;
            color: var(--text-muted);
        }

        /* Table Card */
        .table-card {
            background-color: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            overflow: hidden;
        }
        .table-responsive { overflow-x: auto; }
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        .user-table th {
            padding: 1rem 1.25rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8fafc;
            border-bottom: 1px solid var(--panel-border);
            text-align: left;
            white-space: nowrap;
        }
        .user-table td {
            padding: 1rem 1.25rem;
            font-size: 0.9rem;
            color: var(--text-body);
            border-bottom: 1px solid var(--panel-border);
            vertical-align: middle;
        }
        .user-table tr:last-child td { border-bottom: none; }
        .user-table tbody tr {
            transition: var(--transition-smooth);
        }
        .user-table tbody tr:hover { background-color: #f8fafc; }

        /* User identity cell */
        .user-info { display: flex; align-items: center; gap: 0.85rem; }
        .user-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1rem; color: #fff;
            flex-shrink: 0;
        }
        .avatar-vendor  { background: linear-gradient(135deg, #6366f1, #818cf8); }
        .avatar-officer { background: linear-gradient(135deg, #10b981, #34d399); }
        .avatar-manager { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

        .user-name { font-weight: 700; color: var(--text-title); font-size: 0.95rem; }
        .user-email { font-size: 0.8rem; color: var(--text-muted); margin-top: 1px; }

        /* Role pill */
        .role-pill {
            display: inline-flex; align-items: center;
            padding: 0.22rem 0.65rem;
            border-radius: 50px; font-size: 0.75rem; font-weight: 700;
            text-transform: capitalize; letter-spacing: 0.3px;
        }
        .role-vendor  { background: #ede9fe; color: #5b21b6; }
        .role-officer { background: #d1fae5; color: #065f46; }
        .role-manager { background: #fef3c7; color: #92400e; }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 50px; font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .badge-pending  { background: #fff7ed; color: #c2410c; }
        .badge-pending::before  { background: #f97316; }
        .badge-approved { background: #ecfdf5; color: #065f46; }
        .badge-approved::before { background: #10b981; }
        .badge-rejected { background: #fef2f2; color: #991b1b; }
        .badge-rejected::before { background: #ef4444; }

        /* Action buttons */
        .action-group { display: flex; gap: 0.5rem; }
        .btn-sm {
            padding: 0.38rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: none;
            transition: var(--transition-smooth);
            display: inline-flex; align-items: center; gap: 0.3rem;
            flex: unset;
        }
        .btn-sm-approve {
            background: var(--success-color);
            color: #fff;
            box-shadow: 0 2px 6px rgba(16,185,129,0.2);
        }
        .btn-sm-approve:hover {
            background: var(--success-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(16,185,129,0.3);
        }
        .btn-sm-reject {
            background: var(--panel-bg);
            color: var(--danger-color);
            border: 1.5px solid var(--danger-color);
        }
        .btn-sm-reject:hover {
            background: var(--danger-light);
            transform: translateY(-1px);
        }
        .btn-sm-approved-done {
            background: #ecfdf5; color: #059669;
            border: 1.5px solid #6ee7b7; cursor: default;
        }
        .btn-sm-rejected-done {
            background: #fef2f2; color: #dc2626;
            border: 1.5px solid #fca5a5; cursor: default;
        }

        /* Empty state */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.1rem; font-weight: 700; color: var(--text-title); margin-bottom: 0.4rem; }
        .empty-state p  { font-size: 0.9rem; }

        /* Summary stats strip */
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .stat-tile {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-md);
            padding: 1.2rem 1.5rem;
            display: flex; flex-direction: column; gap: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
            transition: var(--transition-smooth);
        }
        .stat-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.05); }
        .stat-tile .stat-num {
            font-size: 2rem; font-weight: 800;
            color: var(--text-title); line-height: 1;
        }
        .stat-tile .stat-label {
            font-size: 0.78rem; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .stat-tile.tile-total  .stat-num { color: var(--primary-color); }
        .stat-tile.tile-pending  .stat-num { color: #f97316; }
        .stat-tile.tile-approved .stat-num { color: #10b981; }
        .stat-tile.tile-rejected .stat-num { color: #ef4444; }

        @media (max-width: 768px) {
            .stats-strip { grid-template-columns: repeat(2,1fr); }
            .title-row { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
        }
        @media (max-width: 480px) {
            .stats-strip { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="menu-backdrop" id="menuBackdrop"></div>

    <!-- Header -->
    <header class="dash-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="btnMenuToggle" aria-label="Toggle Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-logo"><span>Vendor</span>Bridge</div>
        </div>

        <?php
        $role_class  = 'color-a'; // admin is always blue here
        $logout_path = '../../../auth/logout.php';
        ?>
        <div class="header-profile-group">
            <?php if (isset($_SESSION['role'])): ?>
                <span class="avatar-initial <?= $role_class ?>" title="Role: <?= htmlspecialchars($_SESSION['role']) ?>"><?= strtoupper(substr($_SESSION['role'], 0, 1)) ?></span>
                <div class="user-avatar-circle" title="Logged in as: <?= htmlspecialchars($_SESSION['username']) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>
                <a href="<?= $logout_path ?>" title="Logout" style="text-decoration:none;">
                    <div class="user-avatar-circle" style="border-color:#ef4444;color:#ef4444;margin-left:0.25rem;">
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

        <!-- Sidebar (auto-populated by update_sidebars.php) -->
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

        <!-- Main Content -->
        <main class="app-content">

            <!-- Page Header -->
            <section class="content-header">
                <div class="title-row">
                    <div>
                        <h1 class="welcome-title">User Approvals</h1>
                        <p class="welcome-subtitle">Manage and approve vendor, officer, and manager accounts</p>
                    </div>
                    <div class="filter-tabs">
                        <a href="user_approvals.php?filter=all"      class="filter-tab <?= $filter === 'all'      ? 'active' : '' ?>">All      <span class="tab-count"><?= $counts['all'] ?></span></a>
                        <a href="user_approvals.php?filter=pending"  class="filter-tab <?= $filter === 'pending'  ? 'active' : '' ?>">⏳ Pending  <span class="tab-count"><?= $counts['pending'] ?></span></a>
                        <a href="user_approvals.php?filter=approved" class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">✅ Approved <span class="tab-count"><?= $counts['approved'] ?></span></a>
                        <a href="user_approvals.php?filter=rejected" class="filter-tab <?= $filter === 'rejected' ? 'active' : '' ?>">❌ Rejected <span class="tab-count"><?= $counts['rejected'] ?></span></a>
                    </div>
                </div>
            </section>

            <!-- Stats Strip -->
            <div class="stats-strip">
                <div class="stat-tile tile-total">
                    <span class="stat-num"><?= $counts['all'] ?></span>
                    <span class="stat-label">Total Accounts</span>
                </div>
                <div class="stat-tile tile-pending">
                    <span class="stat-num"><?= $counts['pending'] ?></span>
                    <span class="stat-label">Awaiting Review</span>
                </div>
                <div class="stat-tile tile-approved">
                    <span class="stat-num"><?= $counts['approved'] ?></span>
                    <span class="stat-label">Approved & Active</span>
                </div>
                <div class="stat-tile tile-rejected">
                    <span class="stat-num"><?= $counts['rejected'] ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u):
                                    // Avatar class based on role
                                    $av_class = 'avatar-vendor';
                                    if ($u['role'] === 'procurement_officer') $av_class = 'avatar-officer';
                                    elseif ($u['role'] === 'manager')         $av_class = 'avatar-manager';

                                    // Role pill class
                                    $role_pill = 'role-vendor';
                                    if ($u['role'] === 'procurement_officer') $role_pill = 'role-officer';
                                    elseif ($u['role'] === 'manager')         $role_pill = 'role-manager';

                                    // Role display name
                                    $role_label = ucfirst(str_replace('_', ' ', $u['role']));

                                    // Status badge
                                    $badge_class = 'badge-pending';
                                    if ($u['status'] === 'approved') $badge_class = 'badge-approved';
                                    elseif ($u['status'] === 'rejected') $badge_class = 'badge-rejected';

                                    $is_pending  = $u['status'] === 'pending';
                                    $is_approved = $u['status'] === 'approved';
                                    $is_rejected = $u['status'] === 'rejected';
                                ?>
                                <tr>
                                    <!-- User identity -->
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar <?= $av_class ?>"><?= strtoupper(substr($u['firstname'], 0, 1)) ?></div>
                                            <div>
                                                <div class="user-name"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></div>
                                                <div class="user-email">@<?= htmlspecialchars($u['username']) ?> &bull; <?= htmlspecialchars($u['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Role -->
                                    <td><span class="role-pill <?= $role_pill ?>"><?= htmlspecialchars($role_label) ?></span></td>
                                    <!-- Phone -->
                                    <td><?= htmlspecialchars($u['phone']) ?></td>
                                    <!-- Country -->
                                    <td><?= htmlspecialchars($u['country']) ?></td>
                                    <!-- Status -->
                                    <td><span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                                    <!-- Actions -->
                                    <td>
                                        <div class="action-group">
                                            <?php if ($is_pending || $is_rejected): ?>
                                                <form method="POST" action="user_approvals.php?filter=<?= $filter ?>" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn-sm btn-sm-approve" id="btnApprove<?= $u['id'] ?>" title="Approve this user">✓ Approve</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-sm btn-sm-approved-done" disabled>✓ Approved</button>
                                            <?php endif; ?>

                                            <?php if ($is_pending || $is_approved): ?>
                                                <form method="POST" action="user_approvals.php?filter=<?= $filter ?>" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-sm btn-sm-reject" id="btnReject<?= $u['id'] ?>" title="Reject this user" onclick="return confirm('Reject this user? They will no longer be able to log in.')">✕ Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-sm btn-sm-rejected-done" disabled>✕ Rejected</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon">👥</div>
                                            <h3>No users found</h3>
                                            <p>There are no accounts matching the "<?= htmlspecialchars($filter) ?>" filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnMenuToggle = document.getElementById('btnMenuToggle');
            const menuBackdrop  = document.getElementById('menuBackdrop');
            const body = document.body;

            if (btnMenuToggle && menuBackdrop) {
                const toggleMenu = (e) => { e.preventDefault(); body.classList.toggle('menu-open'); };
                btnMenuToggle.addEventListener('click', toggleMenu);
                menuBackdrop.addEventListener('click', toggleMenu);
                document.querySelectorAll('.sidebar-nav a').forEach(l => {
                    l.addEventListener('click', () => body.classList.remove('menu-open'));
                });
            }
        });
    </script>

    <?php if (isset($_SESSION['toast'])): ?>
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
