<?php
require_once 'includes/config.php';

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// 构建查询
$where = [];
$params = [];

if ($search) {
    $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($category) {
    $where[] = "category_id = ?";
    $params[] = $category;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM books $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取图书列表
$sql = "SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        $where_clause 
        ORDER BY b.id DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// 获取所有分类
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>图书列表</h2>
    </div>
    <div class="col-md-4">
        <form class="d-flex" method="GET">
            <input type="text" name="search" class="form-control me-2" placeholder="搜索图书..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">搜索</button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => 0])); ?>" 
               class="btn btn-outline-primary <?php echo $category === 0 ? 'active' : ''; ?>">
                全部
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['id']])); ?>" 
                   class="btn btn-outline-primary <?php echo $category === $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
    <?php foreach ($books as $book): ?>
        <div class="col">
            <div class="card h-100">
                <?php if ($book['cover_image']): ?>
                    <img src="/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover" alt="<?php echo htmlspecialchars($book['title']); ?>">
                <?php else: ?>
                    <div class="card-img-top book-cover bg-light d-flex align-items-center justify-content-center">
                        <span class="text-muted">无封面图片</span>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                    <p class="card-text">
                        <small class="text-muted">作者：<?php echo htmlspecialchars($book['author']); ?></small><br>
                        <small class="text-muted">分类：<?php echo htmlspecialchars($book['category_name']); ?></small><br>
                        <small class="text-muted">ISBN：<?php echo htmlspecialchars($book['isbn']); ?></small>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="detail.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                        <?php if (isLoggedIn() && $book['stock_quantity'] > 0): ?>
                            <form method="POST" action="borrow.php" class="d-inline">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">借阅</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">上一页</a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">下一页</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 