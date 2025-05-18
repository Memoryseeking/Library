<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/reviews.php');
}

// 获取评论ID和操作类型
$review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$review_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['error_message'] = '无效的请求';
    redirect('/admin/reviews.php');
}

try {
    if ($action === 'approve') {
        // 通过评论
        $stmt = $pdo->prepare("UPDATE reviews SET approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = '评论已通过审核';
    } else {
        // 拒绝评论（删除）
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = '评论已拒绝并删除';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = '操作失败：' . $e->getMessage();
}

redirect('/admin/reviews.php'); 