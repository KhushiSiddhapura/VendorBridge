<?php
session_start();

// Import system database connection parameter
require '../../../config/connection.php';

// Fetch all created RFQs sorted by the newest first
$rfq_query = mysqli_query($conn, "SELECT * FROM rfqs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - RFQ Directory</title>
    <link rel="stylesheet" href="rfq.css">
    <style>
        /* Custom enhancements for the table view dashboard layer */
        .rfq-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
            background: var(--panel-bg);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--panel-border);
        }
        .rfq-table th {
            background-color: #f8fafc;
            color: var(--text-title);
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid var(--panel-border);
        }
        .rfq-table td {
            padding: 1rem;
            color: var(--text-body);
            font-size: 0.9rem;
            border-bottom: 1px solid var(--panel-border);
            vertical-align: top;
        }
        .rfq-table tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-published { background-color: #ecfdf5; color: #059669; }
        .badge-draft { background-color: #f1f5f9; color: #64748b; }
        
        .nested-items-list {
            margin: 0;
            padding-left: 1.2rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .top-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="menu-backdrop" id="menuBackdrop"></div>

    <header class="dash-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="btnMenuToggle" aria-label="Toggle Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-logo"><span>Vendor</span>Bridge</div>
        </div>
    </header>

    <div class="app-layout">
        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="../dashboard/adminDashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
                    <li><a href="../vendors/vendors.php"><span class="nav-icon">🤝</span> Vendors</a></li>
                    <li class="active"><a href="rfq.php"><span class="nav-icon">📝</span> RFQ's</a></li>
                </ul>
            </nav>
        </aside>

        <main class="app-content">
            <section class="content-header">
                <div class="top-action-bar">
                    <div>
                        <h1 class="welcome-title">RFQ Management</h1>
                        <p class="welcome-subtitle">Overview of your issued requests for quotations</p>
                    </div>
                    <a href="rfq.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <span>+</span> Create New RFQ
                    </a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div style="background-color: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 6px; margin-top: 1rem; border: 1px solid #a7f3d0;">
                        <strong>Success!</strong> Your Request for Quotation structure was saved flawlessly inside the database.
                    </div>
                <?php endif; ?>
            </section>

            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>RFQ Number</th>
                        <th>Title / Category</th>
                        <th>Deadline</th>
                        <th>Requested Line Items</th>
                        <th>Workflow Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rfq_query && mysqli_num_rows($rfq_query) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($rfq_query)): 
                            // Unpacking the structured JSON rows back into readable arrays
                            $items      = json_decode($row['items'], true) ?: [];
                            $quantities = json_decode($row['quantities'], true) ?: [];
                            $units      = json_decode($row['units'], true) ?: [];
                            
                            $status_class = ($row['status'] === 'Published') ? 'badge-published' : 'badge-draft';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['rfq_number']) ?></strong></td>
                                <td>
                                    <span style="font-weight: 600; display:block; color: var(--text-title);"><?= htmlspecialchars($row['title']) ?></span>
                                    <small style="color: var(--text-muted); font-size: 0.75rem;"><?= htmlspecialchars($row['category']) ?></small>
                                </td>
                                <td><span style="color: #b91c1c; font-weight: 500;"><?= date('M d, Y', strtotime($row['submission_deadline'])) ?></span></td>
                                <td>
                                    <?php if (!empty($items)): ?>
                                        <ul class="nested-items-list">
                                            <?php for ($i = 0; $i < count($items); $i++): ?>
                                                <li>
                                                    <?= htmlspecialchars($items[$i]) ?> 
                                                    (<strong><?= htmlspecialchars($quantities[$i]) ?> <?= htmlspecialchars($units[$i]) ?></strong>)
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">No line items declared</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                No Request for Quotation configurations logged in system database.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
</body>
</html>