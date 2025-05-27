<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = '评论删除成功';
    } catch (Exception $e) {
        $_SESSION['error_message'] = '删除失败：' . $e->getMessage();
    }
    redirect('/admin/reviews.php');
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// 构建查询
$where = [];
$params = [];

if ($search) {
    $where[] = "(b.title LIKE ? OR u.username LIKE ? OR r.comment LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($status === 'approved') {
    $where[] = "r.approved = 1";
} elseif ($status === 'pending') {
    $where[] = "r.approved = 0";
}

if ($rating) {
    $where[] = "r.rating = ?";
    $params[] = $rating;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM reviews r 
              LEFT JOIN users u ON r.user_id = u.id 
              LEFT JOIN books b ON r.book_id = b.id 
              $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取评论列表
$sql = "SELECT r.*, u.username, b.title as book_title 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN books b ON r.book_id = b.id 
        $where_clause 
        ORDER BY r.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0">评论管理</h2>
            <p class="text-muted">管理用户评论和评分</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success fade-in">
            <?php 
            echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger fade-in">
            <?php 
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card search-box mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="搜索图书、用户或评论..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">所有状态</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已审核</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="rating" class="form-select">
                        <option value="0">所有评分</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $rating === $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> 星
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>筛选
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card fade-in">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户</th>
                            <th>图书</th>
                            <th>评分</th>
                            <th>评论内容</th>
                            <th>状态</th>
                            <th>时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
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
                                <td>
                                    <?php if ($review['approved']): ?>
                                        <span class="badge bg-success">已审核</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">待审核</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if (!$review['approved']): ?>
                                            <form method="POST" action="review_action.php" class="d-inline">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check me-1"></i>通过
                                                </button>
                                            </form>
                                            <form method="POST" action="review_action.php" class="d-inline">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times me-1"></i>拒绝
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('确定要删除此评论吗？');">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="delete_review" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash me-1"></i>删除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 