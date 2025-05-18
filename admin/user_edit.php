<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$error = '';
$success = '';

// 获取用户信息
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('/admin/users.php');
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $new_password = trim($_POST['new_password'] ?? '');
    
    // 验证输入
    if (empty($username) || empty($email)) {
        $error = '请填写必填字段';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮件地址';
    } else {
        try {
            // 检查用户名是否已被其他用户使用
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('用户名已被使用');
            }
            
            // 检查邮箱是否已被其他用户使用
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('邮箱已被注册');
            }
            
            if ($user_id) {
                // 更新用户信息
                if ($new_password) {
                    // 更新密码
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, role = ?, password = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $role, $hashed_password, $user_id]);
                } else {
                    // 不更新密码
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, role = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $role, $user_id]);
                }
            } else {
                // 添加新用户
                if (empty($new_password)) {
                    throw new Exception('新用户必须设置密码');
                }
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashed_password, $role]);
            }
            
            $_SESSION['success_message'] = $user_id ? '用户信息更新成功' : '用户添加成功';
            redirect('/admin/users.php');
            
        } catch (Exception $e) {
            $error = '操作失败：' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><?php echo $user_id ? '编辑用户' : '添加新用户'; ?></h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">电子邮箱 <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="role" class="form-label">角色</label>
                    <select class="form-select" id="role" name="role">
                        <option value="user" <?php echo ($user['role'] ?? '') === 'user' ? 'selected' : ''; ?>>普通用户</option>
                        <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>管理员</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="new_password" class="form-label">
                        <?php echo $user_id ? '新密码（留空则不修改）' : '密码 <span class="text-danger">*</span>'; ?>
                    </label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           <?php echo $user_id ? '' : 'required'; ?>>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/users.php" class="btn btn-secondary">返回</a>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 