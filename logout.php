<?php
session_start();

// 清除session
session_destroy();

// 清除JWT token cookie
setcookie('auth_token', '', time() - 3600, '/', '', true, true);

// 重定向到登录页面
header('Location: login.php');
exit; 