<?php
require 'config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

try {
    $pdo->exec("
        INSERT INTO products (name, description, price, created_at) VALUES
        ('สินค้า A', 'คำอธิบายสำหรับสินค้า A - คุณภาพสูง เหมาะสำหรับทุกการใช้งาน', 100.00, NOW()),
        ('สินค้า B', 'คำอธิบายสำหรับสินค้า B - ทนทานและราคาประหยัด', 200.00, NOW()),
        ('สินค้า C', 'คำอธิบายสำหรับสินค้า C - ดีไซน์ทันสมัย', 150.50, NOW())
    ");
    echo "Sample products inserted successfully ✅";
} catch (PDOException $e) {
    echo "Error inserting products: " . htmlspecialchars($e->getMessage());
}
?>