<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
    
    // 验证验证码
    if (empty($captcha) || !isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
        $_SESSION['error_message'] = '验证码错误';
        redirect('/admin/user_edit.php?id=' . $user_id);
    }
    
    try {
        // 检查用户是否存在
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('用户不存在');
        }
        
        // 不允许删除最后一个管理员
        if ($user['role'] === 'admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount <= 1) {
                throw new Exception('不能删除最后一个管理员账户');
            }
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 删除用户的借阅记录
        $stmt = $pdo->prepare("DELETE FROM borrow_records WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // 删除用户的评论
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // 删除用户头像文件
        if ($user['avatar']) {
            $uploader = new FileUploader(__DIR__ . '/../uploads/avatars');
            $uploader->delete($user['avatar']);
        }
        
        // 删除用户账户
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // 提交事务
        $pdo->commit();
        
        // 设置成功消息
        $_SESSION['success_message'] = '用户账户已成功注销';
        redirect('/admin/users.php');
        
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '注销用户账户失败：' . $e->getMessage();
        redirect('/admin/user_edit.php?id=' . $user_id);
    }
} else {
    redirect('/admin/users.php');
} 