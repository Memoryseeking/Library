<?php
class ImageHandler {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;

    public function __construct($uploadDir = 'uploads/', $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
        // 确保上传目录以 / 结尾
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
    }

    public function uploadImage($file, $oldImagePath = null) {
        // 检查文件是否有效
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('无效的文件上传');
        }

        // 检查文件类型
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('不支持的文件类型');
        }

        // 检查文件大小
        if ($file['size'] > $this->maxSize) {
            throw new Exception('文件大小超过限制');
        }

        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;
        $targetPath = $this->uploadDir . $newFileName;

        // 确保上传目录存在
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('文件上传失败');
        }

        // 如果存在旧图片，删除它
        if ($oldImagePath && file_exists($this->uploadDir . $oldImagePath)) {
            $this->deleteImage($oldImagePath);
        }

        // 返回相对路径
        return $this->uploadDir . $newFileName;
    }

    public function deleteImage($imagePath) {
        if ($imagePath && file_exists($this->uploadDir . $imagePath)) {
            unlink($this->uploadDir . $imagePath);
        }
    }
} 