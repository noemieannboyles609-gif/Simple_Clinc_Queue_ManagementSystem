<?php
// includes/auth.php - Authentication & Session Helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user details
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'counter_id' => $_SESSION['counter_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
    ];
}

/**
 * Login user and store in session
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['counter_id'] = $user['counter_id'] ?? null;
    $_SESSION['email'] = $user['email'] ?? null;
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Check if the user has a specific role (or array of roles)
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['role'], $roles);
}

/**
 * Guard a page: Require login
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Guard a page: Require specific role
 */
function requireRole($roles, $redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!hasRole($roles)) {
        header("Location: index.php?unauthorized=1");
        exit;
    }
}
