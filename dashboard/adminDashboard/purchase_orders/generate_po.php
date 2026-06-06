<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
require_once '../../../services/mailService.php';

requireRoles(['admin', 'procurement_officer']);

$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;
if ($quote_id === 0) {
    header("Location: ../quotations/quotations.php");
    exit();
}

// Fetch quotation details
$sql = "SELECT q.*, r.rfq_number, r.title as rfq_title, r.items, r.quantities, r.units, r.id as rfq_db_id, u.firstname, u.lastname, u.email 
        FROM quotations q 
        JOIN rfqs r ON q.rfq_id = r.id 
        JOIN users u ON q.vendor_id = u.id 
        WHERE q.id = $quote_id";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    die("Quotation not found.");
}
$quote = mysqli_fetch_assoc($result);

// Check if PO already exists
$po_check = mysqli_query($conn, "SELECT * FROM purchase_orders WHERE quotation_id = $quote_id");
$existing_po = mysqli_fetch_assoc($po_check);

if ($existing_po) {
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'PO has already been generated for this quotation.'
    ];
    header("Location: po_list.php");
    exit();
}

if ($quote['status'] !== 'Approved') {
    $_SESSION['toast'] = [
        'type' => 'fail',
        'message' => 'Only L2 approved quotations can generate a Purchase Order.'
    ];
    header("Location: ../approvals/approvals.php?quote_id=$quote_id");
    exit();
}

// Parse items and prices
$items = json_decode($quote['items'], true) ?: [];
$quantities = json_decode($quote['quantities'], true) ?: [];
$units = json_decode($quote['units'], true) ?: [];
$prices = json_decode($quote['items_pricing'], true) ?: [];

$subtotal = 0;
$line_items = [];
for ($i = 0; $i < count($items); $i++) {
    $qty = (int)($quantities[$i] ?? 0);
    $price = (float)($prices[$i] ?? 0);
    $total = $qty * $price;
    $subtotal += $total;
    
    $line_items[] = [
        'name' => $items[$i],
        'qty' => $qty,
        'unit' => $units[$i] ?? 'NOS',
        'price' => $price,
        'total' => $total
    ];
}

$gst_percent = (float)$quote['gst_percent'];
$tax_amount = $subtotal * ($gst_percent / 100);
$grand_total = $subtotal + $tax_amount;

// Auto-generated PO Number
$po_number = 'PO-' . date('Y') . '-' . rand(1000, 9999);

// POST Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billing_val = $_POST['billing_address'] ?? '';
    $shipping_val = $_POST['shipping_address'] ?? '';
    $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms'] ?? '');
    $shipping_method = mysqli_real_escape_string($conn, $_POST['shipping_method'] ?? '');
    
    // Address mappings
    $billing_addresses = [
        'HQ' => 'Headquarters - New Delhi (GST: 07AAAAB1234C1Z1)',
        'Sourcing' => 'Sourcing Division - Bengaluru (GST: 29AAAAB1234C1Z2)',
        'Branch' => 'Branch Office - Mumbai (GST: 27AAAAB1234C1Z3)'
    ];
    
    $shipping_addresses = [
        'WH' => 'Procurement Hub - Warehouse 4 (Bengaluru)',
        'HQ' => 'Corporate HQ (New Delhi)',
        'Pune' => 'Development Center (Pune)'
    ];
    
    $billing_address = $billing_addresses[$billing_val] ?? $billing_val;
    $shipping_address = $shipping_addresses[$shipping_val] ?? $shipping_val;
    
    $billing_address_esc = mysqli_real_escape_string($conn, $billing_address);
    $shipping_address_esc = mysqli_real_escape_string($conn, $shipping_address);
    
    // Insert into purchase_orders
    $insert_po = "INSERT INTO purchase_orders (po_number, rfq_id, quotation_id, billing_address, shipping_address, payment_terms, shipping_method, subtotal, tax_amount, grand_total, status) 
                  VALUES ('$po_number', {$quote['rfq_db_id']}, $quote_id, '$billing_address_esc', '$shipping_address_esc', '$payment_terms', '$shipping_method', $subtotal, $tax_amount, $grand_total, 'Pending')";
                  
    if (mysqli_query($conn, $insert_po)) {
        $po_id = mysqli_insert_id($conn);
        
        // Auto-generate Invoice
        $invoice_number = 'INV-' . date('Y') . '-' . rand(1000, 9999);
        $insert_invoice = "INSERT INTO invoices (invoice_number, po_id, billing_address, shipping_address, subtotal, tax_amount, grand_total, status) 
                           VALUES ('$invoice_number', $po_id, '$billing_address_esc', '$shipping_address_esc', $subtotal, $tax_amount, $grand_total, 'Unpaid')";
        mysqli_query($conn, $insert_invoice);
        
        // Update quotation and RFQ status
        mysqli_query($conn, "UPDATE quotations SET status = 'PO_Generated' WHERE id = $quote_id");
        mysqli_query($conn, "UPDATE rfqs SET status = 'PO_Generated' WHERE id = {$quote['rfq_db_id']}");
        
        // Log activity
        $user_id = $_SESSION['id'];
        $log_desc = "Purchase Order $po_number and Invoice $invoice_number generated for approved quote #$quote_id.";
        mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'PO_GENERATED', '$log_desc')");
        
        // Notify vendor via email (PHPMailer configuration in config/mail.php will run)
        // Let's call a custom sendInvoiceMail function which we will add to mailService.php
        $vendor_name = $quote['firstname'] . ' ' . $quote['lastname'];
        $vendor_email = $quote['email'];
        
        // Send email with credentials/invoice info
        if (function_exists('sendPOMail')) {
            sendPOMail($vendor_email, $quote['firstname'], $po_number, $invoice_number, $grand_total);
        }
        
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => "PO $po_number generated and Invoice $invoice_number created successfully."
        ];
        header("Location: po_list.php");
        exit();
    } else {
        die("Error generating Purchase Order: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal PO generation. Configure addresses, payment terms, review breakdown calculations, and generate the final Purchase Order.">
    <title>VendorBridge - Generate PO</title>
    <link rel="stylesheet" href="generate_po.css">
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
                <h1 class="welcome-title">Generate PO</h1>
                <p class="welcome-subtitle">RFQ: <?= htmlspecialchars($quote['rfq_number']) ?> - Approved Vendor: <?= htmlspecialchars($quote['firstname'] . ' ' . $quote['lastname']) ?></p>
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
                        <div class="step-item completed">
                            <div class="step-icon">✓</div>
                            <span class="step-label">L2 approval</span>
                        </div>
                        <div class="step-line active"></div>
                        <!-- Step 4 -->
                        <div class="step-item current">
                            <div class="step-icon">4</div>
                            <span class="step-label">Generate PO</span>
                        </div>
                    </div>
                </div>
            </section>

            <form id="poConfigForm" method="POST" action="generate_po.php?quote_id=<?= $quote_id ?>">
                <!-- Grid Layout Split -->
                <div class="approvals-grid">
                    
                    <!-- Left Column: PO Configuration Details Form -->
                    <div class="grid-left-col">
                        
                        <!-- PO Details Form Card -->
                        <div class="panel-card po-form-card">
                            <h2 class="panel-card-title">PO Configuration</h2>
                            <div class="form-group">
                                <label class="form-label" for="txtPONumber">PO Number</label>
                                <input type="text" class="form-control read-only-field" id="txtPONumber" value="<?= $po_number ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="selBilling">Billing Address*</label>
                                <select class="form-control" name="billing_address" id="selBilling" required>
                                    <option value="HQ">Headquarters - New Delhi (GST: 07AAAAB1234C1Z1)</option>
                                    <option value="Sourcing">Sourcing Division - Bengaluru (GST: 29AAAAB1234C1Z2)</option>
                                    <option value="Branch">Branch Office - Mumbai (GST: 27AAAAB1234C1Z3)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="selShipping">Shipping Address*</label>
                                <select class="form-control" name="shipping_address" id="selShipping" required>
                                    <option value="WH">Procurement Hub - Warehouse 4 (Bengaluru)</option>
                                    <option value="HQ">Corporate HQ (New Delhi)</option>
                                    <option value="Pune">Development Center (Pune)</option>
                                </select>
                            </div>
                            
                            <div class="form-row-split">
                                <div class="form-group">
                                    <label class="form-label" for="txtTerms">Payment Terms</label>
                                    <input type="text" class="form-control" name="payment_terms" id="txtTerms" value="<?= htmlspecialchars($quote['payment_terms']) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="selShippingMethod">Shipping Method</label>
                                    <select class="form-control" name="shipping_method" id="selShippingMethod">
                                        <option value="Road">Road Cargo Transport</option>
                                        <option value="Air">Air Freight Express</option>
                                        <option value="Courier">Local Courier Services</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                    </div>

                    <!-- Right Column: Quotations Summary and Action Buttons -->
                    <div class="grid-right-col">
                        
                        <!-- PO Line Items Summary Card -->
                        <div class="panel-card summary-card">
                            <h2 class="panel-card-title">PO Line Items Summary</h2>
                            <div class="po-lines-list">
                                <?php foreach ($line_items as $item): ?>
                                    <div class="po-line-item">
                                        <div class="line-item-desc">
                                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                            <span class="item-meta">Qty: <?= htmlspecialchars($item['qty']) ?> | <?= htmlspecialchars($item['unit']) ?> @ ₹<?= number_format($item['price']) ?></span>
                                        </div>
                                        <span class="line-item-price">₹ <?= number_format($item['total']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="price-breakdown">
                                <div class="breakdown-row">
                                    <span class="breakdown-key">Base Subtotal</span>
                                    <span class="breakdown-val">₹ <?= number_format($subtotal) ?></span>
                                </div>
                                <div class="breakdown-row">
                                    <span class="breakdown-key">GST (<?= $gst_percent ?>%)</span>
                                    <span class="breakdown-val">₹ <?= number_format($tax_amount) ?></span>
                                </div>
                                <div class="breakdown-row grand-total-row">
                                    <span class="breakdown-key">Grand Total</span>
                                    <span class="breakdown-val price-text">₹ <?= number_format($grand_total) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Actions Box -->
                        <div class="actions-card">
                            <button type="submit" class="btn btn-approve" id="btnGeneratePO">Generate & Send PO</button>
                            <button type="button" class="btn btn-reject" id="btnCancel">Cancel</button>
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

            const btnCancel = document.getElementById('btnCancel');
            if (btnCancel) {
                btnCancel.addEventListener('click', () => {
                    if (confirm("Are you sure you want to cancel PO generation? Any changes will be lost.")) {
                        window.location.href = '../approvals/approvals.php';
                    }
                });
            }
        });
    </script>
</body>
</html>
