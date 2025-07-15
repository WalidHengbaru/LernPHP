<?php
// ----------------- ตั้งค่าการเชื่อมต่อ -----------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '8513');
define('DB_NAME', 'customer_db');

try {

    /* 1) เชื่อมต่อเซิร์ฟเวอร์ MySQL (ยังไม่เลือก DB) */
    $conn = new PDO(
        "mysql:host=".DB_HOST.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

    /* 2) สร้าง DB ถ้ายังไม่มี */
    $conn->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."`");

    /* 3) เชื่อมต่อเข้า DB ที่สร้าง / มีอยู่ */
    $conn->exec("USE `".DB_NAME."`");

    /* ---- พร้อมใช้งานฐานข้อมูลแล้ว ---- */
    // ไม่ echo อะไรออกไป หากสำเร็จ

} catch (PDOException $e) {

    /* รายงานเฉพาะตอนเกิดปัญหา */
    echo "เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage();
    exit; // หยุดการทำงาน
}
?>
