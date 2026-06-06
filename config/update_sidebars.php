<?php

$sidebar_replacement = '        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <?php
                    $script = $_SERVER[\'SCRIPT_NAME\'];
                    $active_dash = (strpos($script, \'/dashboard/adminDashboard/dashboard/\') !== false) ? \'active\' : \'\';
                    $active_vendors = (strpos($script, \'/dashboard/adminDashboard/vendors/\') !== false) ? \'active\' : \'\';
                    $active_rfq = (strpos($script, \'/dashboard/adminDashboard/RFQ/\') !== false) ? \'active\' : \'\';
                    $active_quotes = (strpos($script, \'/dashboard/adminDashboard/quotations/\') !== false) ? \'active\' : \'\';
                    $active_approvals = (strpos($script, \'/dashboard/adminDashboard/approvals/\') !== false) ? \'active\' : \'\';
                    $active_po = (strpos($script, \'/dashboard/adminDashboard/purchase_orders/\') !== false) ? \'active\' : \'\';
                    $active_invoices = (strpos($script, \'/dashboard/adminDashboard/invoices/\') !== false) ? \'active\' : \'\';
                    $active_reports = (strpos($script, \'/dashboard/adminDashboard/reports/\') !== false) ? \'active\' : \'\';
                    $active_activity = (strpos($script, \'/dashboard/adminDashboard/activity/\') !== false) ? \'active\' : \'\';
                    $active_register = (strpos($script, \'/register/\') !== false) ? \'active\' : \'\';
                    
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
                            RFQ\'s
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
                    <?php if (isset($_SESSION[\'role\']) && $_SESSION[\'role\'] === \'admin\'): ?>
                    <li class="<?= $active_register ?>">
                        <a href="<?= $root ?>register/register.php">
                            <span class="nav-icon">👤</span>
                            Add Account
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>';

function updateSidebarsRecursively($dir, $sidebar_replacement) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            
            // Skip vendor, .git, config, managerDashboard, and vendorDashboard directories
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false ||
                strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false ||
                strpos($path, DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR) !== false ||
                strpos($path, DIRECTORY_SEPARATOR . 'managerDashboard' . DIRECTORY_SEPARATOR) !== false ||
                strpos($path, DIRECTORY_SEPARATOR . 'vendorDashboard' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }

            $content = file_get_contents($path);
            
            // Perform regex replace matching <aside class="app-sidebar"> ... </aside>
            $new_content = preg_replace('/<aside class="app-sidebar">.*?<\/aside>/s', $sidebar_replacement, $content);
            
            if ($new_content !== null && $new_content !== $content) {
                file_put_contents($path, $new_content);
                echo "Updated sidebar in: " . $path . PHP_EOL;
            }
        }
    }
}

$project_root = dirname(__DIR__);
updateSidebarsRecursively($project_root, $sidebar_replacement);
echo "Sidebar update processing complete." . PHP_EOL;
?>
