# Library Management System
## Module Code: COM6023M
## Student Information: Zhengyang Liu 24014994

---

## 1. Application Description

### 1.1 Purpose
The Library Management System is a PHP-based web application that provides comprehensive library management, book lending, and user management functionalities, offering a digital solution for libraries and book management institutions.

### 1.2 Target Users
- Library administrators: Manage books, users, and lending records
- Regular users: Browse books, borrow books, manage personal lending records

### 1.3 Core Features
- User authentication and authorization system (registration, login, permission control)
- Book information management (add, edit, delete, categorize)
- Lending management (borrowing, returning, history records)
- User management (profile editing, avatar upload)
- Review and rating system

### 1.4 Technical Architecture
- **Backend**: PHP 7.4+, MySQL 5.7+, PDO
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Authentication**: Custom JWT implementation, Session

### 1.5 API Endpoints

#### User Authentication
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `GET /logout.php` - User logout
- `POST /login_process.php` - Process login credentials and generate JWT token

#### User Management
- `GET /profile.php` - View user profile
- `POST /profile.php` - Update user profile information
- `POST /profile.php` - Change user password (same endpoint with different parameters)
- `POST /delete_account.php` - Delete user account
- `GET /captcha.php` - Generate CAPTCHA image for form validation

#### Book Management
- `GET /list.php` - Get book list (supports search and category filtering)
- `GET /detail.php` - Get book details
- `POST /admin/book_edit.php` - Add/edit book (admin permission required)
- `POST /admin/books.php` - Delete book (admin permission required)
- `POST /admin/batch_delete_books.php` - Batch delete multiple books (admin permission required)

#### Lending Management
- `POST /borrow.php` - Borrow book
- `GET /borrow_records.php` - Get lending records
- `POST /admin/borrows.php` - Manage lending records (admin permission required)
  - Update status (borrowed, returned, overdue)
  - Process returns

#### Category Management
- `GET /admin/categories.php` - List all categories (admin permission required)
- `POST /admin/category_edit.php` - Add/edit category (admin permission required)
- `POST /admin/batch_delete_categories.php` - Batch delete categories (admin permission required)

#### Review Management
- `GET /admin/reviews.php` - List all reviews (admin permission required)
- `POST /admin/review_action.php` - Approve or reject review (admin permission required)
- `POST /admin/reviews.php` - Delete review (admin permission required)

#### Admin Dashboard
- `GET /admin/dashboard.php` - Admin dashboard with statistics
- `GET /admin/users.php` - List all users (admin permission required)
- `POST /admin/user_edit.php` - Add/edit user (admin permission required)
- `POST /admin/delete_user.php` - Delete user (admin permission required)

#### System
- `GET /check_db.php` - Check database connection status

### 1.6 Database Structure
Main data tables:
- `users` - User information table
- `books` - Book information table
- `borrow_records` - Lending records table
- `categories` - Book category table
- `reviews` - Review table

---

## 2. Installation Guide

### 2.1 Server Environment Requirements
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx Web server
- Required PHP extensions: PDO, JSON, bcrypt
- Internet connection for CDN resources (or local copies)

### 2.2 Third-Party Libraries
This project uses the following third-party libraries via CDN:
- Bootstrap 5.1.3 (CSS framework)
- Font Awesome 6.0.0 (Icon library)
- jQuery 3.6.0 (JavaScript library)

No local package installation is required as all libraries are loaded from CDN.

#### Optional: Local Dependency Setup
If you need to deploy in an offline environment, download these libraries and update references in:
- `includes/header.php` (Bootstrap and Font Awesome)
- `includes/footer.php` (Bootstrap JS and jQuery)

### 2.3 Installation Steps
1. **Clone Repository**
   ```bash
   git clone https://github.com/yourusername/library-system.git
   ```

2. **Database Configuration**
   - Create a new MySQL database named "library"
   - Import the `database.sql` file from the project root directory
   - Modify database connection information in `includes/config.php`

3. **Server Configuration**
   - Set the project directory as the web root directory
   - Ensure the `uploads` directory is writable
   - Configure URL rewrite rules (if needed)

4. **JWT Configuration**
   - Update the secret key in `includes/JWT.php` (replace 'your-secret-key')
   - Adjust token expiration time if needed (default is 1 hour)

5. **Default Accounts**
   - Administrator account:
     - Username: Admin
     - Password: 123456
     - Email: Admin@email.com
   - Test user account:
     - Username: Testuser
     - Password: 123456
     - Email: Test@email.com

---

## 3. Access Instructions

### 3.1 Application Access
- URL: http://8.218.141.218
- Default port: 80

---

## 4. Security Features

### 4.1 Password Security
- Using PHP's built-in BCrypt hashing algorithm for encrypted storage

### 4.2 Input Validation
- Server-side validation of all user inputs
- Using PDO prepared statements to prevent SQL injection
- XSS protection

### 4.3 Authentication Mechanism
- JWT token authentication (1-hour validity)
- Support for token auto-refresh
- PHP session management
- Role-based access control
- Dual verification mechanism (Session + JWT)

---

## 5. Project Structure
- `/` - Project root directory, containing main PHP files
- `/admin/` - Files related to administrator functions
- `/includes/` - Configuration files and common components
- `/assets/` - Static resources such as CSS and JavaScript
- `/uploads/` - Directory for storing uploaded files

---

## 6. Reference Resources
- PHP Official Documentation: [php.net](https://www.php.net/docs.php)
- MySQL Documentation: [dev.mysql.com](https://dev.mysql.com/doc/)
- Bootstrap Documentation: [getbootstrap.com](https://getbootstrap.com/docs/)
- JWT Specification: [jwt.io](https://jwt.io/)

---

## License
MIT License 