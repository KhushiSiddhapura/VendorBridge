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
        <div class="header-profile-group">
            <span class="avatar-initial color-m" title="Manager">M</span>
            <span class="avatar-initial color-a" title="Admin">A</span>
            <span class="avatar-initial color-s" title="Supervisor">S</span>
            <span class="avatar-initial color-g" title="Guest">G</span>
            <div class="user-avatar-circle" title="User Profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </header>

    <!-- App Main Layout -->
    <div class="app-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="landingpage.html">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="vendor.html">
                            <span class="nav-icon">🤝</span>
                            Vendors
                        </a>
                    </li>
                    <li class="active">
                        <a href="rfq.html">
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

        <!-- Main Content Panel -->
        <main class="app-content">
            
            <!-- Welcome Header -->
            <section class="content-header">
                <h1 class="welcome-title">Create RFQ's</h1>
                <p class="welcome-subtitle">new request for quotation</p>
            </section>


            <!-- Form Split Grid Layout -->
            <form class="rfq-form" id="rfqForm" onsubmit="event.preventDefault();">
                <div class="form-grid">
                    
                    <!-- Left Form Section (RFQ Info) -->
                    <div class="form-panel-left">
                        <div class="form-group">
                            <label class="form-label" for="rfqTitle">RFQ's title*</label>
                            <input type="text" class="form-control" id="rfqTitle" required value="Office Furniture procurement Q2">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="rfqCategory">Category</label>
                            <select class="form-control" id="rfqCategory">
                                <option value="Furniture" selected>Furniture</option>
                                <option value="IT">IT</option>
                                <option value="Constructions">Constructions</option>
                                <option value="Logistics">Logistics</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="rfqDeadline">Deadline*</label>
                            <input type="date" class="form-control" id="rfqDeadline" required value="2025-06-15">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="rfqDescription">Description</label>
                            <textarea class="form-control textarea-control" id="rfqDescription" placeholder="Enter Description">Ergonomic chairs and standing desks for 3rd floor</textarea>
                        </div>
                    </div>

                    <!-- Right Form Section (Line Items & Vendors) -->
                    <div class="form-panel-right">
                        
                        <!-- Line Items Card Area -->
                        <div class="form-section-card">
                            <h3 class="section-card-title">Line items</h3>
                            <div class="table-responsive">
                                <table class="line-items-table">
                                    <thead>
                                        <tr>
                                            <th>item</th>
                                            <th>qty</th>
                                            <th>Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="table-input" value="Ergonomic chair" placeholder="Item name"></td>
                                            <td><input type="number" class="table-input qty-input" value="25" placeholder="Qty"></td>
                                            <td><input type="text" class="table-input unit-input" value="NOS" placeholder="Unit"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="table-input" value="Standing desks" placeholder="Item name"></td>
                                            <td><input type="number" class="table-input qty-input" value="10" placeholder="Qty"></td>
                                            <td><input type="text" class="table-input unit-input" value="NOS" placeholder="Unit"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" id="btnAddLineItem">
                                <span class="btn-icon">+</span> add line item
                            </button>
                        </div>

                        <!-- Assigned Vendors Area -->
                        <div class="form-section-card">
                            <h3 class="section-card-title">ASSIGN VENDORS</h3>
                            <div class="vendors-list-container">
                                <div class="assigned-vendor-row">
                                    <span class="vendor-row-name">Infra Supplies Pvt Ltd</span>
                                    <button type="button" class="btn-remove-vendor" aria-label="Remove Vendor">&times;</button>
                                </div>
                                <div class="assigned-vendor-row">
                                    <span class="vendor-row-name">Techcore LTD</span>
                                    <button type="button" class="btn-remove-vendor" aria-label="Remove Vendor">&times;</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" id="btnAddVendor">
                                <span class="btn-icon">+</span> add vendor
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Horizontal Divider Line -->
                <hr class="form-divider">

                <!-- Footer Action & Attachments Panel -->
                <div class="form-footer">
                    <!-- Left Action Column -->
                    <div class="footer-actions">
                        <button type="submit" class="btn btn-primary" id="btnSendRFQ">
                            Save & Send to Vendors
                        </button>
                        <button type="button" class="btn btn-outline" id="btnDraftRFQ">
                            Save as Draft
                        </button>
                    </div>

                    <!-- Right Attachments Card -->
                    <div class="footer-attachments">
                        <span class="form-label">Attachments</span>
                        <div class="upload-dropzone" id="dropzone">
                            <div class="dropzone-content">
                                <span class="upload-icon">📁</span>
                                <span class="upload-text">Drag & drop files or click to upload</span>
                            </div>
                            <input type="file" id="fileUpload" style="display: none;" multiple>
                        </div>
                    </div>
                </div>
            </form>

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

                // Close menu drawer if navigation link is clicked
                const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        body.classList.remove('menu-open');
                    });
                });
            }

            // Dropzone click trigger
            const dropzone = document.getElementById('dropzone');
            const fileUpload = document.getElementById('fileUpload');
            if (dropzone && fileUpload) {
                dropzone.addEventListener('click', () => {
                    fileUpload.click();
                });
            }
        });
    </script>
</body>
</html>
