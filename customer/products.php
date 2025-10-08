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

$product_search = $_GET['product_search'] ?? '';
$category_filter = $_GET['category'] ?? '';

try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND active = 1 ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$categories) $categories = [];
} catch (PDOException $e) {
    $categories = [];
}

$sql = "SELECT p.id, p.name, p.sku, p.category, p.price, p.product_image,
               COUNT(DISTINCT oi.id) as purchase_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN payments pay ON pay.order_id = o.id AND pay.status = 'completed'
        WHERE p.active = 1";
$params = [];

if (!empty($product_search)) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%" . $product_search . "%";
}

if (!empty($category_filter)) {
    $sql .= " AND p.category = ?";
    $params[] = $category_filter;
}

$sql .= " GROUP BY p.id ORDER BY p.order_index ASC, p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด</title>
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
        <h1 class="text-2xl font-bold mb-4">สินค้าทั้งหมด</h1>
        <div class="flex justify-between mb-4">
            <form method="GET" class="flex">
                <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
                <input type="text" name="product_search" value="<?php echo htmlspecialchars($product_search); ?>" placeholder="ค้นหาสินค้า..." class="p-2 border rounded">
                <button type="submit" class="bg-[#FB6F92] text-white py-2 px-4 rounded hover:bg-[#E55D7E] ml-2">ค้นหา</button>
            </form>
            <select onchange="window.location.href=this.value" class="p-2 border rounded">
                <option value="products.php?tab_id=<?php echo urlencode($tab_id); ?>">ทุกหมวดหมู่</option>
                <?php if ($categories): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="products.php?category=<?php echo urlencode($category); ?>&tab_id=<?php echo urlencode($tab_id); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <?php if (empty($products)): ?>
            <p class="text-gray-500">ไม่มีสินค้าในหมวดหมู่นี้</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($products as $product): ?>
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="block bg-white p-4 rounded shadow hover:shadow-lg transition">
                        <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" alt="Product Image" class="w-full h-40 object-cover rounded mb-2">
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-gray-600 text-sm">SKU: <?php echo htmlspecialchars(getDefaultText($product['sku'] ?? '')); ?></p>
                        <p class="text-gray-600 text-sm">หมวดหมู่: <?php echo htmlspecialchars(getDefaultText($product['category'] ?? '')); ?></p>
                        <p class="text-[#FB6F92] font-bold"><?php echo number_format($product['price'], 2); ?> บาท</p>
                        <p class="text-gray-500 text-sm">ซื้อแล้ว <?php echo $product['purchase_count']; ?> ครั้ง</p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>