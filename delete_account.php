<?php
require_once 'includes/config.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
    
    // 验证验证码
    if (empty($captcha) || !isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
        $_SESSION['error_message'] = '验证码错误';
        redirect('/profile.php');
    }
    
    try {
        $user_id = $_SESSION['user']['id'];
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 删除用户的借阅记录
        $stmt = $pdo->prepare("DELETE FROM borrow_records WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // 删除用户的评论
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // 删除用户头像文件
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user['avatar']) {
            $uploader = new FileUploader(__DIR__ . '/uploads/avatars');
            $uploader->delete($user['avatar']);
        }
        
        // 删除用户账户
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // 提交事务
        $pdo->commit();
        
        // 清除会话
        session_destroy();
        
        // 设置成功消息
        $_SESSION['success_message'] = '账户已成功注销';
        redirect('/login.php');
        
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '注销账户失败：' . $e->getMessage();
        redirect('/profile.php');
    }
} else {
    redirect('/profile.php');
} 