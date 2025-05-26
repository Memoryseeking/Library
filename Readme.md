# 图书管理系统
## 模块代码：COM6023M
## 学生信息：Zhengyang Liu 24014994

---

## 1. 项目概述

### 应用简介
图书管理系统是一个基于PHP的Web应用程序，提供完整的图书管理、借阅管理和用户管理功能，为图书馆或图书管理机构提供数字化解决方案。

### 目标用户
- 图书馆管理人员：管理图书、用户和借阅记录
- 普通用户：浏览图书、借阅图书、管理个人借阅记录

### 核心功能
- 用户认证与授权系统（注册、登录、权限控制）
- 图书信息管理（添加、编辑、删除、分类）
- 借阅管理（借阅、归还、历史记录）
- 用户管理（信息编辑、头像上传）
- 评论与评分系统

## 2. 技术架构

### 服务器/客户端技术栈
- **后端**：PHP 7.4+, MySQL 5.7+, PDO
- **前端**：HTML5, CSS3, JavaScript, Bootstrap 5
- **认证**：自定义JWT实现, Session

### 数据库结构
主要数据表：
- `users` - 用户信息表
- `books` - 图书信息表
- `borrow_records` - 借阅记录表
- `categories` - 图书分类表
- `reviews` - 评论表

## 3. 安装指南

### 环境要求
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx Web服务器
- 必要的PHP扩展：PDO, JSON, bcrypt

### 安装步骤
1. **克隆仓库**
   ```bash
   git clone [仓库地址]
   ```

2. **数据库配置**
   - 创建名为"library"的新MySQL数据库
   - 导入项目根目录中的`database.sql`文件
   - 修改`includes/config.php`中的数据库连接信息

3. **服务器配置**
   - 将项目目录设置为Web根目录
   - 确保`uploads`目录可写
   - 配置URL重写规则（如有需要）

4. **默认账户**
   - 管理员账号：
     - 用户名：Admin
     - 密码：123456
     - 邮箱：Admin@email.com
   - 测试用户账号：
     - 用户名：Testuser
     - 密码：123456
     - 邮箱：Test@email.com

## 4. 主要API端点

### 用户认证
- `POST /login.php` - 用户登录
- `POST /register.php` - 用户注册
- `GET /logout.php` - 用户登出

### 图书管理
- `GET /list.php` - 获取图书列表（支持搜索和分类筛选）
- `GET /detail.php` - 获取图书详情
- `POST /admin/book_edit.php` - 添加/编辑图书（需管理员权限）
- `POST /admin/books.php` - 删除图书（需管理员权限）

### 借阅管理
- `POST /borrow.php` - 借阅图书
- `GET /borrow_records.php` - 获取借阅记录
- `POST /admin/borrows.php` - 管理借阅记录（需管理员权限）

## 5. 安全特性

- **密码安全**：使用PHP内置的BCrypt哈希算法加密存储
- **输入验证**：服务器端验证所有用户输入，使用PDO预处理语句防止SQL注入
- **认证机制**：
  - JWT令牌认证（有效期1小时）
  - PHP会话管理
  - 基于角色的访问控制

## 6. 项目结构
- `/` - 项目根目录，包含主要PHP文件
- `/admin/` - 管理员功能相关文件
- `/includes/` - 包含配置文件和公共组件
- `/assets/` - 静态资源，如CSS和JavaScript
- `/uploads/` - 上传文件存储目录

## 7. 参考资源
- PHP官方文档：[php.net](https://www.php.net/docs.php)
- MySQL文档：[dev.mysql.com](https://dev.mysql.com/doc/)
- Bootstrap文档：[getbootstrap.com](https:/?getbootstrap.com/docs/)
- JWT规范：[jwt.io](https://jwt.io/)

---

## 许可证
MIT License 