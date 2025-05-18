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
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $total_users = $stmt->fetchColumn();
    
    // 总借阅数
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_records");
    $total_borrows = $stmt->fetchColumn();
    
    // 当前借阅中
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'borrowed'");
    $active_borrows = $stmt->fetchColumn();
    
    // 已逾期
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'overdue'");
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
        SELECT r.*, b.title, u.username 
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
            <h2 class="mb-0">欢迎回来，<?php echo htmlspecialchars($_SESSION['user']['username']); ?></h2>
            <p class="text-muted">管理图书、用户和借阅记录</p>
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

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white fade-in">
                <div class="card-body">
                    <h5 class="card-title">总图书数</h5>
                    <h2 class="mb-0"><?php echo $total_books; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white fade-in">
                <div class="card-body">
                    <h5 class="card-title">总用户数</h5>
                    <h2 class="mb-0"><?php echo $total_users; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white fade-in">
                <div class="card-body">
                    <h5 class="card-title">当前借阅</h5>
                    <h2 class="mb-0"><?php echo $active_borrows; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white fade-in">
                <div class="card-body">
                    <h5 class="card-title">待审核评论</h5>
                    <h2 class="mb-0"><?php echo $pending_reviews; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 最近借阅记录 -->
        <div class="col-md-8">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>最近借阅记录
                    </h5>
                    <a href="/admin/borrows.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_borrows): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>图书</th>
                                        <th>借阅者</th>
                                        <th>借阅日期</th>
                                        <th>状态</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_borrows as $borrow): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($borrow['title']); ?></td>
                                            <td><?php echo htmlspecialchars($borrow['username']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($borrow['borrow_date'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'borrowed' => 'primary',
                                                    'returned' => 'success',
                                                    'overdue' => 'danger'
                                                ][$borrow['status']];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php
                                                    echo [
                                                        'borrowed' => '借阅中',
                                                        'returned' => '已归还',
                                                        'overdue' => '已逾期'
                                                    ][$borrow['status']];
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">暂无借阅记录</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 待审核评论 -->
        <div class="col-md-4">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments me-2"></i>待审核评论
                    </h5>
                    <a href="/admin/reviews.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <?php if ($pending_reviews_list): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pending_reviews_list as $review): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($review['title']); ?></h6>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <small class="text-muted">
                                        评论者：<?php echo htmlspecialchars($review['username']); ?> | 
                                        评分：<?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">暂无待审核评论</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 快捷操作 -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>快捷操作
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="/admin/books.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-book me-2"></i>图书管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/users.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-users me-2"></i>用户管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/categories.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-tags me-2"></i>分类管理
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/borrows.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-history me-2"></i>借阅管理
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 