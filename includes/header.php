<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图书管理系统</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- 自定义样式 -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-book-reader me-2"></i>图书管理系统
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/list.php">
                            <i class="fas fa-book me-1"></i>图书列表
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/borrow_records.php">
                                <i class="fas fa-history me-1"></i>借阅记录
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn() && isset($_SESSION['user'])): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>管理面板
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (isset($_SESSION['user']['avatar']) && $_SESSION['user']['avatar']): ?>
                                    <img src="/uploads/avatars/<?php echo htmlspecialchars($_SESSION['user']['avatar']); ?>" 
                                         class="rounded-circle me-1" style="width: 24px; height: 24px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/assets/images/default-avatar.jpg" 
                                         class="rounded-circle me-1" style="width: 24px; height: 24px; object-fit: cover;">
                                <?php endif; ?>
                                <?php echo isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : '用户'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="/profile.php">
                                        <i class="fas fa-user me-2"></i>个人设置
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>退出登录
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>登录
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">
                                <i class="fas fa-user-plus me-1"></i>注册
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="py-4">
    <div class="container mt-4"> 