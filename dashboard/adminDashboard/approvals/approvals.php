<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';

// Allows admin, procurement officer (read-only), and manager
requireRoles(['admin', 'procurement_officer', 'manager']);

$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;
$quote = null;
$approvals_list = [];

if ($quote_id > 0) {
    // Fetch quotation details
    $sql = "SELECT q.*, r.rfq_number, r.title as rfq_title, r.id as rfq_db_id, u.firstname, u.lastname, u.email 
            FROM quotations q 
            JOIN rfqs r ON q.rfq_id = r.id 
            JOIN users u ON q.vendor_id = u.id 
            WHERE q.id = $quote_id";
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        die("Quotation not found.");
    }
    $quote = mysqli_fetch_assoc($result);
    
    // Fetch L1 auditor details from activity logs
    $l1_auditor = "Procurement Team";
    $l1_time = "N/A";
    $l1_log_res = mysqli_query($conn, "SELECT a.*, u.firstname, u.lastname FROM activity_logs a JOIN users u ON a.user_id = u.id WHERE a.activity_type = 'QUOTE_L1_VERIFIED' AND a.description LIKE '%#$quote_id%' LIMIT 1");
    if ($l1_log_res && mysqli_num_rows($l1_log_res) > 0) {
        $l1_log = mysqli_fetch_assoc($l1_log_res);
        $l1_auditor = htmlspecialchars($l1_log['firstname'] . ' ' . $l1_log['lastname']);
        $l1_time = date('M d, h:i A', strtotime($l1_log['created_at']));
    }

    // Fetch L2 manager details
    $l2_auditor = "Finance Manager";
    $l2_time = "";
    if ($quote['status'] === 'Approved') {
        $l2_log_res = mysqli_query($conn, "SELECT a.*, u.firstname, u.lastname FROM activity_logs a JOIN users u ON a.user_id = u.id WHERE a.activity_type = 'QUOTE_L2_APPROVED' AND a.description LIKE '%#$quote_id%' LIMIT 1");
        if ($l2_log_res && mysqli_num_rows($l2_log_res) > 0) {
            $l2_log = mysqli_fetch_assoc($l2_log_res);
            $l2_auditor = htmlspecialchars($l2_log['firstname'] . ' ' . $l2_log['lastname']);
            $l2_time = date('M d, h:i A', strtotime($l2_log['created_at']));
        }
    }
} else {
    // Fetch approvals list (all quotes awaiting L2, or already approved)
    $sql = "SELECT q.*, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname 
            FROM quotations q 
            JOIN rfqs r ON q.rfq_id = r.id 
            JOIN users u ON q.vendor_id = u.id 
            WHERE q.status IN ('L1_Reviewed', 'Approved', 'Rejected') 
            ORDER BY q.created_at DESC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $approvals_list[] = $row;
    }
}

// POST processing (for Manager / Admin approvals)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quote_id > 0) {
    // Secure to manager or admin only for actions
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Unauthorized action. Only Managers/Admins can process approvals.'
        ];
        header("Location: approvals.php?quote_id=$quote_id");
        exit();
    }
    
    $action = $_POST['action'] ?? '';
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $user_id = $_SESSION['id'];
    
    if ($action === 'approve') {
        $update_sql = "UPDATE quotations SET status = 'Approved', l2_remarks = '$remarks' WHERE id = $quote_id";
        if (mysqli_query($conn, $update_sql)) {
            $log_desc = "Quotation #$quote_id approved by L2 manager. Remarks: $remarks";
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'QUOTE_L2_APPROVED', '$log_desc')");
            
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Quotation approved successfully! Ready for Purchase Order generation.'
            ];
            header("Location: ../purchase_orders/generate_po.php?quote_id=$quote_id");
            exit();
        }
    } elseif ($action === 'reject') {
        if (empty($remarks)) {
            $_SESSION['toast'] = [
                'type' => 'fail',
                'message' => 'Remarks are required for rejection.'
            ];
            header("Location: approvals.php?quote_id=$quote_id");
            exit();
        }
        
        $update_sql = "UPDATE quotations SET status = 'Rejected', l2_remarks = '$remarks' WHERE id = $quote_id";
        if (mysqli_query($conn, $update_sql)) {
            $log_desc = "Quotation #$quote_id rejected by L2 manager. Remarks: $remarks";
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'QUOTE_L2_REJECTED', '$log_desc')");
            
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Quotation rejected.'
            ];
            header("Location: approvals.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal approval workflow. Review received quotations, track approvals, and proceed to PO generation.">
    <title>VendorBridge - Approval Workflow</title>
    <link rel="stylesheet" href="approvals.css">
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
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-l1 { background-color: #fef3c7; color: #d97706; }
        .badge-approved { background-color: #ecfdf5; color: #059669; }
        .badge-rejected { background-color: #fee2e2; color: #dc2626; }
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

        <!-- Main Content Panel -->
        <main class="app-content">
            
            <?php if ($quote): 
                $is_approved = ($quote['status'] === 'Approved');
                $is_rejected = ($quote['status'] === 'Rejected');
                $is_pending = ($quote['status'] === 'L1_Reviewed');
            ?>
                <!-- Welcome Header -->
                <section class="content-header">
                    <h1 class="welcome-title">Approval Workflow</h1>
                    <p class="welcome-subtitle">RFQ: <?= htmlspecialchars($quote['rfq_number']) ?> - <?= htmlspecialchars($quote['rfq_title']) ?> &bull; Vendor: <?= htmlspecialchars($quote['firstname'] . ' ' . $quote['lastname']) ?></p>
                </section>

                <!-- Stepper Progress Card -->
                <section class="stepper-section">
                    <div class="stepper-card">
                        <div class="stepper-wrapper">
                            <!-- Step 1 -->
                            <div class="step-item completed">
                                <div class="step-icon">✓</div>
                                <span class="step-label">Submitted</span>
                            </div>
                            <div class="step-line active"></div>
                            <!-- Step 2 -->
                            <div class="step-item completed">
                                <div class="step-icon">✓</div>
                                <span class="step-label">L1 Review</span>
                            </div>
                            <div class="step-line active"></div>
                            <!-- Step 3 -->
                            <div class="step-item <?= $is_approved ? 'completed' : 'current' ?>">
                                <div class="step-icon"><?= $is_approved ? '✓' : '3' ?></div>
                                <span class="step-label">L2 approval</span>
                            </div>
                            <div class="step-line <?= $is_approved ? 'active' : '' ?>"></div>
                            <!-- Step 4 -->
                            <div class="step-item <?= ($quote['status'] === 'PO_Generated') ? 'completed' : '' ?>">
                                <div class="step-icon">4</div>
                                <span class="step-label">Generate PO</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Form wrapper -->
                <form id="approvalForm" method="POST" action="approvals.php?quote_id=<?= $quote_id ?>">
                    <input type="hidden" name="action" id="formAction" value="approve">
                    
                    <!-- Grid Layout Split -->
                    <div class="approvals-grid">
                        
                        <!-- Left Column: Approval Chain and Remarks -->
                        <div class="grid-left-col">
                            
                            <!-- Approval Chain timeline card -->
                            <div class="panel-card timeline-card">
                                <h2 class="panel-card-title">Approval Chain</h2>
                                <div class="timeline-container">
                                    <!-- L1 (Approved always because we are at L2 now) -->
                                    <div class="timeline-item approved">
                                        <div class="timeline-status">
                                            <div class="status-circle">✓</div>
                                        </div>
                                        <div class="timeline-content">
                                            <h3 class="approver-name"><?= $l1_auditor ?></h3>
                                            <span class="approver-role">Procurement head</span>
                                            <p class="status-time">Approved L1 on <?= $l1_time ?></p>
                                            <?php if (!empty($quote['l1_remarks'])): ?>
                                                <p style="font-style:italic; font-size:0.85rem; color:var(--text-muted); margin-top:4px;">"<?= htmlspecialchars($quote['l1_remarks']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- L2 (Awaiting or Approved) -->
                                    <?php if ($is_approved): ?>
                                        <div class="timeline-item approved">
                                            <div class="timeline-status">
                                                <div class="status-circle">✓</div>
                                            </div>
                                            <div class="timeline-content">
                                                <h3 class="approver-name"><?= $l2_auditor ?></h3>
                                                <span class="approver-role">Finance Manager</span>
                                                <p class="status-time">Approved L2 on <?= $l2_time ?></p>
                                                <?php if (!empty($quote['l2_remarks'])): ?>
                                                    <p style="font-style:italic; font-size:0.85rem; color:var(--text-muted); margin-top:4px;">"<?= htmlspecialchars($quote['l2_remarks']) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif ($is_rejected): ?>
                                        <div class="timeline-item rejected" style="border-left: 2px solid #ef4444;">
                                            <div class="timeline-status">
                                                <div class="status-circle" style="background:#ef4444; color:#fff;">✗</div>
                                            </div>
                                            <div class="timeline-content">
                                                <h3 class="approver-name">Finance Manager</h3>
                                                <span class="approver-role">Finance Manager</span>
                                                <p class="status-time" style="color:#ef4444;">Rejected L2</p>
                                                <?php if (!empty($quote['l2_remarks'])): ?>
                                                    <p style="font-style:italic; font-size:0.85rem; color:var(--text-muted); margin-top:4px;">"<?= htmlspecialchars($quote['l2_remarks']) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline-item awaiting">
                                            <div class="timeline-status">
                                                <div class="status-circle">🕒</div>
                                            </div>
                                            <div class="timeline-content">
                                                <h3 class="approver-name">Finance Manager</h3>
                                                <span class="approver-role">Finance manager</span>
                                                <p class="status-status">Awaiting Approval</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Approval Remarks Box -->
                            <div class="panel-card remarks-card">
                                <h2 class="panel-card-title">Approval Remarks</h2>
                                <div class="form-group">
                                    <label class="form-label" for="txtRemarks">Add your comments or conditions</label>
                                    <textarea class="form-control textarea-control" name="remarks" id="txtRemarks" placeholder="Enter comments or remarks..." <?= !$is_pending ? 'disabled' : '' ?>><?= htmlspecialchars($quote['l2_remarks'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                        </div>

                        <!-- Right Column: Quotations Summary and Action Buttons -->
                        <div class="grid-right-col">
                            
                            <!-- Quotation Summary Card -->
                            <div class="panel-card summary-card">
                                <h2 class="panel-card-title">Quotations Summary</h2>
                                <div class="summary-details">
                                    <div class="summary-row">
                                        <span class="summary-key">Vendor:</span>
                                        <span class="summary-val vendor-highlight"><?= htmlspecialchars($quote['firstname'] . ' ' . $quote['lastname']) ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-key">Total:</span>
                                        <span class="summary-val price-text">₹ <?= number_format($quote['grand_total']) ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-key">Delivery:</span>
                                        <span class="summary-val"><?= htmlspecialchars($quote['delivery_days']) ?> days</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-key">Rating:</span>
                                        <span class="summary-val"><span class="score-pill">4.5/5</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Approval Actions Box -->
                            <div class="actions-card">
                                <?php if ($is_pending): ?>
                                    <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                                        <button type="submit" class="btn btn-approve" id="btnApprove">Approve</button>
                                        <button type="submit" class="btn btn-reject" id="btnReject">Reject</button>
                                    <?php else: ?>
                                        <p style="color:var(--text-muted); font-size:0.9rem; text-align:center;">Read-Only: Awaiting Manager approval.</p>
                                    <?php endif; ?>
                                <?php elseif ($is_approved): ?>
                                    <button type="button" class="btn btn-approve" onclick="window.location.href='../purchase_orders/generate_po.php?quote_id=<?= $quote_id ?>'">Proceed to PO</button>
                                <?php else: ?>
                                    <p style="color:#dc2626; font-weight:600; text-align:center;">This quotation was rejected.</p>
                                    <button type="button" class="btn btn-outline" onclick="window.location.href='approvals.php'">Back to Approvals</button>
                                <?php endif; ?>
                            </div>

                        </div>

                    </div>
                </form>
            
            <?php else: ?>
                <!-- Awaiting Approvals Directory -->
                <section class="content-header">
                    <h1 class="welcome-title">Approvals Directory</h1>
                    <p class="welcome-subtitle">Review verification chains and authorize L2 approvals</p>
                </section>
                
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="rfq-select-table">
                            <thead>
                                <tr>
                                    <th>RFQ Number</th>
                                    <th>RFQ Title</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($approvals_list)): ?>
                                    <?php foreach ($approvals_list as $row): 
                                        if ($row['status'] === 'L1_Reviewed') {
                                            $badge_class = 'badge-l1';
                                            $status_text = 'Awaiting L2';
                                        } elseif ($row['status'] === 'Approved') {
                                            $badge_class = 'badge-approved';
                                            $status_text = 'Approved';
                                        } else {
                                            $badge_class = 'badge-rejected';
                                            $status_text = 'Rejected';
                                        }
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['rfq_title']) ?></td>
                                            <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                            <td>₹ <?= number_format($row['grand_total']) ?></td>
                                            <td><span class="badge <?= $badge_class ?>"><?= $status_text ?></span></td>
                                            <td>
                                                <a href="approvals.php?quote_id=<?= $row['id'] ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block; font-size: 0.85rem; padding: 6px 12px;">Open Workflow</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                            No active approvals logged in system database.
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

    <!-- Toggle Navigation & Action Scripts -->
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

            const btnApprove = document.getElementById('btnApprove');
            const btnReject = document.getElementById('btnReject');
            const formAction = document.getElementById('formAction');
            const txtRemarks = document.getElementById('txtRemarks');

            if (btnApprove) {
                btnApprove.addEventListener('click', () => {
                    formAction.value = 'approve';
                });
            }

            if (btnReject) {
                btnReject.addEventListener('click', (e) => {
                    const remarks = txtRemarks.value.trim();
                    if (!remarks) {
                        e.preventDefault();
                        alert("Please enter remarks before rejecting the quotation.");
                        txtRemarks.focus();
                        return;
                    }
                    formAction.value = 'reject';
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
