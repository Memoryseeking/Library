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
- SSL证书（用于HTTPS）

### 2.2 腾讯云部署步骤

1. 购买腾讯云服务器
   - 推荐配置：2核4G内存
   - 操作系统：CentOS 7.6 或 Ubuntu 20.04
   - 带宽：5Mbps以上

2. 环境配置
   ```bash
   # 安装LNMP环境
   yum install -y nginx mysql-server php-fpm php-mysql
   
   # 启动服务
   systemctl start nginx
   systemctl start mysqld
   systemctl start php-fpm
   
   # 设置开机自启
   systemctl enable nginx
   systemctl enable mysqld
   systemctl enable php-fpm
   ```

3. SSL证书配置
   - 在腾讯云SSL证书控制台申请免费证书
   - 下载证书文件
   - 配置Nginx SSL：
     ```nginx
     server {
         listen 443 ssl;
         server_name your-domain.com;
         
         ssl_certificate /path/to/cert.pem;
         ssl_certificate_key /path/to/key.pem;
         
         # SSL配置
         ssl_protocols TLSv1.2 TLSv1.3;
         ssl_ciphers HIGH:!aNULL:!MD5;
         ssl_prefer_server_ciphers on;
         
         root /var/www/html;
         index index.php index.html;
         
         location / {
             try_files $uri $uri/ /index.php?$query_string;
         }
         
         location ~ \.php$ {
             fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
             fastcgi_index index.php;
             fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
             include fastcgi_params;
         }
     }
     ```

4. 项目部署
   ```bash
   # 克隆项目
   git clone [仓库地址] /var/www/html/
   
   # 设置权限
   chown -R nginx:nginx /var/www/html
   chmod -R 755 /var/www/html
   chmod -R 777 /var/www/html/uploads
   
   # 配置数据库
   mysql -u root -p
   CREATE DATABASE library;
   USE library;
   source /var/www/html/database.sql;
   ```

5. 安全配置
   - 配置防火墙
   ```bash
   # 开放必要端口
   firewall-cmd --permanent --add-service=http
   firewall-cmd --permanent --add-service=https
   firewall-cmd --reload
   ```
   
   - 设置数据库安全
   ```sql
   CREATE USER 'library_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON library.* TO 'library_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

6. 性能优化
   - 启用PHP OPcache
   - 配置Nginx缓存
   - 设置MySQL优化参数

### 2.3 访问说明
- 域名：https://your-domain.com
- 默认端口：443
- 测试账号：
  - 管理员：Admin/123456
  - 普通用户：Test/123456

---

## 3. 访问说明

### 3.1 应用访问
- URL：http://ysjcs.net:[端口号]
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
- CSRF防护
- SQL注入防护
- 安全的Cookie设置

### 5.2 身份验证
- JWT token有效期：1小时
- 支持token自动刷新
- 双重验证机制（Session + JWT）

---

## 许可证
MIT License 