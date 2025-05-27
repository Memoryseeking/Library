<?php
require_once 'includes/config.php';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$book_id) {
    redirect('/list.php');
}

// 获取图书信息
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    redirect('/list.php');
}

// 获取图书评论
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.avatar 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.book_id = ? AND r.approved = 1 
    ORDER BY r.created_at DESC
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll();

// 获取评论统计
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(CAST(rating AS DECIMAL(3,1))) as avg_rating
    FROM reviews 
    WHERE book_id = ? AND approved = 1
");
$stmt->execute([$book_id]);
$review_stats = $stmt->fetch();

// 处理评论提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // 检查用户是否登录
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = '请先登录后再发表评论';
        redirect('/login.php');
    }

    $book_id = (int)$_POST['book_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user']['id'];

    try {
        // 检查是否已经评论过
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('您已经评论过这本书了');
        }

        // 插入评论
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, book_id, rating, comment, approved) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$user_id, $book_id, $rating, $comment]);
        
        $_SESSION['success_message'] = '评论提交成功，等待管理员审核';
        redirect("/detail.php?id=$book_id");
    } catch (Exception $e) {
        $_SESSION['error_message'] = '评论提交失败：' . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <?php if ($book['cover_image']): ?>
            <img src="/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($book['title']); ?>">
        <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 400px;">
                <span class="text-muted">无封面图片</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <h1><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="text-muted">作者：<?php echo htmlspecialchars($book['author']); ?></p>
        <p class="text-muted">ISBN：<?php echo htmlspecialchars($book['isbn']); ?></p>
        <p class="text-muted">分类：<?php echo htmlspecialchars($book['category_name']); ?></p>
        <p class="text-muted">出版日期：<?php echo $book['published_date'] ? date('Y-m-d', strtotime($book['published_date'])) : '未知'; ?></p>
        <p class="text-muted">库存数量：<?php echo $book['stock_quantity']; ?></p>
        
        <div class="mb-4">
            <h5>图书简介</h5>
            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
        </div>

        <?php if (isLoggedIn() && $book['stock_quantity'] > 0): ?>
            <form method="POST" action="borrow.php" class="mb-4">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <button type="submit" class="btn btn-primary">借阅此书</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">读者评论</h5>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($review_stats['total_reviews'] > 0): ?>
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <h6 class="mb-0 me-2">平均评分：</h6>
                    <div class="text-warning">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($review_stats['avg_rating'])): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - 0.5 <= $review_stats['avg_rating']): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-2"><?php echo number_format($review_stats['avg_rating'], 1); ?></span>
                    </div>
                </div>
                <p class="text-muted mb-0">共 <?php echo $review_stats['total_reviews']; ?> 条评论</p>
            </div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                <div class="mb-3">
                    <label class="form-label">评分</label>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="rating<?php echo $i; ?>" required>
                            <label for="rating<?php echo $i; ?>" class="rating-star">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="comment" class="form-label">评论内容</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">提交评论</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                请<a href="login.php">登录</a>后发表评论
            </div>
        <?php endif; ?>

        <?php if ($reviews): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">
                            <?php if (!empty($review['avatar'])): ?>
                                <img src="/uploads/avatars/<?php echo htmlspecialchars($review['avatar']); ?>" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/assets/images/default-avatar.jpg" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($review['username']); ?></h6>
                            <div class="text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <small class="text-muted ms-auto">
                            <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p class="mb-0">暂无评论</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 