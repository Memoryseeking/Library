<?php
// 数据库配置
$db_host = 'localhost';
$db_name = 'library';
$db_user = 'rootuser';
$db_pass = 'rootpass';

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 会话设置
session_start();

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 数据库连接
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 通用函数
function redirect($url) {
    header("Location: $url");
    exit();
}

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

// 检查是否是管理员
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

// 要求管理员权限
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['error_message'] = '需要管理员权限';
        redirect('/login.php');
    }
}

// 获取当前用户ID
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user']['id'] : null;
}

// 获取当前用户名
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['user']['username'] : null;
}

// 获取当前用户角色
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['user']['role'] : null;
}

// 要求用户登录
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = '请先登录';
        redirect('/login.php');
    }
} 