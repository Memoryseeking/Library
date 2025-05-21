<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_ids = json_decode($_POST['category_ids'] ?? '[]', true);
    
    if (empty($category_ids)) {
        $_SESSION['error_message'] = '请选择要删除的分类';
        redirect('/admin/categories.php');
    }
    
    try {
        // 开始事务
        $pdo->beginTransaction();
        
        // 检查分类是否包含图书
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id IN (" . str_repeat('?,', count($category_ids) - 1) . "?)");
        $stmt->execute($category_ids);
        $bookCount = $stmt->fetchColumn();
        
        if ($bookCount > 0) {
            throw new Exception('选中的分类中包含图书，无法删除');
        }
        
        // 删除分类
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id IN (" . str_repeat('?,', count($category_ids) - 1) . "?)");
        $stmt->execute($category_ids);
        
        // 提交事务
        $pdo->commit();
        
        $_SESSION['success_message'] = '已成功删除选中的分类';
        redirect('/admin/categories.php');
        
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '删除分类失败：' . $e->getMessage();
        redirect('/admin/categories.php');
    }
} else {
    redirect('/admin/categories.php');
} 