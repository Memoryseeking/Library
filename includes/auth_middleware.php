<?php
require_once 'JWT.php';

function authenticate() {
    // 检查session
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // 检查JWT token
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $payload = JWT::validateToken($token);
        
        if ($payload) {
            // 更新session
            $_SESSION['user_id'] = $payload['user_id'];
            $_SESSION['username'] = $payload['username'];
            $_SESSION['role'] = $payload['role'];
            return true;
        }
    }
    
    return false;
}

function requireAuth() {
    if (!authenticate()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    if (!authenticate() || $_SESSION['role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

function getCurrentUser() {
    if (authenticate()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
} 