<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/functions.php';
$pdo = getPDO();

// รับค่าจาก checkout.php
$user_id      = $_SESSION['user_id'] ?? null;
$address_id   = $_POST['selected_address_id'] ?? null;
$payment_type = $_POST['payment_method'] ?? 'cod';
$raw_products = $_POST['selected_products'] ?? [];
$raw_qty      = $_POST['quantities'] ?? [];
$tab_id       = $_POST['tab_id'] ?? '';

if (!$user_id || !$address_id || empty($raw_products)) {
    die("ข้อมูลไม่ครบถ้วน ไม่สามารถทำรายการได้");
}

// แปลงเป็น associative array: product_id => quantity
$selected_products = [];
if (!is_array($raw_products)) $raw_products = [$raw_products];
if (!is_array($raw_qty)) $raw_qty = [$raw_qty];

foreach ($raw_products as $i => $pid) {
    $pid = intval($pid);
    $qty = isset($raw_qty[$i]) ? max(1, intval($raw_qty[$i])) : 1;
    $selected_products[$pid] = $qty;
}

// ตอนนี้ $selected_products = [ product_id => quantity, ... ]

try {
    $pdo->beginTransaction();

    // สร้าง order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, address_id, status, created_at) 
                           VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $address_id]);
    $order_id = $pdo->lastInsertId();

    $total_amount = 0;

    // เตรียม statement สำหรับบันทึก order_items และลด stock
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                VALUES (?, ?, ?, ?)");
    $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    foreach ($selected_products as $pid => $qty) {
        // ดึงข้อมูลสินค้า
        $stmt_p = $pdo->prepare("SELECT price, stock, name FROM products WHERE id = ? AND active = 1");
        $stmt_p->execute([$pid]);
        $product = $stmt_p->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("สินค้า ID $pid ไม่มีอยู่หรือถูกปิดใช้งาน");
        }

        if ($product['stock'] < $qty) {
            throw new Exception("สินค้า '{$product['name']}' มีสต็อกไม่พอ (คงเหลือ: {$product['stock']})");
        }

        $price = $product['price'];
        $total_amount += $price * $qty;

        // บันทึก order_items
        $stmt_item->execute([$order_id, $pid, $qty, $price]);

        // ลด stock
        $stmt_stock->execute([$qty, $pid, $qty]);
    }

    // บันทึกการชำระเงิน
    $stmt_pay = $pdo->prepare("INSERT INTO payments (order_id, user_id, amount, payment_method, status, created_at) 
                               VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt_pay->execute([$order_id, $user_id, $total_amount, $payment_type]);

    $pdo->commit();

    // ล้างสินค้าที่เลือกออกจาก session cart
    if (isset($_SESSION['cart'])) {
        foreach (array_keys($selected_products) as $pid) {
            unset($_SESSION['cart'][$pid]);
        }
    }
    // เก็บข้อมูลใน session สำหรับใช้งานต่อ
$_SESSION['checkout'] = [
    'products' => $selected_products, // product_id => quantity
    'total' => $total_amount,
    'address_id' => $address_id,
];


    echo "<script>
        alert('สั่งซื้อสำเร็จ! ยอดรวม: " . number_format($total_amount, 2) . " บาท');
        window.location.href='../review/orders.php?tab_id=" . urlencode($tab_id) . "';
    </script>";
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()));
}
