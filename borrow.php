<?php
require_once 'includes/config.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    $_SESSION['error_message'] = '请先登录后再借阅图书';
    redirect('/login.php');
}

// 检查是否是POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/list.php');
}

$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$user_id = $_SESSION['user']['id']; // 从会话中获取用户ID

if (!$book_id) {
    $_SESSION['error_message'] = '无效的图书ID';
    redirect('/list.php');
}

try {
    $pdo->beginTransaction();

    // 检查图书是否存在且有库存
    $stmt = $pdo->prepare("SELECT stock_quantity FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        throw new Exception('图书不存在');
    }

    if ($book['stock_quantity'] <= 0) {
        throw new Exception('图书库存不足');
    }

    // 检查用户是否已经借阅了这本书
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM borrow_records 
        WHERE user_id = ? AND book_id = ? AND status = 'borrowed'
    ");
    $stmt->execute([$user_id, $book_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('您已经借阅了这本书');
    }

    // 设置借阅期限（14天）
    $due_date = date('Y-m-d H:i:s', strtotime('+14 days'));

    // 创建借阅记录
    $stmt = $pdo->prepare("
        INSERT INTO borrow_records (user_id, book_id, due_date, status) 
        VALUES (?, ?, ?, 'borrowed')
    ");
    $stmt->execute([$user_id, $book_id, $due_date]);

    // 更新图书库存
    $stmt = $pdo->prepare("
        UPDATE books 
        SET stock_quantity = stock_quantity - 1 
        WHERE id = ?
    ");
    $stmt->execute([$book_id]);

    $pdo->commit();
    $_SESSION['success_message'] = '借阅成功，请在' . date('Y-m-d', strtotime($due_date)) . '前归还';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = '借阅失败：' . $e->getMessage();
}

redirect("/detail.php?id=$book_id"); 