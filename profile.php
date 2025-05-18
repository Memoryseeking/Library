<?php
require_once 'includes/config.php';
require_once 'includes/FileUploader.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    redirect('/login.php');
}

// 获取用户信息
$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    try {
        // 验证用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = '用户名已存在';
        } else {
            // 验证邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = '邮箱已存在';
            } else {
                // 处理头像上传
                $avatar = $user['avatar'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $uploader = new FileUploader(__DIR__ . '/uploads/avatars');
                    $fileName = $uploader->upload($_FILES['avatar']);
                    if ($fileName) {
                        // 如果上传成功，删除旧头像
                        if ($avatar) {
                            $uploader->delete($avatar);
                        }
                        $avatar = $fileName;
                    } else {
                        $_SESSION['error_message'] = '头像上传失败：' . $uploader->getError();
                    }
                }

                if (!isset($_SESSION['error_message'])) {
                    // 如果要修改密码
                    if (!empty($new_password)) {
                        // 验证当前密码
                        if (!password_verify($current_password, $user['password'])) {
                            $_SESSION['error_message'] = '当前密码错误';
                        } elseif ($new_password !== $confirm_password) {
                            $_SESSION['error_message'] = '两次输入的新密码不一致';
                        } else {
                            // 更新用户信息（包括密码）
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET username = ?, email = ?, password = ?, avatar = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $username, 
                                $email, 
                                password_hash($new_password, PASSWORD_DEFAULT),
                                $avatar,
                                $user_id
                            ]);
                        }
                    } else {
                        // 更新用户信息（不修改密码）
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET username = ?, email = ?, avatar = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $email, $avatar, $user_id]);
                    }

                    if (!isset($_SESSION['error_message'])) {
                        $_SESSION['success_message'] = '个人信息更新成功';
                        // 更新session中的用户信息
                        $_SESSION['user']['username'] = $username;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['avatar'] = $avatar;
                        redirect('/profile.php');
                    }
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = '操作失败：' . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0">个人设置</h2>
            <p class="text-muted">管理您的个人信息和账户设置</p>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger fade-in">
            <?php 
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success fade-in">
            <?php 
            echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card fade-in">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">修改密码</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认新密码</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>保存更改
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card fade-in">
                <div class="card-body">
                    <h5 class="card-title mb-3">头像设置</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-3">
                            <?php if ($user['avatar']): ?>
                                <img src="/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/assets/images/default-avatar.jpg" 
                                     class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">上传新头像</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <div class="form-text">支持 JPG、PNG、GIF 格式，最大 5MB</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>更新头像
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 