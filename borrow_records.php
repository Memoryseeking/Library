<?php
require_once 'includes/config.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    $_SESSION['error_message'] = '请先登录后查看借阅记录';
    redirect('/login.php');
}

$user_id = $_SESSION['user']['id']; // 从会话中获取用户ID

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 构建查询
$where = ["br.user_id = ?"];
$params = [$user_id];

if ($status) {
    $where[] = "br.status = ?";
    $params[] = $status;
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM borrow_records br $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取借阅记录
$sql = "SELECT br.*, b.title, b.author, b.isbn, b.cover_image 
        FROM borrow_records br 
        LEFT JOIN books b ON br.book_id = b.id 
        $where_clause 
        ORDER BY br.borrow_date DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// 处理归还请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $record_id = (int)$_POST['record_id'];
    
    try {
        $pdo->beginTransaction();
        
        // 检查记录是否存在且属于当前用户
        $stmt = $pdo->prepare("
            SELECT br.*, b.id as book_id 
            FROM borrow_records br
            JOIN books b ON br.book_id = b.id
            WHERE br.id = ? AND br.user_id = ? AND br.status = 'borrowed'
        ");
        $stmt->execute([$record_id, $_SESSION['user']['id']]);
        $record = $stmt->fetch();
        
        if (!$record) {
            throw new Exception('借阅记录不存在或已归还');
        }
        
        // 更新借阅记录
        $stmt = $pdo->prepare("
            UPDATE borrow_records 
            SET status = 'returned', return_date = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$record_id]);
        
        // 更新图书库存
        $stmt = $pdo->prepare("
            UPDATE books 
            SET stock_quantity = stock_quantity + 1 
            WHERE id = ?
        ");
        $stmt->execute([$record['book_id']]);
        
        $pdo->commit();
        $_SESSION['success_message'] = '图书归还成功！';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    redirect('/borrow_records.php');
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>我的借阅记录</h2>
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
                    <select name="status" class="form-select">
                        <option value="">所有状态</option>
                        <option value="borrowed" <?php echo $status === 'borrowed' ? 'selected' : ''; ?>>借阅中</option>
                        <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>已归还</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>已逾期</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">筛选</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>图书信息</th>
                    <th>借阅日期</th>
                    <th>应还日期</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($record['cover_image'])): ?>
                                    <img src="/uploads/covers/<?php echo htmlspecialchars($record['cover_image']); ?>" 
                                         class="me-3" style="width: 50px; height: 70px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="me-3" style="width: 50px; height: 70px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-book text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($record['title']); ?></h6>
                                    <small class="text-muted">
                                        作者：<?php echo htmlspecialchars($record['author']); ?><br>
                                        ISBN：<?php echo htmlspecialchars($record['isbn']); ?>
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
                                    <button type="submit" name="return_book" class="btn btn-success btn-sm">归还</button>
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
</div>

<?php require_once 'includes/footer.php'; ?> 