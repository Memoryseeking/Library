# 图书管理系统
## 模块代码：COM6023M
## 学生信息：[待填写]

---

## 1. 应用说明

### 1.1 应用目的
本系统是一个基于PHP的图书管理系统，旨在提供完整的图书管理、借阅管理和用户管理功能，为图书馆或图书管理机构提供数字化解决方案。

### 1.2 核心功能
- 用户认证与授权系统
- 图书信息管理
- 借阅管理
- 用户管理

### 1.3 技术架构
- 后端：PHP 7.4+
- 数据库：MySQL 5.7+
- 前端：HTML5, CSS3, JavaScript
- 身份验证：JWT (JSON Web Token)

### 1.4 API端点
#### 用户认证
- POST /login.php - 用户登录
- POST /register.php - 用户注册
- GET /logout.php - 用户登出

#### 图书管理
- GET /books.php - 获取图书列表
- POST /books.php - 添加新图书
- PUT /books.php/{id} - 更新图书信息
- DELETE /books.php/{id} - 删除图书

#### 借阅管理
- POST /borrow.php - 借阅图书
- POST /return.php - 归还图书
- GET /borrow-history.php - 获取借阅历史

### 1.5 数据库结构
主要数据表：
- users - 用户信息表
- books - 图书信息表
- borrow_records - 借阅记录表

---

## 2. 安装指南

### 2.1 服务器环境要求
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx Web服务器
- 必要的PHP扩展：PDO, JSON, bcrypt

### 2.2 依赖库安装
```bash
# 安装PHP依赖
composer install

# 安装前端依赖
npm install
```

### 2.3 配置步骤
1. 克隆仓库
```bash
git clone [仓库地址]
```

2. 数据库配置
- 创建新的MySQL数据库
- 导入 `database.sql` 文件
- 配置数据库连接信息

3. 环境配置
- 复制 `config.example.php` 为 `config.php`
- 修改数据库连接信息
- 设置JWT密钥

4. Web服务器配置
- 将项目目录设置为Web根目录
- 配置URL重写规则
- 设置适当的文件权限

---

## 3. 访问说明

### 3.1 应用访问
- URL：http://8.218.141.218
- 默认端口：80

### 3.2 测试账号
1. 管理员账号
   - 用户名：Admin
   - 密码：123456
   - 邮箱：Admin@email.com

2. 测试用户账号
   - 用户名：Test
   - 密码：123456
   - 邮箱：Test@email.com

---

## 4. 代码引用

### 4.1 第三方库
- JWT库：[jwt-php](https://github.com/firebase/php-jwt)
- 密码加密：PHP内置bcrypt
- 前端框架：Bootstrap 5

### 4.2 参考资源
- PHP官方文档：[php.net](https://www.php.net/docs.php)
- MySQL文档：[dev.mysql.com](https://dev.mysql.com/doc/)
- JWT规范：[jwt.io](https://jwt.io/)

---

## 5. 安全说明

### 5.1 安全特性
- 密码加密存储（bcrypt）
- XSS防护
- SQL注入防护
- 安全的Cookie设置

### 5.2 身份验证
- JWT token有效期：1小时
- 支持token自动刷新
- 双重验证机制（Session + JWT）

---

## 许可证
MIT License 