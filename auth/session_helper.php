<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Calculates the relative project root prefix based on the current script URI.
 */
function getProjectRoot() {
    $current_uri = $_SERVER['SCRIPT_NAME'];
    if (strpos($current_uri, '/dashboard/adminDashboard/') !== false) {
        return '../../../';
    } elseif (strpos($current_uri, '/dashboard/') !== false) {
        return '../../';
    } elseif (strpos($current_uri, '/register/') !== false || strpos($current_uri, '/login/') !== false) {
        return '../';
    }
    return '';
}

/**
 * Checks if the user is authenticated. If not, redirects to the login screen.
 */
function checkLogin() {
    if (!isset($_SESSION['id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Please log in to access this page.'
        ];
        $root = getProjectRoot();
        header("Location: {$root}login/login.php");
        exit();
    }
}

/**
 * Checks if the logged-in user is authorized for specified roles.
 * If not, redirects to their own dashboard with a warning toast.
 * 
 * @param array $allowedRoles Array of strings containing allowed role names
 */
function requireRoles(array $allowedRoles) {
    checkLogin();
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Unauthorized access. Redirected to your dashboard.'
        ];
        
        $root = getProjectRoot();
        // Find their dashboard path
        $role = $_SESSION['role'];
        if ($role === 'admin' || $role === 'procurement_officer') {
            header("Location: {$root}dashboard/adminDashboard/dashboard/adminDashboard.php");
        } elseif ($role === 'vendor') {
            header("Location: {$root}dashboard/vendorDashboard/vendorDashboard.php");
        } elseif ($role === 'manager') {
            header("Location: {$root}dashboard/managerDashboard/managerDashboard.php");
        } else {
            header("Location: {$root}index.php");
        }
        exit();
    }
}

/**
 * Gets the display initials for the user.
 */
function getUserInitials($firstname, $lastname = '') {
    $first = !empty($firstname) ? strtoupper(substr($firstname, 0, 1)) : '';
    $last = !empty($lastname) ? strtoupper(substr($lastname, 0, 1)) : '';
    return $first . $last;
}
?>
