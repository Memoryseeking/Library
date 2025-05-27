<?php
require_once '../includes/config.php';
require_once '../includes/FileUploader.php';

// 检查管理员权限
requireAdmin();

// 获取图书ID
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取图书信息
$book = null;
if ($book_id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
}

// 获取所有分类
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $published_date = !empty($_POST['published_date']) ? trim($_POST['published_date']) : date('Y-m-d');
    
    // 验证必填字段
    if (empty($title) || empty($author) || empty($isbn)) {
        $_SESSION['error_message'] = '请填写必填字段';
    } else {
        try {
            // 检查ISBN是否已存在
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
            $stmt->execute([$isbn, $book_id]);
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = 'ISBN已存在';
            } else {
                // 处理封面图片上传
                $cover_image = $book ? $book['cover_image'] : null;
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                    $uploader = new FileUploader(__DIR__ . '/../uploads/covers');
                    $fileName = $uploader->upload($_FILES['cover_image']);
                    if ($fileName) {
                        // 如果上传成功，删除旧图片
                        if ($cover_image) {
                            $uploader->delete($cover_image);
                        }
                        $cover_image = $fileName;
                    } else {
                        $_SESSION['error_message'] = '封面图片上传失败：' . $uploader->getError();
                    }
                }

                if (!isset($_SESSION['error_message'])) {
                    if ($book_id) {
                        // 更新图书
                        $stmt = $pdo->prepare("
                            UPDATE books 
                            SET title = ?, author = ?, isbn = ?, description = ?, 
                                category_id = ?, stock_quantity = ?, published_date = ?,
                                cover_image = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $title, $author, $isbn, $description, $category_id,
                            $stock_quantity, $published_date, $cover_image, $book_id
                        ]);
                    } else {
                        // 添加新图书
                        $stmt = $pdo->prepare("
                            INSERT INTO books (title, author, isbn, description, 
                                             category_id, stock_quantity, published_date, cover_image)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $title, $author, $isbn, $description, $category_id,
                            $stock_quantity, $published_date, $cover_image
                        ]);
                    }
                    $_SESSION['success_message'] = $book_id ? '图书更新成功' : '图书添加成功';
                    redirect('/admin/books.php');
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = '操作失败：' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><?php echo $book_id ? '编辑图书' : '添加图书'; ?></h2>
            <p class="text-muted"><?php echo $book_id ? '修改图书信息' : '添加新图书到系统'; ?></p>
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

    <div class="card fade-in">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">书名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo $book ? htmlspecialchars($book['title']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">作者 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" required
                                   value="<?php echo $book ? htmlspecialchars($book['author']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="isbn" name="isbn" required
                                   value="<?php echo $book ? htmlspecialchars($book['isbn']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">描述</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php 
                                echo $book ? htmlspecialchars($book['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">分类</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">选择分类</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $book && $book['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">库存数量</label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                           min="0" value="<?php echo $book ? $book['stock_quantity'] : 1; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="published_date" class="form-label">出版日期</label>
                            <input type="date" class="form-control" id="published_date" name="published_date"
                                   value="<?php echo $book ? $book['published_date'] : ''; ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="cover_image" class="form-label">封面图片</label>
                            <?php if ($book && $book['cover_image']): ?>
                                <div class="mb-2">
                                    <img src="/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                            <div class="form-text">支持 JPG、PNG、GIF 格式，最大 5MB</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="/admin/books.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>返回
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 