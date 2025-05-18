<?php
require_once '../includes/config.php';

// 检查管理员权限
requireAdmin();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = null;
$error = '';
$success = '';

// 获取分类信息
if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        redirect('/admin/categories.php');
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // 验证输入
    if (empty($name)) {
        $error = '请填写分类名称';
    } else {
        try {
            // 检查分类名称是否已被使用
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $category_id]);
            if ($stmt->fetch()) {
                throw new Exception('分类名称已被使用');
            }
            
            if ($category_id) {
                // 更新分类
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $category_id]);
            } else {
                // 添加新分类
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
            }
            
            $_SESSION['success_message'] = $category_id ? '分类更新成功' : '分类添加成功';
            redirect('/admin/categories.php');
            
        } catch (Exception $e) {
            $error = '操作失败：' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><?php echo $category_id ? '编辑分类' : '添加新分类'; ?></h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">分类描述</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/categories.php" class="btn btn-secondary">返回</a>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 