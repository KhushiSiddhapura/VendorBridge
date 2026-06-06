<?php

require_once __DIR__ . '/connection.php';

echo "VendorBridge Database Setup started...\n";

// 1. Create rfqs table if not exists
$rfqsTable = "CREATE TABLE IF NOT EXISTS rfqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    submission_deadline DATE NOT NULL,
    description TEXT,
    items TEXT, -- JSON array
    quantities TEXT, -- JSON array
    units TEXT, -- JSON array
    assigned_vendors TEXT, -- JSON array of user IDs
    status VARCHAR(50) DEFAULT 'Published', -- 'Draft', 'Published', 'PO_Generated'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $rfqsTable)) {
    echo "✓ Table 'rfqs' verified/created.\n";
} else {
    die("✗ Error creating 'rfqs': " . mysqli_error($conn) . "\n");
}

// 2. Create quotations table if not exists
$quotationsTable = "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_id INT NOT NULL,
    vendor_id INT NOT NULL,
    items_pricing TEXT NOT NULL, -- JSON array
    grand_total DECIMAL(12,2) NOT NULL,
    gst_percent DECIMAL(5,2) DEFAULT 18.00,
    delivery_days INT NOT NULL,
    payment_terms VARCHAR(100) DEFAULT 'Net 30 days',
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Submitted', -- 'Submitted', 'L1_Reviewed', 'Approved', 'Rejected'
    l1_remarks TEXT,
    l2_remarks TEXT,
    l1_checked_specs TINYINT(1) DEFAULT 0,
    l1_checked_price TINYINT(1) DEFAULT 0,
    l1_checked_tax TINYINT(1) DEFAULT 0,
    l1_checked_delivery TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $quotationsTable)) {
    echo "✓ Table 'quotations' verified/created.\n";
} else {
    die("✗ Error creating 'quotations': " . mysqli_error($conn) . "\n");
}

// 3. Create purchase_orders table if not exists
$purchaseOrdersTable = "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    rfq_id INT NOT NULL,
    quotation_id INT NOT NULL,
    billing_address VARCHAR(255) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    payment_terms VARCHAR(100) NOT NULL,
    shipping_method VARCHAR(100) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    grand_total DECIMAL(12,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- 'Pending', 'Dispatched', 'Cancelled'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE CASCADE,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $purchaseOrdersTable)) {
    echo "✓ Table 'purchase_orders' verified/created.\n";
} else {
    die("✗ Error creating 'purchase_orders': " . mysqli_error($conn) . "\n");
}

// 4. Create invoices table if not exists
$invoicesTable = "CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    po_id INT NOT NULL,
    billing_address VARCHAR(255) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    grand_total DECIMAL(12,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Unpaid', -- 'Unpaid', 'Paid', 'Overdue'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $invoicesTable)) {
    echo "✓ Table 'invoices' verified/created.\n";
} else {
    die("✗ Error creating 'invoices': " . mysqli_error($conn) . "\n");
}

// 5. Create activity_logs table if not exists
$activityLogsTable = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $activityLogsTable)) {
    echo "✓ Table 'activity_logs' verified/created.\n";
} else {
    die("✗ Error creating 'activity_logs': " . mysqli_error($conn) . "\n");
}

echo "Database Setup complete!\n";
?>
