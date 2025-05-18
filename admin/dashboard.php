<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 获取统计数据
try {
    // 总图书数
    $stmt = $pdo->query("SELECT COUNT(*) FROM books");
    $total_books = $stmt->fetchColumn();
    
    // 总用户数
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    // 总借阅数
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_records");
    $total_borrows = $stmt->fetchColumn();
    
    // 当前借阅中
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'borrowed'");
    $active_borrows = $stmt->fetchColumn();
    
    // 已逾期
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM borrow_records 
        WHERE status = 'borrowed' AND due_date < CURRENT_TIMESTAMP
    ");
    $overdue_borrows = $stmt->fetchColumn();
    
    // 待审核评论
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved = 0");
    $pending_reviews = $stmt->fetchColumn();
    
    // 最近借阅记录
    $stmt = $pdo->query("
        SELECT br.*, b.title, u.username 
        FROM borrow_records br 
        JOIN books b ON br.book_id = b.id 
        JOIN users u ON br.user_id = u.id 
        ORDER BY br.borrow_date DESC 
        LIMIT 5
    ");
    $recent_borrows = $stmt->fetchAll();
    
    // 待审核评论
    $stmt = $pdo->query("
        SELECT r.*, b.title as book_title, u.username 
        FROM reviews r 
        JOIN books b ON r.book_id = b.id 
        JOIN users u ON r.user_id = u.id 
        WHERE r.approved = 0 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $pending_reviews_list = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '获取统计数据失败：' . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0">管理员仪表盘</h2>
            <p class="text-muted">欢迎回来，<?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger fade-in">
            <?php 
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- 统计卡片 -->
        <div class="col-md-3 mb-4">
            <div class="stat-card bg-primary">
                <div class="card-body">
                    <h5 class="card-title">总图书数</h5>
                    <h2 class="card-text"><?php echo $total_books; ?></h2>
                    <a href="books.php" class="text-white text-decoration-none">查看详情 →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card bg-success">
                <div class="card-body">
                    <h5 class="card-title">总用户数</h5>
                    <h2 class="card-text"><?php echo $total_users; ?></h2>
                    <a href="users.php" class="text-white text-decoration-none">查看详情 →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card bg-info">
                <div class="card-body">
                    <h5 class="card-title">总借阅数</h5>
                    <h2 class="card-text"><?php echo $total_borrows; ?></h2>
                    <a href="borrows.php" class="text-white text-decoration-none">查看详情 →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">待审核评论</h5>
                    <h2 class="card-text"><?php echo $pending_reviews; ?></h2>
                    <a href="reviews.php" class="text-white text-decoration-none">查看详情 →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 最近借阅记录 -->
        <div class="col-md-6 mb-4">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">最近借阅记录</h5>
                    <a href="borrows.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>图书</th>
                                    <th>借阅日期</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_borrows as $borrow): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrow['username']); ?></td>
                                        <td><?php echo htmlspecialchars($borrow['title']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($borrow['borrow_date'])); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($borrow['status']) {
                                                case 'borrowed':
                                                    $status_class = 'text-primary';
                                                    $status_text = '借阅中';
                                                    if (strtotime($borrow['due_date']) < time()) {
                                                        $status_class = 'text-danger';
                                                        $status_text = '已逾期';
                                                    }
                                                    break;
                                                case 'returned':
                                                    $status_class = 'text-success';
                                                    $status_text = '已归还';
                                                    break;
                                                case 'overdue':
                                                    $status_class = 'text-danger';
                                                    $status_text = '已逾期';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 待审核评论 -->
        <div class="col-md-6 mb-4">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">待审核评论</h5>
                    <a href="reviews.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>图书</th>
                                    <th>评分</th>
                                    <th>评论</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_reviews_list as $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['username']); ?></td>
                                        <td><?php echo htmlspecialchars($review['book_title']); ?></td>
                                        <td>
                                            <div class="rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $review['rating'] ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($review['comment']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 快捷操作 -->
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-header">
                    <h5 class="card-title mb-0">快捷操作</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="books.php" class="btn btn-primary w-100">
                                <i class="fas fa-book me-2"></i>图书管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users.php" class="btn btn-success w-100">
                                <i class="fas fa-users me-2"></i>用户管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="categories.php" class="btn btn-info w-100">
                                <i class="fas fa-tags me-2"></i>分类管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="borrows.php" class="btn btn-warning w-100">
                                <i class="fas fa-book-reader me-2"></i>借阅管理
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 