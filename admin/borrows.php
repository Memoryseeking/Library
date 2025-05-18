<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 处理状态更新请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $record_id = (int)$_POST['record_id'];
    $status = $_POST['status'];
    
    try {
        $pdo->beginTransaction();
        
        // 获取借阅记录信息
        $stmt = $pdo->prepare("
            SELECT br.*, b.id as book_id 
            FROM borrow_records br
            JOIN books b ON br.book_id = b.id
            WHERE br.id = ?
        ");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            throw new Exception('借阅记录不存在');
        }
        
        // 更新借阅记录状态
        $stmt = $pdo->prepare("
            UPDATE borrow_records 
            SET status = ?, 
                return_date = CASE 
                    WHEN ? = 'returned' THEN CURRENT_TIMESTAMP 
                    ELSE return_date 
                END
            WHERE id = ?
        ");
        $stmt->execute([$status, $status, $record_id]);
        
        // 如果是归还操作，更新图书库存
        if ($status === 'returned' && $record['status'] === 'borrowed') {
            $stmt = $pdo->prepare("
                UPDATE books 
                SET stock_quantity = stock_quantity + 1 
                WHERE id = ?
            ");
            $stmt->execute([$record['book_id']]);
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = '借阅状态更新成功';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = '操作失败：' . $e->getMessage();
    }
    
    redirect('/admin/borrows.php');
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 构建查询
$where = [];
$params = [];

if ($search) {
    $where[] = "(b.title LIKE ? OR u.username LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

if ($status) {
    $where[] = "br.status = ?";
    $params[] = $status;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM borrow_records br 
              LEFT JOIN users u ON br.user_id = u.id 
              LEFT JOIN books b ON br.book_id = b.id 
              $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取借阅记录
$sql = "SELECT br.*, b.title, b.author, b.isbn, b.cover_image, u.username 
        FROM borrow_records br 
        LEFT JOIN books b ON br.book_id = b.id 
        LEFT JOIN users u ON br.user_id = u.id 
        $where_clause 
        ORDER BY br.borrow_date DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$borrows = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2>借阅管理</h2>
    </div>
</div>

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

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="搜索图书或用户..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">所有状态</option>
                    <option value="borrowed" <?php echo $status === 'borrowed' ? 'selected' : ''; ?>>借阅中</option>
                    <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>已归还</option>
                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>已逾期</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">搜索</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>图书</th>
                <th>借阅日期</th>
                <th>应还日期</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrows as $record): ?>
                <tr>
                    <td><?php echo $record['id']; ?></td>
                    <td><?php echo htmlspecialchars($record['username']); ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if (!empty($record['cover_image'])): ?>
                                <img src="/uploads/covers/<?php echo htmlspecialchars($record['cover_image']); ?>" 
                                     class="me-3" style="width: 50px; height: 70px; object-fit: cover;">
                            <?php else: ?>
                                <div class="me-3" style="width: 50px; height: 70px; background-color: #f8f9fa; display: flex; align-items-center; justify-content: center;">
                                    <i class="fas fa-book text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($record['title']); ?></h6>
                                <small class="text-muted">
                                    作者：<?php echo htmlspecialchars($record['author'] ?? '未知'); ?><br>
                                    ISBN：<?php echo htmlspecialchars($record['isbn'] ?? '未知'); ?>
                                </small>
                            </div>
                        </div>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($record['borrow_date'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($record['due_date'])); ?></td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        
                        switch ($record['status']) {
                            case 'borrowed':
                                $status_class = 'text-primary';
                                $status_text = '借阅中';
                                if (strtotime($record['due_date']) < time()) {
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
                        <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </td>
                    <td>
                        <?php if ($record['status'] === 'borrowed'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <input type="hidden" name="status" value="returned">
                                <button type="submit" name="update_status" class="btn btn-sm btn-success">标记归还</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($record['status'] === 'borrowed' && strtotime($record['due_date']) < time()): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <input type="hidden" name="status" value="overdue">
                                <button type="submit" name="update_status" class="btn btn-sm btn-danger">标记逾期</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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

<?php require_once '../includes/footer.php'; ?> 