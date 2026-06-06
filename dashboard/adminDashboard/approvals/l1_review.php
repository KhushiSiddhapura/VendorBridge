<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
requireRoles(['admin', 'procurement_officer']);

$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;
if ($quote_id === 0) {
    header("Location: ../quotations/quotations.php");
    exit();
}

// Fetch quotation details
$sql = "SELECT q.*, r.rfq_number, r.title as rfq_title, u.firstname, u.lastname, u.email 
        FROM quotations q 
        JOIN rfqs r ON q.rfq_id = r.id 
        JOIN users u ON q.vendor_id = u.id 
        WHERE q.id = $quote_id";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    die("Quotation not found.");
}
$quote = mysqli_fetch_assoc($result);

// POST processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $user_id = $_SESSION['id'];
    
    if ($action === 'verify') {
        $chkSpecs = isset($_POST['chkSpecs']) ? 1 : 0;
        $chkPrice = isset($_POST['chkPrice']) ? 1 : 0;
        $chkTax = isset($_POST['chkTax']) ? 1 : 0;
        $chkDelivery = isset($_POST['chkDelivery']) ? 1 : 0;
        
        $update_sql = "UPDATE quotations SET 
            status = 'L1_Reviewed',
            l1_remarks = '$remarks',
            l1_checked_specs = $chkSpecs,
            l1_checked_price = $chkPrice,
            l1_checked_tax = $chkTax,
            l1_checked_delivery = $chkDelivery
            WHERE id = $quote_id";
            
        if (mysqli_query($conn, $update_sql)) {
            // Log activity
            $log_desc = "Quotation #$quote_id from " . $quote['firstname'] . " " . $quote['lastname'] . " for RFQ " . $quote['rfq_number'] . " verified and sent to L2.";
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'QUOTE_L1_VERIFIED', '$log_desc')");
            
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'L1 Review Complete! Forwarded for L2 approval.'
            ];
            header("Location: approvals.php?quote_id=$quote_id");
            exit();
        }
    } elseif ($action === 'reject') {
        if (empty($remarks)) {
            $_SESSION['toast'] = [
                'type' => 'fail',
                'message' => 'Remarks are required for rejection.'
            ];
            header("Location: l1_review.php?quote_id=$quote_id");
            exit();
        }
        
        $update_sql = "UPDATE quotations SET status = 'Rejected', l1_remarks = '$remarks' WHERE id = $quote_id";
        if (mysqli_query($conn, $update_sql)) {
            $log_desc = "Quotation #$quote_id rejected during L1 Review. Remarks: $remarks";
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'QUOTE_L1_REJECTED', '$log_desc')");
            
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Quotation rejected and sent back to vendor.'
            ];
            header("Location: ../quotations/quotations.php");
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
    <meta name="description" content="VendorBridge portal L1 review workflow. Review received quotations, complete checklist verification, and submit for L2 approval.">
    <title>VendorBridge - L1 Review</title>
    <link rel="stylesheet" href="l1_review.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
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
            
            <!-- Welcome Header -->
            <section class="content-header">
                <h1 class="welcome-title">L1 Review</h1>
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
                        <div class="step-item current">
                            <div class="step-icon">2</div>
                            <span class="step-label">L1 Review</span>
                        </div>
                        <div class="step-line"></div>
                        <!-- Step 3 -->
                        <div class="step-item">
                            <div class="step-icon">3</div>
                            <span class="step-label">L2 approval</span>
                        </div>
                        <div class="step-line"></div>
                        <!-- Step 4 -->
                        <div class="step-item">
                            <div class="step-icon">4</div>
                            <span class="step-label">Generate PO</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Form submission wrapper -->
            <form id="l1ReviewForm" method="POST" action="l1_review.php?quote_id=<?= $quote_id ?>">
                <input type="hidden" name="action" id="formAction" value="verify">
                
                <!-- Grid Layout Split -->
                <div class="approvals-grid">
                    
                    <!-- Left Column: Verification Checklist and Remarks -->
                    <div class="grid-left-col">
                        
                        <!-- Checklist card -->
                        <div class="panel-card checklist-card">
                            <h2 class="panel-card-title">Verification Checklist</h2>
                            <div class="checklist-container">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="chkSpecs" id="chkSpecs" class="custom-checkbox" value="1" <?= $quote['l1_checked_specs'] ? 'checked' : '' ?>>
                                    <span class="checkbox-custom-box"></span>
                                    <span class="checkbox-text">Specifications verified and compliant</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="chkPrice" id="chkPrice" class="custom-checkbox" value="1" <?= $quote['l1_checked_price'] ? 'checked' : '' ?>>
                                    <span class="checkbox-custom-box"></span>
                                    <span class="checkbox-text">Pricing matches quote records</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="chkTax" id="chkTax" class="custom-checkbox" value="1" <?= $quote['l1_checked_tax'] ? 'checked' : '' ?>>
                                    <span class="checkbox-custom-box"></span>
                                    <span class="checkbox-text">Tax registration/GST verified</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="chkDelivery" id="chkDelivery" class="custom-checkbox" value="1" <?= $quote['l1_checked_delivery'] ? 'checked' : '' ?>>
                                    <span class="checkbox-custom-box"></span>
                                    <span class="checkbox-text">Delivery timeline accepted</span>
                                </label>
                            </div>
                        </div>

                        <!-- Review Remarks Box -->
                        <div class="panel-card remarks-card">
                            <h2 class="panel-card-title">Review Remarks</h2>
                            <div class="form-group">
                                <label class="form-label" for="txtRemarks">Add your comments or conditions</label>
                                <textarea class="form-control textarea-control" name="remarks" id="txtRemarks" placeholder="Enter comments or remarks..."><?= htmlspecialchars($quote['l1_remarks'] ?? '') ?></textarea>
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
                            <button type="submit" class="btn btn-approve" id="btnVerify">Verify & Send to L2</button>
                            <button type="submit" class="btn btn-reject" id="btnReject">Reject & Re-quote</button>
                        </div>

                    </div>

                </div>
            </form>

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

            const btnVerify = document.getElementById('btnVerify');
            const btnReject = document.getElementById('btnReject');
            const formAction = document.getElementById('formAction');
            const txtRemarks = document.getElementById('txtRemarks');
            
            const checkboxes = [
                document.getElementById('chkSpecs'),
                document.getElementById('chkPrice'),
                document.getElementById('chkTax'),
                document.getElementById('chkDelivery')
            ];

            btnVerify.addEventListener('click', (e) => {
                const allChecked = checkboxes.every(cb => cb && cb.checked);
                if (!allChecked) {
                    e.preventDefault();
                    alert("Please verify and tick all items in the checklist before sending to L2 approval.");
                    return;
                }
                formAction.value = 'verify';
            });

            btnReject.addEventListener('click', (e) => {
                const remarks = txtRemarks.value.trim();
                if (!remarks) {
                    e.preventDefault();
                    alert("Please enter remarks explaining the rejection reason before rejecting the quote.");
                    txtRemarks.focus();
                    return;
                }
                formAction.value = 'reject';
            });
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
