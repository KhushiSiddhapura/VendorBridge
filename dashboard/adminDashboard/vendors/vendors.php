<?php

session_start();

require '../../../config/connection.php';

$vendors = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE role = 'vendor'"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VendorBridge system vendors directory. Search, manage, filter, and review vendor supplier profiles.">
    <title>VendorBridge - Vendors Portal</title>
    <link rel="stylesheet" href="vendors.css">
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
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
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
                    <li class="active">
                        <a href="vendor.html">
                            <span class="nav-icon">🤝</span>
                            Vendors
                        </a>
                    </li>
                    <li>
                        <a href="#rfqs">
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

            <!-- Welcome Header & Action -->
            <section class="content-header">
                <div class="title-row">
                    <div class="title-left">
                        <h1 class="welcome-title">Vendors</h1>
                        <p class="welcome-subtitle">Manage supplier profiles and registrations</p>
                    </div>
                    <a href="../../../register/register.php">
                        <button class="btn btn-primary" id="btnAddVendor">
                            <span class="btn-icon">+</span> Add Vendor
                        </button>
                    </a>
                </div>
            </section>

            <!-- Search Bar Section -->
            <section class="search-section">
                <div class="search-container">
                    <input type="text" class="search-control" placeholder="Search by name, GST number, category...">
                    <div class="search-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
            </section>

            <!-- Status Tabs Section -->
            <section class="filter-section">
                <div class="filter-tabs">
                    <button class="filter-tab active">
                        All (<?= mysqli_num_rows($vendors) ?>)
                    </button>

                    <button class="filter-tab">
                        Active (0)
                    </button>

                    <button class="filter-tab">
                        Pending (<?= mysqli_num_rows($vendors) ?>)
                    </button>

                    <button class="filter-tab">
                        Blocked (0)
                    </button>
                </div>
            </section>

            <!-- Supplier Directory Table -->
            <section class="directory-section">
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="directory-table">
                            <thead>
                                <tr>
                                    <th>Vendor Name</th>
                                    <th>Category</th>
                                    <th>GST No.</th>
                                    <th>Contact No.</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if (mysqli_num_rows($vendors) > 0): ?>

                                    <?php mysqli_data_seek($vendors, 0); ?>

                                    <?php while ($vendor = mysqli_fetch_assoc($vendors)): ?>

                                        <tr>

                                            <td class="vendor-name-col">

                                                <div class="vendor-info">

                                                    <span class="vendor-avatar">

                                                        <?= strtoupper(substr($vendor['firstname'], 0, 1)) ?>

                                                    </span>

                                                    <span class="vendor-name-text">

                                                        <?= htmlspecialchars(
                                                            $vendor['firstname'] . ' ' . $vendor['lastname']
                                                        ) ?>

                                                    </span>

                                                </div>

                                            </td>

                                            <td>

                                                -

                                            </td>

                                            <td>

                                                <code class="gst-code">

                                                    -

                                                </code>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($vendor['phone']) ?>

                                            </td>

                                            <td>

                                                <span class="status-pill pending-status">

                                                    Pending

                                                </span>

                                            </td>

                                            <td>

                                                <button
                                                    class="btn btn-action"
                                                    onclick="window.location.href='vendorprofile.php?id=<?= $vendor['id'] ?>'">

                                                    View

                                                </button>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="6" style="text-align:center; padding:30px;">

                                            No vendors found.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
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
        });
    </script>
</body>

</html>