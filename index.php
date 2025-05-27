<?php
require_once 'includes/config.php';

// 获取推荐图书（评分最高的5本）
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name, 
               AVG(CAST(r.rating AS DECIMAL)) as avg_rating,
               COUNT(r.id) as review_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN reviews r ON b.id = r.book_id AND r.approved = 1
        GROUP BY b.id
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT 5
    ");
    $recommended_books = $stmt->fetchAll();
} catch (Exception $e) {
    $recommended_books = [];
}

// 获取最新图书（最近添加的5本）
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $latest_books = $stmt->fetchAll();
} catch (Exception $e) {
    $latest_books = [];
}

// 如果用户已登录，获取其最近借阅记录
$recent_borrows = [];
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("
            SELECT br.*, b.title, b.cover_image
            FROM borrow_records br
            JOIN books b ON br.book_id = b.id
            WHERE br.user_id = ?
            ORDER BY br.borrow_date DESC
            LIMIT 3
        ");
        $stmt->execute([$_SESSION['user']['id']]);
        $recent_borrows = $stmt->fetchAll();
    } catch (Exception $e) {
        // 处理错误
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <?php if (isLoggedIn() && isset($_SESSION['user'])): ?>
                <h2 class="mb-0">欢迎回来，<?php echo htmlspecialchars($_SESSION['user']['username']); ?></h2>
                <p class="text-muted">浏览和借阅您感兴趣的图书</p>
            <?php else: ?>
                <h2 class="mb-0">欢迎来到图书管理系统</h2>
                <p class="text-muted">请登录以借阅图书或查看您的借阅记录</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- 搜索框 -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card search-box">
                <div class="card-body">
                    <form action="list.php" method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="搜索图书标题、作者或ISBN...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>搜索
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 推荐图书 -->
        <div class="col-md-8">
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2"></i>推荐图书
                    </h5>
                    <a href="list.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <?php foreach ($recommended_books as $book): ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <img src="<?php echo $book['cover_image'] ? '/uploads/covers/' . htmlspecialchars($book['cover_image']) : '/assets/images/default-cover.jpg'; ?>" 
                                                 class="img-fluid rounded-start h-100 object-fit-cover" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                                        </div>
                                        <div class="col-8">
                                            <div class="card-body">
                                                <h6 class="card-title text-truncate">
                                                    <a href="detail.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </a>
                                                </h6>
                                                <p class="card-text small text-muted mb-1">
                                                    <?php echo htmlspecialchars($book['author']); ?>
                                                </p>
                                                <p class="card-text small text-muted mb-2">
                                                    <?php echo htmlspecialchars($book['category_name']); ?>
                                                </p>
                                                <div class="rating small">
                                                    <?php
                                                    $rating = round($book['avg_rating']);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $rating ? '★' : '☆';
                                                    }
                                                    ?>
                                                    <span class="text-muted ms-1">(<?php echo $book['review_count']; ?>)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 侧边栏 -->
        <div class="col-md-4">
            <?php if (isLoggedIn()): ?>
                <!-- 最近借阅 -->
                <div class="card fade-in mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>最近借阅
                        </h5>
                        <a href="borrow_records.php" class="btn btn-sm btn-primary">查看全部</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_borrows): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_borrows as $borrow): ?>
                                    <a href="detail.php?id=<?php echo $borrow['book_id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $borrow['cover_image'] ? '/uploads/covers/' . htmlspecialchars($borrow['cover_image']) : '/assets/images/default-cover.jpg'; ?>" 
                                                 class="rounded me-3" style="width: 50px; height: 70px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($borrow['title']); ?></h6>
                                                <small class="text-muted">
                                                    借阅日期：<?php echo date('Y-m-d', strtotime($borrow['borrow_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">暂无借阅记录</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 最新图书 -->
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-book me-2"></i>最新上架
                    </h5>
                    <a href="list.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($latest_books as $book): ?>
                            <a href="detail.php?id=<?php echo $book['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $book['cover_image'] ? '/uploads/covers/' . htmlspecialchars($book['cover_image']) : '/assets/images/default-cover.jpg'; ?>" 
                                         class="rounded me-3" style="width: 50px; height: 70px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($book['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($book['category_name']); ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 