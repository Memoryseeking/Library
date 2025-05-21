<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    try {
        // 检查分类是否有关联的图书
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
        $stmt->execute([$category_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('该分类下还有图书，无法删除');
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $_SESSION['success_message'] = '分类删除成功';
    } catch (Exception $e) {
        $_SESSION['error_message'] = '删除失败：' . $e->getMessage();
    }
    redirect('/admin/categories.php');
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 构建查询
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM categories $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取分类列表
$sql = "SELECT c.*, COUNT(b.id) as book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        $where_clause 
        GROUP BY c.id 
        ORDER BY c.id DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2>分类管理</h2>
    </div>
    <div class="col-auto">
        <a href="category_edit.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>添加分类
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
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" 
                       placeholder="搜索分类名称或描述" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>搜索
                </button>
            </div>
            <div class="col-md-3">
                <a href="categories.php" class="btn btn-secondary w-100">
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
        <form id="categoriesForm" method="POST" action="batch_delete_categories.php">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>分类名称</th>
                            <th>描述</th>
                            <th>图书数量</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input category-checkbox" type="checkbox" 
                                           name="category_ids[]" value="<?php echo $category['id']; ?>">
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td><?php echo $category['book_count']; ?></td>
                            <td>
                                <a href="category_edit.php?id=<?php echo $category['id']; ?>" 
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
                    此操作将永久删除选中的分类，且无法恢复。请谨慎操作！
                </div>
                <form id="batchDeleteForm" method="POST" action="batch_delete_categories.php">
                    <input type="hidden" name="category_ids" id="selectedCategoryIds">
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
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const batchDeleteBtn = document.getElementById('batchDeleteBtn');
    const selectedCategoryIds = document.getElementById('selectedCategoryIds');
    
    // 全选/取消全选
    selectAll.addEventListener('change', function() {
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBatchDeleteButton();
    });
    
    // 更新批量删除按钮状态
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchDeleteButton);
    });
    
    function updateBatchDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
        batchDeleteBtn.disabled = checkedBoxes.length === 0;
    }
    
    // 批量删除按钮点击事件
    batchDeleteBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        selectedCategoryIds.value = JSON.stringify(ids);
        
        const modal = new bootstrap.Modal(document.getElementById('batchDeleteModal'));
        modal.show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 