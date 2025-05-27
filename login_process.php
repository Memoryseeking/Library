<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/JWT.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = '用户名和密码不能为空';
        header('Location: login.php');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // 生成JWT token
            $token = JWT::generateToken($user);
            
            // 将token存储在cookie中
            setcookie('auth_token', $token, time() + 3600, '/', '', true, true);
            
            // 设置session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // 根据用户角色重定向
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $_SESSION['error'] = '用户名或密码错误';
            header('Location: login.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = '登录失败，请稍后重试';
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
} 