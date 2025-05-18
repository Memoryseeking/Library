<?php
require_once 'includes/config.php';

// 清除所有会话数据
session_destroy();

// 重定向到登录页面
redirect('/login.php'); 