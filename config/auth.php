<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE', '/spta-system');

function redirectToDashboard() {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':        header('Location: ' . BASE . '/admin/dashboard.php');   break;
        case 'staff':        header('Location: ' . BASE . '/staff/dashboard.php');   break;
        case 'spta_officer': header('Location: ' . BASE . '/officer/dashboard.php'); break;
        case 'parent':       header('Location: ' . BASE . '/parent/dashboard.php');  break;
        default:             header('Location: ' . BASE . '/login.php');             break;
    }
    exit;
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE . '/login.php');
        exit;
    }
}

function requireRole(...$roles) {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: ' . BASE . '/login.php');
        exit;
    }
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']);
}

function currentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'name'    => $_SESSION['name']    ?? '',
        'email'   => $_SESSION['email']   ?? '',
        'role'    => $_SESSION['role']    ?? '',
    ];
}

function logAudit($action, $table = null, $record_id = null, $old = null, $new = null) {
    try {
        require_once __DIR__ . '/db.php';
        $db       = getDB();
        $user_id  = $_SESSION['user_id'] ?? null;
        $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
        $old_json = $old ? json_encode($old) : null;
        $new_json = $new ? json_encode($new) : null;
        $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, table_affected, record_id, old_value, new_value, ip_address) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('ississs', $user_id, $action, $table, $record_id, $old_json, $new_json, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) { /* silent fail */ }
}
