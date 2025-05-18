<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // 不允许删除自己
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error_message'] = '不能删除当前登录的用户';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = '用户删除成功';
        } catch (Exception $e) {
            $_SESSION['error_message'] = '删除失败：' . $e->getMessage();
        }
    }
    redirect('/admin/users.php');
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

// 构建查询
$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取用户列表
$sql = "SELECT id, username, email, role, created_at FROM users 
        $where_clause 
        ORDER BY id DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 调试信息
error_log("Users data: " . print_r($users, true));

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>用户管理</h2>
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
                <input type="text" name="search" class="form-control" placeholder="搜索用户..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">所有角色</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>普通用户</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
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
                <th>用户名</th>
                <th>邮箱</th>
                <th>角色</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                            <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                        <?php if (isset($_SESSION['user_id']) && $user['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('确定要删除此用户吗？');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger">删除</button>
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