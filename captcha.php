<?php
session_start();

// 创建图片
$image = imagecreatetruecolor(120, 40);

// 设置背景色
$bg = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg);

// 生成随机验证码
$code = '';
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[mt_rand(0, strlen($chars) - 1)];
}

// 保存验证码到会话
$_SESSION['captcha'] = $code;

// 添加干扰线
for ($i = 0; $i < 6; $i++) {
    $color = imagecolorallocate($image, mt_rand(100, 200), mt_rand(100, 200), mt_rand(100, 200));
    imageline($image, mt_rand(0, 120), mt_rand(0, 40), mt_rand(0, 120), mt_rand(0, 40), $color);
}

// 添加干扰点
for ($i = 0; $i < 50; $i++) {
    $color = imagecolorallocate($image, mt_rand(100, 200), mt_rand(100, 200), mt_rand(100, 200));
    imagesetpixel($image, mt_rand(0, 120), mt_rand(0, 40), $color);
}

// 写入验证码
for ($i = 0; $i < 4; $i++) {
    $color = imagecolorallocate($image, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
    imagestring($image, 5, 20 + ($i * 20), 10, $code[$i], $color);
}

// 输出图片
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image); 