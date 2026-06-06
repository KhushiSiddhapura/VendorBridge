<?php
require_once '../../../auth/session_helper.php';
require_once '../../../config/connection.php';
require_once '../../../services/mailService.php';

// Allows admin, procurement officer, and vendor
requireRoles(['admin', 'procurement_officer', 'vendor']);

$where = "";
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $where = "i.id = $id";
} elseif (isset($_GET['po_id'])) {
    $po_id = (int)$_GET['po_id'];
    $where = "po.id = $po_id";
} else {
    // Redirect based on role
    if ($_SESSION['role'] === 'vendor') {
        header("Location: ../../vendorDashboard/vendorDashboard.php");
    } else {
        header("Location: invoices.php");
    }
    exit();
}

$sql = "SELECT i.*, po.po_number, po.payment_terms, po.shipping_method, r.rfq_number, r.title as rfq_title, r.items, r.quantities, r.units, q.items_pricing, q.vendor_id, u.firstname, u.lastname, u.email, u.phone, u.country
        FROM invoices i
        JOIN purchase_orders po ON i.po_id = po.id
        JOIN quotations q ON po.quotation_id = q.id
        JOIN rfqs r ON q.rfq_id = r.id
        JOIN users u ON q.vendor_id = u.id
        WHERE $where LIMIT 1";

$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    die("Invoice not found.");
}
$invoice = mysqli_fetch_assoc($result);

// Secure access for vendors
if ($_SESSION['role'] === 'vendor' && $invoice['vendor_id'] != $_SESSION['id']) {
    die("Access Denied: You cannot view invoices belonging to other vendors.");
}

// Parse items and prices
$items = json_decode($invoice['items'], true) ?: [];
$quantities = json_decode($invoice['quantities'], true) ?: [];
$units = json_decode($invoice['units'], true) ?: [];
$prices = json_decode($invoice['items_pricing'], true) ?: [];

$line_items = [];
for ($i = 0; $i < count($items); $i++) {
    $qty = (int)($quantities[$i] ?? 0);
    $price = (float)($prices[$i] ?? 0);
    $total = $qty * $price;
    $line_items[] = [
        'name' => $items[$i],
        'qty' => $qty,
        'unit' => $units[$i] ?? 'NOS',
        'price' => $price,
        'total' => $total
    ];
}

// Handle email trigger
$email_sent = false;
$email_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $vendor_email = $invoice['email'];
    $vendor_first = $invoice['firstname'];
    
    $sent = sendInvoiceMail(
        $vendor_email,
        $vendor_first,
        $invoice['invoice_number'],
        $invoice['po_number'],
        (float)$invoice['grand_total'],
        (float)$invoice['subtotal'],
        (float)$invoice['tax_amount']
    );
    
    if ($sent) {
        $email_sent = true;
        // Log activity
        $user_id = $_SESSION['id'];
        $log_desc = "Invoice #" . $invoice['invoice_number'] . " emailed to vendor " . $invoice['firstname'] . " " . $invoice['lastname'] . ".";
        mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($user_id, 'INVOICE_SENT', '$log_desc')");
    } else {
        $email_error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link rel="stylesheet" href="../RFQ/rfq.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .invoice-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--panel-border);
            padding: 40px;
            margin-top: 1.5rem;
            color: #334155;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .company-info h2 {
            font-size: 1.8rem;
            margin: 0 0 10px 0;
            color: #1e293b;
        }
        .company-info span {
            color: #7c6af7;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-meta h1 {
            font-size: 2rem;
            margin: 0 0 10px 0;
            color: #1e293b;
        }
        .invoice-meta p {
            margin: 4px 0;
            color: #64748b;
        }
        .invoice-addresses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .address-box h3 {
            font-size: 0.95rem;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
        .address-box p {
            margin: 6px 0;
            line-height: 1.5;
            color: #334155;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .invoice-table th, .invoice-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .invoice-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .invoice-summary {
            display: flex;
            justify-content: flex-end;
        }
        .summary-block {
            width: 300px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-row.total-row {
            border-bottom: none;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            padding-top: 15px;
        }
        .actions-panel {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
            }
            .app-sidebar, .dash-header, .actions-panel, .content-header, .menu-toggle-btn {
                display: none !important;
            }
            .app-layout {
                display: block !important;
            }
            .app-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
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
        <!-- Sidebar Navigation (Only show if not printing) -->
                <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <?php
                    $script = $_SERVER['SCRIPT_NAME'];
                    $active_dash = (strpos($script, '/dashboard/adminDashboard/dashboard/') !== false) ? 'active' : '';
                    $active_vendors = (strpos($script, '/dashboard/adminDashboard/vendors/') !== false) ? 'active' : '';
                    $active_rfq = (strpos($script, '/dashboard/adminDashboard/RFQ/') !== false) ? 'active' : '';
                    $active_quotes = (strpos($script, '/dashboard/adminDashboard/quotations/') !== false) ? 'active' : '';
                    $active_approvals = (strpos($script, '/dashboard/adminDashboard/approvals/') !== false) ? 'active' : '';
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
                <h1 class="welcome-title">Invoice details</h1>
                <p class="welcome-subtitle">Invoice document #<?= htmlspecialchars($invoice['invoice_number']) ?></p>
            </section>

            <div class="invoice-card" id="invoiceCard">
                <div class="invoice-header">
                    <div class="company-info">
                        <h2><span>Vendor</span>Bridge</h2>
                        <p>Smart Vendor Sourcing Corp.</p>
                        <p>GSTIN: 07AAAAB1234C1Z1</p>
                    </div>
                    <div class="invoice-meta">
                        <h1>INVOICE</h1>
                        <p><strong>Invoice #:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></p>
                        <p><strong>PO Ref:</strong> <?= htmlspecialchars($invoice['po_number']) ?></p>
                        <p><strong>Date:</strong> <?= date('F d, Y', strtotime($invoice['created_at'])) ?></p>
                        <p><strong>Due Date:</strong> <?= date('F d, Y', strtotime($invoice['created_at'] . ' + 30 days')) ?></p>
                    </div>
                </div>

                <div class="invoice-addresses">
                    <div class="address-box">
                        <h3>Billing Details</h3>
                        <p><strong><?= htmlspecialchars($invoice['firstname'] . ' ' . $invoice['lastname']) ?></strong></p>
                        <p><?= nl2br(htmlspecialchars($invoice['billing_address'])) ?></p>
                        <p>Email: <?= htmlspecialchars($invoice['email']) ?></p>
                        <p>Phone: <?= htmlspecialchars($invoice['phone']) ?></p>
                    </div>
                    <div class="address-box">
                        <h3>Shipping Instructions</h3>
                        <p><?= nl2br(htmlspecialchars($invoice['shipping_address'])) ?></p>
                        <p><strong>Delivery Term:</strong> <?= htmlspecialchars($invoice['shipping_method']) ?></p>
                        <p><strong>Payment Terms:</strong> <?= htmlspecialchars($invoice['payment_terms']) ?></p>
                    </div>
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($line_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= htmlspecialchars($item['qty']) ?></td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                <td>₹ <?= number_format($item['price']) ?></td>
                                <td>₹ <?= number_format($item['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="invoice-summary">
                    <div class="summary-block">
                        <div class="summary-row">
                            <span>Base Subtotal</span>
                            <span>₹ <?= number_format($invoice['subtotal']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>GST (18%)</span>
                            <span>₹ <?= number_format($invoice['tax_amount']) ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Grand Total</span>
                            <span>₹ <?= number_format($invoice['grand_total']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-panel">
                <button class="btn btn-primary" onclick="window.print();">Print / Save as PDF</button>
                
                <?php if ($_SESSION['role'] !== 'vendor'): ?>
                    <form method="POST" style="display:inline-block;">
                        <button type="submit" name="send_email" class="btn btn-outline">Send Invoice to Vendor Email</button>
                    </form>
                    <button class="btn btn-reject" onclick="window.location.href='invoices.php'">Back to Invoices</button>
                <?php else: ?>
                    <button class="btn btn-outline" onclick="window.location.href='../../vendorDashboard/vendorDashboard.php'">Back to Dashboard</button>
                <?php endif; ?>
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

    <?php if($email_sent): ?>
    <script>
        showToast("Invoice emailed successfully via PHPMailer!", "success");
    </script>
    <?php elseif($email_error): ?>
    <script>
        showToast("Error sending invoice email. Check SMTP server configuration.", "fail");
    </script>
    <?php endif; ?>
</body>
</html>
