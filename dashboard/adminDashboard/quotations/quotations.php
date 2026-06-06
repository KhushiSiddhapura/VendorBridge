<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge portal quotation comparison. Compare received vendor quotations on pricing, GST, ratings, and payment terms side-by-side.">
    <title>VendorBridge - Quotation Comparison</title>
    <link rel="stylesheet" href="quotations.css">
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
                    <li>
                        <a href="rfq.html">
                            <span class="nav-icon">📝</span>
                            RFQ's
                        </a>
                    </li>
                    <li class="active">
                        <a href="quotations.html">
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
                <h1 class="welcome-title">Quotation Comparison</h1>
                <p class="welcome-subtitle">RFQ: office furniture procurement q2 - 3 quotations received</p>
            </section>

            <!-- Comparison Table Panel Card -->
            <section class="comparison-section">
                <div class="comparison-card">
                    <div class="table-responsive">
                        <table class="comparison-table">
                            <thead>
                                <tr>
                                    <th class="col-criteria">Criteria</th>
                                    <th class="col-vendor highlighted-vendor">
                                        <div class="vendor-header-wrap">
                                            <span class="badge-lowest">Lowest</span>
                                            <span class="vendor-name">Infra Supplies</span>
                                        </div>
                                    </th>
                                    <th class="col-vendor">TechCore LTD</th>
                                    <th class="col-vendor">Office Need Co.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="row-label">Grand Total</td>
                                    <td class="cell-val highlighted-vendor">₹ 1,85,000</td>
                                    <td class="cell-val">₹ 2,00,010</td>
                                    <td class="cell-val">₹ 2,14,800</td>
                                </tr>
                                <tr>
                                    <td class="row-label">GST %</td>
                                    <td class="cell-val highlighted-vendor">18%</td>
                                    <td class="cell-val">18%</td>
                                    <td class="cell-val">18%</td>
                                </tr>
                                <tr>
                                    <td class="row-label">Delivery (days)</td>
                                    <td class="cell-val highlighted-vendor">10 days</td>
                                    <td class="cell-val">14 days</td>
                                    <td class="cell-val">7 days</td>
                                </tr>
                                <tr>
                                    <td class="row-label">Rating</td>
                                    <td class="cell-val highlighted-vendor">
                                        <span class="score-pill">4.5/5</span>
                                    </td>
                                    <td class="cell-val">
                                        <span class="score-pill">4.2/5</span>
                                    </td>
                                    <td class="cell-val">
                                        <span class="score-pill">3.8/5</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="row-label">Payment terms</td>
                                    <td class="cell-val highlighted-vendor">30 days</td>
                                    <td class="cell-val">30 days</td>
                                    <td class="cell-val">15 days</td>
                                </tr>
                                <tr class="actions-row-cell">
                                    <td></td>
                                    <td class="highlighted-vendor cell-action">
                                        <button class="btn btn-approve" id="btnApproveInfra">Select & Approve</button>
                                    </td>
                                    <td class="cell-action">
                                        <button class="btn btn-select" id="btnSelectTechcore">Select & Approve</button>
                                    </td>
                                    <td class="cell-action">
                                        <button class="btn btn-select" id="btnSelectOfficeneed">Select & Approve</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Responsive Cards for Tablet/Mobile View -->
                <div class="comparison-cards-mobile">
                    <!-- Vendor Card 1 (Lowest) -->
                    <div class="vendor-card-item highlighted-vendor-card">
                        <div class="vendor-card-header">
                            <span class="badge-lowest">Lowest</span>
                            <h3 class="vendor-card-title">Infra Supplies</h3>
                        </div>
                        <div class="vendor-card-body">
                            <div class="vendor-card-row">
                                <span class="row-key">Grand Total</span>
                                <span class="row-value val-lowest">₹ 1,85,000</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">GST %</span>
                                <span class="row-value">18%</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Delivery</span>
                                <span class="row-value">10 days</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Rating</span>
                                <span class="row-value"><span class="score-pill">4.5/5</span></span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Payment terms</span>
                                <span class="row-value">30 days</span>
                            </div>
                        </div>
                        <div class="vendor-card-footer">
                            <button class="btn btn-approve" id="btnApproveInfraMobile">Select & Approve</button>
                        </div>
                    </div>

                    <!-- Vendor Card 2 -->
                    <div class="vendor-card-item">
                        <div class="vendor-card-header">
                            <h3 class="vendor-card-title">TechCore LTD</h3>
                        </div>
                        <div class="vendor-card-body">
                            <div class="vendor-card-row">
                                <span class="row-key">Grand Total</span>
                                <span class="row-value">₹ 2,00,010</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">GST %</span>
                                <span class="row-value">18%</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Delivery</span>
                                <span class="row-value">14 days</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Rating</span>
                                <span class="row-value"><span class="score-pill">4.2/5</span></span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Payment terms</span>
                                <span class="row-value">30 days</span>
                            </div>
                        </div>
                        <div class="vendor-card-footer">
                            <button class="btn btn-select" id="btnSelectTechcoreMobile">Select & Approve</button>
                        </div>
                    </div>

                    <!-- Vendor Card 3 -->
                    <div class="vendor-card-item">
                        <div class="vendor-card-header">
                            <h3 class="vendor-card-title">Office Need Co.</h3>
                        </div>
                        <div class="vendor-card-body">
                            <div class="vendor-card-row">
                                <span class="row-key">Grand Total</span>
                                <span class="row-value">₹ 2,14,800</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">GST %</span>
                                <span class="row-value">18%</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Delivery</span>
                                <span class="row-value">7 days</span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Rating</span>
                                <span class="row-value"><span class="score-pill">3.8/5</span></span>
                            </div>
                            <div class="vendor-card-row">
                                <span class="row-key">Payment terms</span>
                                <span class="row-value">15 days</span>
                            </div>
                        </div>
                        <div class="vendor-card-footer">
                            <button class="btn btn-select" id="btnSelectOfficeneedMobile">Select & Approve</button>
                        </div>
                    </div>
                </div>
                <!-- Legend Information -->
                <div class="comparison-legend-container">
                    <p class="comparison-legend">Green = lowest price, selecting vendor initiates the approval workflow.</p>
                </div>
            </section>

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

            // Button selection alerts for interaction checks
            const setupSelectAction = (btnId, vendorName, isApprove) => {
                const button = document.getElementById(btnId);
                if (button) {
                    button.addEventListener('click', () => {
                        alert(isApprove 
                            ? `Vendor "${vendorName}" selected! Initiating approval workflow.` 
                            : `Vendor "${vendorName}" selected.`);
                    });
                }
            };

            setupSelectAction('btnApproveInfra', 'Infra Supplies', true);
            setupSelectAction('btnSelectTechcore', 'TechCore LTD', true);
            setupSelectAction('btnSelectOfficeneed', 'Office Need Co.', true);

            setupSelectAction('btnApproveInfraMobile', 'Infra Supplies', true);
            setupSelectAction('btnSelectTechcoreMobile', 'TechCore LTD', true);
            setupSelectAction('btnSelectOfficeneedMobile', 'Office Need Co.', true);
        });
    </script>
</body>
</html>
