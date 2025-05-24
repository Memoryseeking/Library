<?php
require_once '../includes/config.php';
require_once '../includes/FileUploader.php';

// 检查管理员权限
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_ids = json_decode($_POST['book_ids'] ?? '[]', true);
    
    if (empty($book_ids)) {
        $_SESSION['error_message'] = '请选择要删除的图书';
        redirect('/admin/books.php');
    }
    
    try {
        // 开始事务
        $pdo->beginTransaction();
        
        // 获取图书封面信息
        $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id IN (" . str_repeat('?,', count($book_ids) - 1) . "?)");
        $stmt->execute($book_ids);
        $books = $stmt->fetchAll();
        
        // 删除图书封面文件
        $uploader = new \Library\Includes\FileUploader(__DIR__ . '/../uploads/covers');
        foreach ($books as $book) {
            if ($book['cover_image']) {
                $uploader->delete($book['cover_image']);
            }
        }
        
        // 删除相关的借阅记录
        $stmt = $pdo->prepare("DELETE FROM borrow_records WHERE book_id IN (" . str_repeat('?,', count($book_ids) - 1) . "?)");
        $stmt->execute($book_ids);
        
        // 删除相关的评论
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE book_id IN (" . str_repeat('?,', count($book_ids) - 1) . "?)");
        $stmt->execute($book_ids);
        
        // 删除图书
        $stmt = $pdo->prepare("DELETE FROM books WHERE id IN (" . str_repeat('?,', count($book_ids) - 1) . "?)");
        $stmt->execute($book_ids);
        
        // 提交事务
        $pdo->commit();
        
        $_SESSION['success_message'] = '已成功删除选中的图书';
        redirect('/admin/books.php');
        
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '删除图书失败：' . $e->getMessage();
        redirect('/admin/books.php');
    }
} else {
    redirect('/admin/books.php');
} 