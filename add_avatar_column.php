<?php
require_once 'includes/config.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    echo "Avatar column added successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 