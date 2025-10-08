<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name('CUSTOMER_SESSION_' . $tab_id);
    session_start();
}
require_once '../../includes/functions.php';
checkAuth('customer');

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

// ดึง order + order_items + products
$stmt = $pdo->prepare("
    SELECT o.id AS order_id, o.created_at, pay.payment_method, pay.amount,
           oi.product_id, oi.quantity, oi.price,
           p.name AS product_name, p.product_image
    FROM orders o
    JOIN payments pay ON pay.order_id = o.id
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    WHERE pay.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การสั่งซื้อ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen">
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-xl font-bold">E-Commerce</a>
            <div class="flex space-x-6">
                <a href="../products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="../profile.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">โปรไฟล์</a>
                <a href="../cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ตะกร้าสินค้า</a>
                <a href="../favorites.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าที่ถูกใจ</a>
                <a href="orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">การสั่งซื้อ</a>
                <a href="../../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">การสั่งซื้อสำเร็จ</h1>
        <?php if (empty($orders)): ?>
            <p class="text-gray-500">ไม่มีรายการสั่งซื้อ</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="flex justify-between items-center bg-white p-4 mb-4 rounded-lg shadow">
                        <img src="<?php echo htmlspecialchars($order['product_image'] ?? getDefaultImage()); ?>" alt="Product" class="w-16 h-16 object-cover rounded mr-4">
                        <div class="flex-grow">
                            <span class="font-semibold"><?php echo htmlspecialchars($order['product_name']); ?></span>
                            <p>จำนวน: <?php echo $order['quantity']; ?> | ราคา: <?php echo number_format($order['quantity'] * $order['price'], 2); ?> บาท</p>
                            <p>วันที่: <?php echo $order['created_at']; ?></p>
                            <p>วิธีชำระ: <?php echo htmlspecialchars($order['payment_method'] === 'cod' ? 'เงินปลายทาง' : ($order['payment_method'] === 'qrcode' ? 'QR Code' : 'บัตร')); ?></p>
                        </div>
                        <?php 
                        $stmt = $pdo->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND product_id = ?");
                        $stmt->execute([$_SESSION['user_id'], $order['product_id']]);
                        if (!$stmt->fetch()): ?>
                            <a href="add_review.php?product_id=<?php echo $order['product_id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#ee4d2d] text-white font-bold px-4 py-2 rounded hover:bg-[#d73211]">เขียนรีวิว</a>
                        <?php else: ?>
                            <span class="text-gray-500">รีวิวแล้ว</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html>