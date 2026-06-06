<?php
require_once '../../auth/session_helper.php';
require_once '../../config/connection.php';
requireRoles(['vendor']);

$rfq_id = isset($_GET['rfq_id']) ? (int)$_GET['rfq_id'] : 0;
if ($rfq_id === 0) {
    header("Location: vendorDashboard.php");
    exit();
}

$vendor_id = $_SESSION['id'];

// Fetch RFQ Details
$rfq_res = mysqli_query($conn, "SELECT * FROM rfqs WHERE id = $rfq_id AND status = 'Published'");
if (!$rfq_res || mysqli_num_rows($rfq_res) === 0) {
    die("RFQ not found or is closed.");
}
$rfq = mysqli_fetch_assoc($rfq_res);

// Verify vendor assignment
$assigned_vendors = json_decode($rfq['assigned_vendors'], true) ?: [];
if (!in_array($vendor_id, $assigned_vendors)) {
    die("Access Denied: You are not assigned to participate in this RFQ sourcing round.");
}

// Check if vendor already submitted a quote
$quote_check = mysqli_query($conn, "SELECT id FROM quotations WHERE rfq_id = $rfq_id AND vendor_id = $vendor_id");
if (mysqli_num_rows($quote_check) > 0) {
    $_SESSION['toast'] = [
        'type' => 'fail',
        'message' => 'You have already submitted a quotation for this RFQ.'
    ];
    header("Location: vendorDashboard.php");
    exit();
}

// Parse items and quantities
$items = json_decode($rfq['items'], true) ?: [];
$quantities = json_decode($rfq['quantities'], true) ?: [];
$units = json_decode($rfq['units'], true) ?: [];

// POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_prices = $_POST['prices'] ?? [];
    $delivery_days = (int)($_POST['delivery_days'] ?? 0);
    $payment_terms = mysqli_real_escape_string($conn, trim($_POST['payment_terms'] ?? 'Net 30 days'));
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
    
    // Clean and validate pricing
    $sanitized_prices = [];
    $subtotal = 0;
    
    for ($i = 0; $i < count($items); $i++) {
        $price = (float)($unit_prices[$i] ?? 0);
        $qty = (int)($quantities[$i] ?? 0);
        
        $sanitized_prices[] = $price;
        $subtotal += ($price * $qty);
    }
    
    $gst_percent = 18.00;
    $tax_amount = $subtotal * ($gst_percent / 100);
    $grand_total = $subtotal + $tax_amount;
    
    $json_prices = mysqli_real_escape_string($conn, json_encode($sanitized_prices));
    
    $insert_sql = "INSERT INTO quotations (rfq_id, vendor_id, items_pricing, grand_total, gst_percent, delivery_days, payment_terms, notes, status) 
                   VALUES ($rfq_id, $vendor_id, '$json_prices', $grand_total, $gst_percent, $delivery_days, '$payment_terms', '$notes', 'Submitted')";
                   
    if (mysqli_query($conn, $insert_sql)) {
        // Log activity
        $log_desc = "Vendor " . $_SESSION['username'] . " submitted a quotation response of ₹ " . number_format($grand_total) . " for RFQ " . $rfq['rfq_number'] . ".";
        mysqli_query($conn, "INSERT INTO activity_logs (user_id, activity_type, description) VALUES ($vendor_id, 'QUOTE_SUBMITTED', '$log_desc')");
        
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Quotation submitted successfully!'
        ];
        header("Location: vendorDashboard.php");
        exit();
    } else {
        die("Error saving quotation: " . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - Submit Quotation</title>
    <link rel="stylesheet" href="submit_quotation.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            margin-top: 1.5rem;
        }
        .form-panel-left, .form-panel-right {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-section-card {
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 24px;
        }
        .section-card-title {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-title);
            margin-bottom: 1rem;
            font-weight: 600;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 8px;
        }
        .line-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .line-items-table th, .line-items-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--panel-border);
        }
        .line-items-table th {
            background-color: #f8fafc;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .table-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid var(--panel-border);
            border-radius: 4px;
            outline: none;
            background-color: #f8fafc;
        }
        .price-breakdown {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--panel-border);
        }
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 0.9rem;
        }
        .grand-total-row {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1a2e;
            border-top: 1px solid var(--panel-border);
            padding-top: 10px;
            margin-top: 10px;
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
                    <li><a href="vendorDashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
                    <li class="active"><a href="quotations.php"><span class="nav-icon">📁</span> Quotations</a></li>
                </ul>
            </nav>
        </aside>

        <main class="app-content">
            <section class="content-header">
                <h1 class="welcome-title">Submit Quotation</h1>
                <p class="welcome-subtitle">RFQ Sourcing: <?= htmlspecialchars($rfq['rfq_number']) ?> &bull; <?= htmlspecialchars($rfq['title']) ?></p>
            </section>

            <form class="rfq-form" id="quoteForm" method="POST" action="submit_quotation.php?rfq_id=<?= $rfq_id ?>">
                <div class="form-grid">
                    
                    <!-- Left Panel: Line Items Pricing Entry -->
                    <div class="form-panel-left">
                        <div class="form-section-card">
                            <h3 class="section-card-title">Enter Item Unit Pricing</h3>
                            <div class="table-responsive">
                                <table class="line-items-table" id="lineItemsTable">
                                    <thead>
                                        <tr>
                                            <th>Item Description</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Unit Price (INR)*</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $i => $name): 
                                            $qty = (int)($quantities[$i] ?? 0);
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($name) ?></td>
                                                <td><?= $qty ?></td>
                                                <td><?= htmlspecialchars($units[$i] ?? 'NOS') ?></td>
                                                <td>
                                                    <input type="number" 
                                                           name="prices[]" 
                                                           class="table-input price-input" 
                                                           required 
                                                           min="0.01" 
                                                           step="0.01" 
                                                           data-qty="<?= $qty ?>" 
                                                           placeholder="e.g. 150.00">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notes and delivery options -->
                        <div class="form-section-card">
                            <h3 class="section-card-title">Additional Sourcing Details</h3>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" for="delivery_days">Delivery Timeline (Days to deliver)*</label>
                                <input type="number" class="form-control" name="delivery_days" id="delivery_days" required min="1" placeholder="e.g. 10">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" for="payment_terms">Payment Terms offered</label>
                                <input type="text" class="form-control" name="payment_terms" id="payment_terms" value="Net 30 days">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="notes">Vendor remarks / Proposal notes</label>
                                <textarea class="form-control" name="notes" id="notes" style="height: 100px;" placeholder="Optional proposal details or terms..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Dynamic Live Calculations Card -->
                    <div class="form-panel-right">
                        <div class="form-section-card">
                            <h3 class="section-card-title">Quotation Summary</h3>
                            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:15px;">Pricing updates dynamically as you fill out unit values.</p>
                            
                            <div class="price-breakdown">
                                <div class="breakdown-row">
                                    <span>Base Subtotal</span>
                                    <span id="lblSubtotal">₹ 0.00</span>
                                </div>
                                <div class="breakdown-row">
                                    <span>GST (18%)</span>
                                    <span id="lblGST">₹ 0.00</span>
                                </div>
                                <div class="breakdown-row grand-total-row">
                                    <span>Grand Total</span>
                                    <span id="lblGrandTotal" style="color: #7c6af7;">₹ 0.00</span>
                                </div>
                            </div>
                            
                            <div style="margin-top:20px; display:flex; gap:10px;">
                                <button type="submit" class="btn btn-primary" style="flex:1;">Submit Proposal</button>
                                <button type="button" class="btn btn-outline" onclick="window.location.href='vendorDashboard.php'">Cancel</button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>

    <!-- Live Pricing Scripts -->
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

            const priceInputs = document.querySelectorAll('.price-input');
            const lblSubtotal = document.getElementById('lblSubtotal');
            const lblGST = document.getElementById('lblGST');
            const lblGrandTotal = document.getElementById('lblGrandTotal');

            const calculateTotals = () => {
                let subtotal = 0;
                priceInputs.forEach(input => {
                    const price = parseFloat(input.value) || 0;
                    const qty = parseInt(input.getAttribute('data-qty')) || 0;
                    subtotal += (price * qty);
                });

                const gst = subtotal * 0.18;
                const grandTotal = subtotal + gst;

                lblSubtotal.textContent = `₹ ${subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                lblGST.textContent = `₹ ${gst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                lblGrandTotal.textContent = `₹ ${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            };

            priceInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
        });
    </script>
</body>
</html>
