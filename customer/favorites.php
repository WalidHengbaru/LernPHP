<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name('CUSTOMER_SESSION_' . $tab_id);
    session_start();
}
require_once '../includes/functions.php';
checkAuth('customer');

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

// ✅ ใช้ order_items + orders + payments แทน
$stmt = $pdo->prepare("
    SELECT 
        p.id, p.name, p.sku, p.category, p.price, p.product_image,
        COUNT(DISTINCT oi.id) AS purchase_count
    FROM favorites f
    JOIN products p ON f.product_id = p.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON oi.order_id = o.id
    LEFT JOIN payments pay ON pay.order_id = o.id AND pay.status = 'completed'
    WHERE f.user_id = ? AND p.active = 1
    GROUP BY p.id
");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าที่ถูกใจ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen">
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-xl font-bold">E-Commerce</a>
            <div class="flex space-x-6">
                <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="profile.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">โปรไฟล์</a>
                <a href="cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ตะกร้าสินค้า</a>
                <a href="favorites.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าที่ถูกใจ</a>
                <a href="review/orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">การสั่งซื้อ</a>
                <a href="../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">สินค้าที่ถูกใจ</h1>
        <?php if (empty($favorites)): ?>
            <p class="text-gray-500">ไม่มีสินค้าที่ถูกใจ</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($favorites as $product): ?>
                    <div class="bg-white p-4 rounded shadow">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="block">
                            <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" alt="Product Image" class="w-full h-40 object-cover rounded mb-2">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-gray-600 text-sm">SKU: <?php echo htmlspecialchars(getDefaultText($product['sku'] ?? '')); ?></p>
                            <p class="text-gray-600 text-sm">หมวดหมู่: <?php echo htmlspecialchars(getDefaultText($product['category'] ?? '')); ?></p>
                            <p class="text-[#FB6F92] font-bold"><?php echo number_format($product['price'], 2); ?> บาท</p>
                            <p class="text-gray-500 text-sm">ซื้อแล้ว <?php echo $product['purchase_count']; ?> ครั้ง</p>
                        </a>
                        <button onclick="toggleFavorite(<?php echo $product['id']; ?>)" class="bg-gray-200 py-2 px-4 rounded hover:bg-gray-300 mt-2 w-full">❤️ ลบออกจากที่ถูกใจ</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
    <script>
        function toggleFavorite(productId) {
            fetch('../api/favorites.php?tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle', product_id: productId })
            }).then(res => res.json()).then(data => {
                alert(data.message);
                if (data.status === 'success') location.reload();
            });
        }
    </script>
</body>
</html>