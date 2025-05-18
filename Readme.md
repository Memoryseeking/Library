# 图书管理系统文档

## 封面

**模块代码：** COM6023M  
**作业标题：** 图书管理系统  
**学生姓名：** Memoryseeking  
**学号：** [你的学号]

## 应用说明

### 目的和功能描述

本系统是一个基于 PHP 和 MySQL 的图书管理系统，旨在提供图书管理、借阅、评论等功能的完整解决方案。主要功能包括：

1. 用户管理
   - 用户注册和登录
   - 个人信息管理
   - 头像上传

2. 图书管理
   - 图书列表展示
   - 图书详情查看
   - 图书搜索和分类

3. 借阅管理
   - 图书借阅
   - 借阅记录查看
   - 借阅状态管理

4. 评论系统
   - 图书评论
   - 评分功能
   - 评论管理

### 服务详细说明

#### API 端点

1. 用户相关
   - `/register.php` - 用户注册
   - `/login.php` - 用户登录
   - `/logout.php` - 用户登出
   - `/profile.php` - 用户信息管理

2. 图书相关
   - `/index.php` - 首页（图书列表）
   - `/detail.php` - 图书详情
   - `/list.php` - 图书列表
   - `/borrow.php` - 图书借阅
   - `/borrow_records.php` - 借阅记录

#### 数据库表结构

1. users 表
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

2. books 表
```sql
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    cover VARCHAR(255),
    category VARCHAR(50),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

3. borrows 表
```sql
CREATE TABLE borrows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL,
    return_date TIMESTAMP NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);
```

4. reviews 表
```sql
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);
```

## 安装指南

### 服务器环境配置

1. PHP 版本要求：PHP 7.4 或更高版本
2. MySQL 版本要求：MySQL 5.7 或更高版本
3. Web 服务器：Apache 或 Nginx
4. 操作系统：Windows/Linux/MacOS

### 依赖库安装

1. PHP 依赖（通过 Composer 安装）：
```bash
composer install
```

2. 前端依赖：
- Bootstrap 5
- Font Awesome 5
- jQuery 3.6.0

### 数据库初始化

1. 创建数据库：
```sql
CREATE DATABASE library;
```

2. 导入数据库结构：
```bash
mysql -u username -p library < database.sql
```

## 访问说明

### 应用访问

- URL：http://localhost/Library
- 默认端口：80（Apache）或 443（HTTPS）

### 测试账号

1. 管理员账号
   - 用户名：admin
   - 密码：admin123

2. 普通用户账号
   - 用户名：user
   - 密码：user123

## 代码引用

### 第三方库和框架

1. Bootstrap 5
   - 来源：https://getbootstrap.com/
   - 用途：前端 UI 框架
   - 许可证：MIT

2. Font Awesome 5
   - 来源：https://fontawesome.com/
   - 用途：图标库
   - 许可证：MIT

3. jQuery
   - 来源：https://jquery.com/
   - 用途：JavaScript 库
   - 许可证：MIT

4. PHP PDO
   - 来源：PHP 内置
   - 用途：数据库连接
   - 许可证：PHP License

### 开源组件

1. 文件上传处理
   - 来源：自定义实现
   - 用途：处理用户头像和图书封面上传

2. 密码加密
   - 来源：PHP 内置 password_hash()
   - 用途：用户密码加密存储

3. 会话管理
   - 来源：PHP 内置 session
   - 用途：用户登录状态管理 