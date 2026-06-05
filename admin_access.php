<?php
// Admin access control middleware
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ensure_admin_access(): void {
    $role = $_SESSION['role'] ?? '';
    
    // Require admin role
    if (strtolower($role) !== 'admin') {
        // Redirect non-admin users to login
        header('Location: login.php');
        exit;
    }
}

function is_admin(): bool {
    return strtolower($_SESSION['role'] ?? '') === 'admin';
}

// Auto-redirect admin users away from non-admin pages if they accidentally access them
function redirect_admin_if_needed(): void {
    if (is_admin() && !str_contains($_SERVER['REQUEST_URI'], 'admin_')) {
        header('Location: admin_dashboard.php');
        exit;
    }
}
?>
