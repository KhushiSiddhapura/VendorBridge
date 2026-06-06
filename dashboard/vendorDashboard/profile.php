<?php
require_once '../../auth/session_helper.php';
require_once '../../config/connection.php';
requireRoles(['vendor']);

$vendor_id = $_SESSION['id'];

// Fetch latest user details
$vendor_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $vendor_id LIMIT 1");
if (!$vendor_res || mysqli_num_rows($vendor_res) === 0) {
    die("Vendor session context invalid.");
}
$vendor = mysqli_fetch_assoc($vendor_res);

// Handle POST Update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname'] ?? ''));
    $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $country = mysqli_real_escape_string($conn, trim($_POST['country'] ?? ''));
    $category = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $gst_number = mysqli_real_escape_string($conn, trim($_POST['gst_number'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if (empty($firstname) || empty($lastname) || empty($phone) || empty($country) || empty($category) || empty($gst_number)) {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Please fill all required profile fields.'
        ];
    } else {
        $update_sql = "UPDATE users SET 
                       firstname = '$firstname', 
                       lastname = '$lastname', 
                       phone = '$phone', 
                       country = '$country', 
                       category = '$category', 
                       gst_number = '$gst_number', 
                       description = '$description' 
                       WHERE id = $vendor_id";
                       
        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Profile updated successfully!'
            ];
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['toast'] = [
                'type' => 'fail',
                'message' => 'Database Update Error: ' . mysqli_error($conn)
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorBridge - My Profile</title>
    <link rel="stylesheet" href="vendorDashboard.css">
    <link rel="stylesheet" href="../../toaster/toaster.css">
    <script src="../../toaster/toaster.js"></script>
    <style>
        .profile-layout-grid {
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;
            gap: 24px;
            margin-top: 1.5rem;
        }
        .profile-side-card {
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .profile-avatar-giant {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #ffffff;
            font-size: 3rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px var(--primary-glow);
            margin-bottom: 20px;
        }
        .profile-side-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 4px;
        }
        .profile-side-role {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            background-color: var(--primary-light);
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .profile-meta-list {
            width: 100%;
            border-top: 1px solid var(--panel-border);
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: left;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .meta-label {
            color: var(--text-muted);
        }
        .meta-value {
            font-weight: 600;
            color: var(--text-title);
        }
        .profile-form-card {
            background: var(--panel-bg);
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            padding: 30px;
        }
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 8px;
        }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-body);
        }
        .form-control {
            padding: 10px 12px;
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            outline: none;
            background-color: #f8fafc;
            color: var(--text-title);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .form-control:disabled {
            background-color: #f1f5f9;
            color: var(--text-muted);
            cursor: not-allowed;
        }
        .textarea-control {
            height: 120px;
            resize: vertical;
        }
        .btn-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
    </style>
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

    <div class="app-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="vendorDashboard.php">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="quotations.php">
                            <span class="nav-icon">📁</span>
                            Quotations
                        </a>
                    </li>
                    <li class="active">
                        <a href="profile.php">
                            <span class="nav-icon">👤</span>
                            Profile
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Panel -->
        <main class="app-content">
            
            <section class="content-header">
                <h1 class="welcome-title">My Business Profile</h1>
                <p class="welcome-subtitle">Manage supplier identity cards and credentials synced with the procurement officers</p>
            </section>

            <div class="profile-layout-grid">
                <!-- Left panel: visual info card -->
                <div class="profile-side-card">
                    <div class="profile-avatar-giant">
                        <?= strtoupper(substr($vendor['firstname'], 0, 1)) ?>
                    </div>
                    <h2 class="profile-side-name"><?= htmlspecialchars($vendor['firstname'] . ' ' . $vendor['lastname']) ?></h2>
                    <span class="profile-side-role"><?= htmlspecialchars($vendor['role']) ?></span>
                    
                    <div class="profile-meta-list">
                        <div class="meta-row">
                            <span class="meta-label">Username:</span>
                            <span class="meta-value">@<?= htmlspecialchars($vendor['username']) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Email:</span>
                            <span class="meta-value"><?= htmlspecialchars($vendor['email']) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value"><?= htmlspecialchars($vendor['category'] ?? 'Not Set') ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">GST No:</span>
                            <span class="meta-value"><?= htmlspecialchars($vendor['gst_number'] ?? 'Not Set') ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Status:</span>
                            <span class="meta-value" style="color: #059669;">Verified Sourcing</span>
                        </div>
                    </div>
                </div>

                <!-- Right panel: Update Form -->
                <div class="profile-form-card">
                    <h2 class="form-section-title">Supplier Details Form</h2>
                    
                    <form method="POST" action="profile.php">
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="firstname">First Name*</label>
                                <input type="text" name="firstname" id="firstname" class="form-control" value="<?= htmlspecialchars($vendor['firstname']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="lastname">Last Name*</label>
                                <input type="text" name="lastname" id="lastname" class="form-control" value="<?= htmlspecialchars($vendor['lastname']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number*</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($vendor['phone']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="country">Country*</label>
                                <input type="text" name="country" id="country" class="form-control" value="<?= htmlspecialchars($vendor['country']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="category">Business Category*</label>
                                <select name="category" id="category" class="form-control" style="width: 100%;" required>
                                    <option value="" disabled <?= empty($vendor['category']) ? 'selected' : '' ?>>Select Category</option>
                                    <option value="Furniture" <?= ($vendor['category'] === 'Furniture') ? 'selected' : '' ?>>Furniture</option>
                                    <option value="IT" <?= ($vendor['category'] === 'IT') ? 'selected' : '' ?>>IT</option>
                                    <option value="Constructions" <?= ($vendor['category'] === 'Constructions') ? 'selected' : '' ?>>Constructions</option>
                                    <option value="Logistics" <?= ($vendor['category'] === 'Logistics') ? 'selected' : '' ?>>Logistics</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="gst_number">GST Number*</label>
                                <input type="text" name="gst_number" id="gst_number" class="form-control" value="<?= htmlspecialchars($vendor['gst_number'] ?? '') ?>" placeholder="e.g. 27AAAAA1111A1Z1" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Business Info & GSTIN Details</label>
                            <textarea name="description" id="description" class="form-control textarea-control" placeholder="Enter GSTIN, PAN, primary services, and terms..."><?= htmlspecialchars($vendor['description'] ?? '') ?></textarea>
                        </div>

                        <div class="btn-row">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

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
            }
        });
    </script>

    <?php if(isset($_SESSION['toast'])): ?>
    <script>
    showToast(
        <?= json_encode($_SESSION['toast']['message']) ?>,
        <?= json_encode($_SESSION['toast']['type']) ?>
    );
    </script>
    <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
</body>
</html>
