<?php
require_once '../../auth/session_helper.php';
require_once '../../config/connection.php';
requireRoles(['manager']);

$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending', 'history'])) $tab = 'pending';

// Count KPIs
$pending_res  = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status = 'L1_Reviewed'");
$pending_count = mysqli_fetch_assoc($pending_res)['cnt'] ?? 0;

$approved_res  = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status IN ('Approved','PO_Generated')");
$approved_count = mysqli_fetch_assoc($approved_res)['cnt'] ?? 0;

$rejected_res  = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quotations WHERE status = 'Rejected'");
$rejected_count = mysqli_fetch_assoc($rejected_res)['cnt'] ?? 0;

// Pending approvals
$pending_query = mysqli_query($conn, "
    SELECT q.id, q.grand_total, q.status, q.created_at, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname
    FROM quotations q
    JOIN rfqs r ON q.rfq_id = r.id
    JOIN users u ON q.vendor_id = u.id
    WHERE q.status = 'L1_Reviewed'
    ORDER BY q.created_at ASC
");

// History
$history_query = mysqli_query($conn, "
    SELECT q.id, q.grand_total, q.status, q.created_at, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname
    FROM quotations q
    JOIN rfqs r ON q.rfq_id = r.id
    JOIN users u ON q.vendor_id = u.id
    WHERE q.status IN ('Approved','PO_Generated','Rejected')
    ORDER BY q.created_at DESC
    LIMIT 20
");

// Handle manager approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $qid     = (int)($_POST['quote_id'] ?? 0);
    if ($qid > 0 && in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'Approved' : 'Rejected';
        $safe = mysqli_real_escape_string($conn, $new_status);
        mysqli_query($conn, "UPDATE quotations SET status = '$safe' WHERE id = $qid");
        $_SESSION['toast'] = [
            'type'    => 'success',
            'message' => "Quotation #$qid has been $new_status."
        ];
    }
    header('Location: managerDashboard.php?tab=' . ($action === 'approve' ? 'history' : 'pending'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge Manager portal - Review and approve procurement quotations.">
    <title>VendorBridge - Manager Portal</title>
    <link rel="stylesheet" href="managerDashboard.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        /* ── Tab navigation ── */
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--panel-border);
            padding-bottom: 0;
        }
        .tab-btn {
            padding: 0.65rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: var(--font-heading);
            cursor: pointer;
            background: none;
            border: none;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: var(--transition-smooth);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tab-btn:hover { color: var(--primary-color); }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-btn .count-badge {
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 50px;
            padding: 1px 7px;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .tab-btn.active .count-badge {
            background: var(--primary-color);
            color: #fff;
        }

        /* ── Content pane ── */
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Table card ── */
        .table-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
        }
        .table-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--panel-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-card-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-title);
        }
        .table-card-header p { font-size: 0.82rem; color: var(--text-muted); }

        /* ── Data table ── */
        .mgr-table { width: 100%; border-collapse: collapse; }
        .mgr-table th {
            padding: 0.85rem 1.25rem;
            font-size: 0.78rem; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.5px;
            background: #f8fafc;
            border-bottom: 1px solid var(--panel-border);
            text-align: left; white-space: nowrap;
        }
        .mgr-table td {
            padding: 1rem 1.25rem;
            font-size: 0.9rem;
            color: var(--text-body);
            border-bottom: 1px solid var(--panel-border);
            vertical-align: middle;
        }
        .mgr-table tr:last-child td { border-bottom: none; }
        .mgr-table tbody tr { transition: var(--transition-smooth); }
        .mgr-table tbody tr:hover { background: #f8fafc; }

        /* ── Status badge ── */
        .badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.25rem 0.65rem;
            border-radius: 50px; font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
        .badge-pending   { background: #fff7ed; color: #c2410c; }
        .badge-pending::before   { background: #f97316; }
        .badge-approved  { background: #ecfdf5; color: #065f46; }
        .badge-approved::before  { background: #10b981; }
        .badge-rejected  { background: #fef2f2; color: #991b1b; }
        .badge-rejected::before  { background: #ef4444; }
        .badge-po        { background: #eff6ff; color: #1d4ed8; }
        .badge-po::before        { background: #3b82f6; }

        /* ── Action buttons ── */
        .action-group { display: flex; gap: 0.5rem; }
        .btn-action {
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem; font-weight: 700;
            border-radius: var(--radius-sm);
            cursor: pointer; border: none;
            transition: var(--transition-smooth);
            display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .btn-approve {
            background: #10b981; color: #fff;
            box-shadow: 0 2px 6px rgba(16,185,129,0.2);
        }
        .btn-approve:hover {
            background: #059669; transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(16,185,129,0.3);
        }
        .btn-reject {
            background: #fff; color: #ef4444;
            border: 1.5px solid #ef4444;
        }
        .btn-reject:hover { background: #fef2f2; transform: translateY(-1px); }

        /* ── Empty state ── */
        .empty-state {
            padding: 3.5rem 2rem;
            text-align: center; color: var(--text-muted);
        }
        .empty-state .e-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .empty-state h3 { font-weight: 700; color: var(--text-title); margin-bottom: 0.3rem; }
        .empty-state p { font-size: 0.85rem; }

        /* ── KPI strip ── */
        .kpi-grid { grid-template-columns: repeat(3, 1fr) !important; }
        .kpi-card.kpi-pending .kpi-value   { color: #f97316; }
        .kpi-card.kpi-approved .kpi-value  { color: #10b981; }
        .kpi-card.kpi-rejected .kpi-value  { color: #ef4444; }
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
        $logout_path = '../../auth/logout.php';
        ?>
        <div class="header-profile-group">
            <?php if (isset($_SESSION['role'])): ?>
                <span class="avatar-initial color-m" title="Manager"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
                <div class="user-avatar-circle" title="<?= htmlspecialchars($_SESSION['username']) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <a href="<?= $logout_path ?>" title="Logout" style="text-decoration:none;">
                    <div class="user-avatar-circle" style="border-color:#ef4444;color:#ef4444;margin-left:0.25rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="app-layout">

        <!-- Manager sidebar: Dashboard + Change Password -->
        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="managerDashboard.php">
                            <span class="nav-icon">✅</span>
                            Approvals
                        </a>
                    </li>
                    <li>
                        <a href="../../auth/update_password.php">
                            <span class="nav-icon">🔑</span>
                            Update Password
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="app-content">

            <!-- Header -->
            <section class="content-header">
                <h1 class="welcome-title">Manager Dashboard</h1>
                <p class="welcome-subtitle">Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> — Procurement Approval Centre</p>
            </section>

            <!-- KPI strip -->
            <section class="kpi-grid">
                <div class="kpi-card kpi-pending">
                    <div class="kpi-value"><?= $pending_count ?></div>
                    <div class="kpi-label">Awaiting Review</div>
                </div>
                <div class="kpi-card kpi-approved">
                    <div class="kpi-value"><?= $approved_count ?></div>
                    <div class="kpi-label">Approved</div>
                </div>
                <div class="kpi-card kpi-rejected">
                    <div class="kpi-value"><?= $rejected_count ?></div>
                    <div class="kpi-label">Rejected</div>
                </div>
            </section>

            <!-- Tab Navigation -->
            <nav class="tab-nav">
                <a href="?tab=pending"
                   class="tab-btn <?= $tab === 'pending' ? 'active' : '' ?>">
                    ⏳ Pending Review
                    <span class="count-badge"><?= $pending_count ?></span>
                </a>
                <a href="?tab=history"
                   class="tab-btn <?= $tab === 'history' ? 'active' : '' ?>">
                    📋 Decision History
                    <span class="count-badge"><?= $approved_count + $rejected_count ?></span>
                </a>
            </nav>

            <!-- ── Tab: Pending ── -->
            <div class="tab-pane <?= $tab === 'pending' ? 'active' : '' ?>">
                <div class="table-card">
                    <div class="table-card-header">
                        <div>
                            <h2>Quotations Awaiting L2 Approval</h2>
                            <p>These quotations have been reviewed by the Procurement Officer and need your decision.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="mgr-table">
                            <thead>
                                <tr>
                                    <th>RFQ #</th>
                                    <th>Title</th>
                                    <th>Vendor</th>
                                    <th>Quote Value</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_query && mysqli_num_rows($pending_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($pending_query)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['rfq_title']) ?></td>
                                        <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                        <td><strong>₹<?= number_format($row['grand_total']) ?></strong></td>
                                        <td style="color:var(--text-muted);font-size:0.82rem;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <div class="action-group">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="quote_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn-action btn-approve" id="btnApprove<?= $row['id'] ?>">✓ Approve</button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="quote_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn-action btn-reject" id="btnReject<?= $row['id'] ?>"
                                                        onclick="return confirm('Reject this quotation?')">✕ Reject</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="6">
                                    <div class="empty-state">
                                        <div class="e-icon">🎉</div>
                                        <h3>All caught up!</h3>
                                        <p>No quotations are waiting for your approval right now.</p>
                                    </div>
                                </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Tab: History ── -->
            <div class="tab-pane <?= $tab === 'history' ? 'active' : '' ?>">
                <div class="table-card">
                    <div class="table-card-header">
                        <div>
                            <h2>Decision History</h2>
                            <p>Quotations you have approved or rejected.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="mgr-table">
                            <thead>
                                <tr>
                                    <th>RFQ #</th>
                                    <th>Title</th>
                                    <th>Vendor</th>
                                    <th>Value</th>
                                    <th>Decision</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history_query && mysqli_num_rows($history_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($history_query)):
                                        $badge = 'badge-approved';
                                        $label = 'Approved';
                                        if ($row['status'] === 'Rejected') { $badge = 'badge-rejected'; $label = 'Rejected'; }
                                        elseif ($row['status'] === 'PO_Generated') { $badge = 'badge-po'; $label = 'PO Generated'; }
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['rfq_title']) ?></td>
                                        <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                        <td>₹<?= number_format($row['grand_total']) ?></td>
                                        <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                                        <td style="color:var(--text-muted);font-size:0.82rem;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="6">
                                    <div class="empty-state">
                                        <div class="e-icon">📋</div>
                                        <h3>No decisions yet</h3>
                                        <p>Your approval history will appear here.</p>
                                    </div>
                                </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btnMenuToggle');
            const bd  = document.getElementById('menuBackdrop');
            const body = document.body;
            if (btn && bd) {
                const toggle = e => { e.preventDefault(); body.classList.toggle('menu-open'); };
                btn.addEventListener('click', toggle);
                bd.addEventListener('click', toggle);
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.addEventListener('click', () => body.classList.remove('menu-open')));
            }
        });
    </script>

    <?php if (isset($_SESSION['toast'])): ?>
    <script>showToast(<?= json_encode($_SESSION['toast']['message']) ?>, <?= json_encode($_SESSION['toast']['type']) ?>);</script>
    <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
</body>
</html>
