<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $book_id = (int)$_POST['book_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $_SESSION['success_message'] = '图书删除成功';
    } catch (Exception $e) {
        $_SESSION['error_message'] = '删除失败：' . $e->getMessage();
    }
    redirect('/admin/books.php');
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
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

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2>图书管理</h2>
    </div>
    <div class="col-auto">
        <a href="book_edit.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>添加图书
        </a>
        <button type="button" class="btn btn-danger" id="batchDeleteBtn" disabled>
            <i class="fas fa-trash-alt me-2"></i>批量删除
        </button>
    </div>
</div>

<!-- 搜索表单 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="搜索书名、作者或ISBN" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">所有分类</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>搜索
                </button>
            </div>
            <div class="col-md-2">
                <a href="books.php" class="btn btn-secondary w-100">
                    <i class="fas fa-redo me-2"></i>重置
                </a>
            </div>
        </form>
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

<div class="card">
    <div class="card-body">
        <form id="booksForm" method="POST" action="batch_delete_books.php">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>封面</th>
                            <th>书名</th>
                            <th>作者</th>
                            <th>ISBN</th>
                            <th>分类</th>
                            <th>库存</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input book-checkbox" type="checkbox" 
                                           name="book_ids[]" value="<?php echo $book['id']; ?>">
                                </div>
                            </td>
                            <td>
                                <?php if ($book['cover_image']): ?>
                                    <img src="/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         alt="封面" style="width: 50px; height: 70px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/assets/images/no-cover.jpg" alt="无封面" 
                                         style="width: 50px; height: 70px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                            <td><?php echo $book['stock_quantity']; ?></td>
                            <td>
                                <a href="book_edit.php?id=<?php echo $book['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- 批量删除确认模态框 -->
<div class="modal fade" id="batchDeleteModal" tabindex="-1" aria-labelledby="batchDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchDeleteModalLabel">确认批量删除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    此操作将永久删除选中的图书，且无法恢复。请谨慎操作！
                </div>
                <form id="batchDeleteForm" method="POST" action="batch_delete_books.php">
                    <input type="hidden" name="book_ids" id="selectedBookIds">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" form="batchDeleteForm" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-2"></i>确认删除
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const bookCheckboxes = document.querySelectorAll('.book-checkbox');
    const batchDeleteBtn = document.getElementById('batchDeleteBtn');
    const selectedBookIds = document.getElementById('selectedBookIds');
    
    // 全选/取消全选
    selectAll.addEventListener('change', function() {
        bookCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBatchDeleteButton();
    });
    
    // 更新批量删除按钮状态
    bookCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchDeleteButton);
    });
    
    function updateBatchDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.book-checkbox:checked');
        batchDeleteBtn.disabled = checkedBoxes.length === 0;
    }
    
    // 批量删除按钮点击事件
    batchDeleteBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.book-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        selectedBookIds.value = JSON.stringify(ids);
        
        const modal = new bootstrap.Modal(document.getElementById('batchDeleteModal'));
        modal.show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 