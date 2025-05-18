<?php
class FileUploader {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $error;

    public function __construct($uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
        $this->uploadDir = $uploadDir;
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize; // 默认5MB
        $this->error = null;
    }

    public function upload($file, $customName = null) {
        // 检查文件是否存在
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->error = '没有文件被上传';
            return false;
        }

        // 检查文件大小
        if ($file['size'] > $this->maxSize) {
            $this->error = '文件大小超过限制';
            return false;
        }

        // 检查文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            $this->error = '不支持的文件类型';
            return false;
        }

        // 生成文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $customName ? $customName . '.' . $extension : uniqid() . '.' . $extension;
        $targetPath = $this->uploadDir . '/' . $fileName;

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->error = '文件上传失败';
            return false;
        }

        return $fileName;
    }

    public function getError() {
        return $this->error;
    }

    public function delete($fileName) {
        $filePath = $this->uploadDir . '/' . $fileName;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
} 