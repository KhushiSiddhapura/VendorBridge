<?php
session_start();

require '../../../config/connection.php';
require_once '../../../services/mailService.php';

// ==========================================
// PART 1: POST SUBMISSION PROCESSING HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Generate a unique RFQ number for the background entry
    $rfq_number = 'RFQ-' . date('Y') . '-' . rand(1000, 9999);

    // Sanitize basic form text parameters
    $title       = mysqli_real_escape_string($conn, $_POST['rfq_title']);
    $category    = mysqli_real_escape_string($conn, $_POST['category']);
    $deadline    = mysqli_real_escape_string($conn, $_POST['submission_deadline']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status      = mysqli_real_escape_string($conn, $_POST['workflow_status']);

    // Clean up empty form entries before encoding arrays
    $filtered_items      = [];
    $filtered_quantities = [];
    $filtered_units      = [];

    if (isset($_POST['items']) && is_array($_POST['items'])) {
        for ($i = 0; $i < count($_POST['items']); $i++) {
            $item_name = trim($_POST['items'][$i]);
            if (!empty($item_name)) {
                $filtered_items[]      = $item_name;
                $filtered_quantities[] = (int)$_POST['quantities'][$i];
                $filtered_units[]      = trim($_POST['units'][$i]);
            }
        }
    }

    // Convert arrays into structured JSON strings for single row storage
    $json_items      = mysqli_real_escape_string($conn, json_encode($filtered_items));
    $json_quantities = mysqli_real_escape_string($conn, json_encode($filtered_quantities));
    $json_units      = mysqli_real_escape_string($conn, json_encode($filtered_units));

    // Handle assigned vendors transformation array mapping
    $vendor_array = isset($_POST['assigned_vendors']) && is_array($_POST['assigned_vendors']) ? array_map('intval', $_POST['assigned_vendors']) : [];
    $json_vendors = mysqli_real_escape_string($conn, json_encode($vendor_array));

    // Insert everything cleanly down into your single parent table 
    $rfq_query = "INSERT INTO rfqs (rfq_number, title, category, submission_deadline, description, items, quantities, units, assigned_vendors, status) 
                  VALUES ('$rfq_number', '$title', '$category', '$deadline', '$description', '$json_items', '$json_quantities', '$json_units', '$json_vendors', '$status')";

    if (mysqli_query($conn, $rfq_query)) {

        // Send RFQ email to each assigned vendor
        if (!empty($vendor_array)) {
            foreach ($vendor_array as $vendor_id) {
                $vResult = mysqli_query($conn, "SELECT firstname, email FROM users WHERE id = $vendor_id");
                if ($vResult && $vendor = mysqli_fetch_assoc($vResult)) {
                    sendRFQMail(
                        $vendor['email'],
                        $vendor['firstname'],
                        $rfq_number,
                        $title,
                        $category,
                        $deadline,
                        $description,
                        $filtered_items,
                        $filtered_quantities,
                        $filtered_units
                    );
                }
            }
        }

        header("Location: rfq_list.php?success=1");
        exit();
    } else {
        $error_message = "Database Save Failure: " . mysqli_error($conn);
    }
}

// ==========================================
// PART 2: INITIAL PAGE LOAD QUERY FETCHES
// ==========================================
// Fetch active registered vendor profiles directly from your database
$vendors_query = mysqli_query($conn, "SELECT id, firstname, lastname FROM users WHERE role = 'vendor' ORDER BY firstname ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal RFQ management. Create and configure requests for quotations, specify items, assign suppliers, and upload attachments.">
    <title>VendorBridge - Create RFQ</title>
    <link rel="stylesheet" href="rfq.css">
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
                    <li>
                        <a href="../dashboard/adminDashboard.php">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="../vendors/vendors.php">
                            <span class="nav-icon">🤝</span>
                            Vendors
                        </a>
                    </li>
                    <li class="active">
                        <a href="../RFQ/rfq.php">
                            <span class="nav-icon">📝</span>
                            RFQ's
                        </a>
                    </li>
                    <li>
                        <a href="#quotations">
                            <span class="nav-icon">📁</span>
                            Quotations
                        </a>
                    </li>
                    <li>
                        <a href="#approvals">
                            <span class="nav-icon">✅</span>
                            Approvals
                        </a>
                    </li>
                    <li>
                        <a href="#purchase-orders">
                            <span class="nav-icon">📦</span>
                            Purchase orders
                        </a>
                    </li>
                    <li>
                        <a href="#invoices">
                            <span class="nav-icon">🧾</span>
                            Invoices
                        </a>
                    </li>
                    <li>
                        <a href="#reports">
                            <span class="nav-icon">📈</span>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="#activity">
                            <span class="nav-icon">🔔</span>
                            Activity
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="app-content">

            <section class="content-header">
                <h1 class="welcome-title">Create RFQ's</h1>
                <p class="welcome-subtitle">new request for quotation</p>

                <?php if (isset($error_message)): ?>
                    <div style="background-color: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                        <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
            </section>

            <form class="rfq-form" id="rfqForm" action="rfq.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="workflow_status" id="workflowStatus" value="Published">

                <div class="form-grid">

                    <div class="form-panel-left">
                        <div class="form-group">
                            <label class="form-label" for="rfqTitle">RFQ's title*</label>
                            <input type="text" class="form-control" name="rfq_title" id="rfqTitle" required placeholder="e.g., Office Furniture procurement Q2">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rfqCategory">Category</label>
                            <select class="form-control" name="category" id="rfqCategory">
                                <option value="Furniture">Furniture</option>
                                <option value="IT">IT</option>
                                <option value="Constructions">Constructions</option>
                                <option value="Logistics">Logistics</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rfqDeadline">Deadline*</label>
                            <input type="date" class="form-control" name="submission_deadline" id="rfqDeadline" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rfqDescription">Description</label>
                            <textarea class="form-control textarea-control" name="description" id="rfqDescription" placeholder="Enter Description parameters..."></textarea>
                        </div>
                    </div>

                    <div class="form-panel-right">

                        <div class="form-section-card">
                            <h3 class="section-card-title">Line items</h3>
                            <div class="table-responsive">
                                <table class="line-items-table" id="lineItemsTable">
                                    <thead>
                                        <tr>
                                            <th>item</th>
                                            <th>qty</th>
                                            <th>Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" name="items[]" class="table-input" required placeholder="Item name"></td>
                                            <td><input type="number" name="quantities[]" class="table-input qty-input" required min="1" placeholder="Qty"></td>
                                            <td><input type="text" name="units[]" class="table-input unit-input" required placeholder="Unit (e.g. NOS)"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" id="btnAddLineItem">
                                <span class="btn-icon">+</span> add line item
                            </button>
                        </div>

                        <div class="form-section-card">
                            <h3 class="section-card-title">ASSIGN VENDORS</h3>

                            <div style="margin-bottom: 1rem; display: flex; gap: 0.5rem;">
                                <select class="form-control" id="vendorSelectPicker" style="flex: 1;">
                                    <option value="" disabled selected>Choose vendor</option>
                                    <?php if ($vendors_query && mysqli_num_rows($vendors_query) > 0): ?>
                                        <?php while ($vendor = mysqli_fetch_assoc($vendors_query)): ?>
                                            <option value="<?= $vendor['id'] ?>" data-name="<?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?>">
                                                <?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No active vendors found in system</option>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline" id="btnAddVendor" style="white-space: nowrap;">
                                    <span class="btn-icon">+</span> add vendor
                                </button>
                            </div>

                            <div class="vendors-list-container" id="assignedVendorsDeck"></div>
                        </div>

                    </div>
                </div>

                <hr class="form-divider">

                <div class="form-footer">
                    <div class="footer-actions">
                        <button type="submit" class="btn btn-primary" id="btnSendRFQ">
                            Save & Send to Vendors
                        </button>
                        <button type="button" class="btn btn-outline" id="btnDraftRFQ">
                            Save as Draft
                        </button>
                    </div>

                    <div class="footer-attachments">
                        <span class="form-label">Attachments</span>
                        <div class="upload-dropzone" id="dropzone">
                            <div class="dropzone-content">
                                <span class="upload-icon">📁</span>
                                <span class="upload-text" id="dropzoneText">Drag & drop files or click to upload</span>
                            </div>
                            <input type="file" name="rfq_attachments[]" id="fileUpload" style="display: none;" multiple>
                        </div>
                    </div>
                </div>
            </form>

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

            const btnAddLineItem = document.getElementById('btnAddLineItem');
            const lineItemsTableBody = document.getElementById('lineItemsTable').getElementsByTagName('tbody')[0];

            btnAddLineItem.addEventListener('click', () => {
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" name="items[]" class="table-input" required placeholder="Item name"></td>
                    <td><input type="number" name="quantities[]" class="table-input qty-input" required min="1" placeholder="Qty"></td>
                    <td><input type="text" name="units[]" class="table-input unit-input" required placeholder="Unit"></td>
                `;
                lineItemsTableBody.appendChild(newRow);
            });

            const btnAddVendor = document.getElementById('btnAddVendor');
            const vendorSelectPicker = document.getElementById('vendorSelectPicker');
            const assignedVendorsDeck = document.getElementById('assignedVendorsDeck');

            btnAddVendor.addEventListener('click', () => {
                const selectedOption = vendorSelectPicker.options[vendorSelectPicker.selectedIndex];
                const vendorId = vendorSelectPicker.value;
                const vendorName = selectedOption.getAttribute('data-name');

                if (!vendorId) {
                    alert('Please select a valid vendor first.');
                    return;
                }

                if (document.getElementById(`assigned-v-${vendorId}`)) {
                    alert('This vendor is already added to this RFQ workflow selection.');
                    return;
                }

                const vendorCard = document.createElement('div');
                vendorCard.className = 'assigned-vendor-row';
                vendorCard.id = `assigned-v-${vendorId}`;
                vendorCard.innerHTML = `
                    <input type="hidden" name="assigned_vendors[]" value="${vendorId}">
                    <span class="vendor-row-name">${vendorName}</span>
                    <button type="button" class="btn-remove-vendor" aria-label="Remove Vendor">&times;</button>
                `;

                vendorCard.querySelector('.btn-remove-vendor').addEventListener('click', () => {
                    vendorCard.remove();
                });

                assignedVendorsDeck.appendChild(vendorCard);
                vendorSelectPicker.selectedIndex = 0;
            });

            const dropzone = document.getElementById('dropzone');
            const fileUpload = document.getElementById('fileUpload');
            const dropzoneText = document.getElementById('dropzoneText');

            if (dropzone && fileUpload) {
                dropzone.addEventListener('click', () => fileUpload.click());
                fileUpload.addEventListener('change', () => {
                    if (fileUpload.files.length > 0) {
                        dropzoneText.textContent = `✓ Attached ${fileUpload.files.length} document(s)`;
                        dropzoneText.style.color = '#2563eb';
                    }
                });
            }

            // Locate these lines near the bottom of your file inside DOMContentLoaded
            const rfqForm = document.getElementById('rfqForm');
            const workflowStatus = document.getElementById('workflowStatus');
            const btnDraftRFQ = document.getElementById('btnDraftRFQ');

            if (btnDraftRFQ && rfqForm && workflowStatus) {
                btnDraftRFQ.addEventListener('click', (e) => {
                    // Prevent default action if any exists
                    e.preventDefault();

                    // 1. Temporarily disable 'required' attributes so an incomplete/partial draft can save successfully
                    const requiredInputs = rfqForm.querySelectorAll('[required]');
                    requiredInputs.forEach(input => {
                        input.removeAttribute('required');
                    });

                    // 2. Flip status flag to Draft
                    workflowStatus.value = 'Draft';

                    // 3. Programmatically force form validation submission bypass
                    rfqForm.submit();
                });
            }
        });
    </script>
</body>

</html>