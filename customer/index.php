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

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$product_id = intval($_GET['id'] ?? 0);
$product = null;

// 🔹 ถ้ามี id ให้ดึงข้อมูลสินค้านั้น
if ($product_id) {
    $stmt = $pdo->prepare("SELECT p.*, COUNT(DISTINCT oi.id) as purchase_count
                       FROM products p
                       LEFT JOIN order_items oi ON p.id = oi.product_id
                       LEFT JOIN orders o ON oi.order_id = o.id
                       LEFT JOIN payments pay ON pay.order_id = o.id
                       WHERE p.id = :id AND p.active = 1
                       GROUP BY p.id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 🔹 ถ้าไม่มี id ให้ดึงสินค้าแนะนำทั้งหมด
if (!$product_id) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.sku, p.category, p.price, p.product_image, 
               COUNT(DISTINCT oi.id) as purchase_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN payments pay ON pay.order_id = o.id
        WHERE p.active = 1
        GROUP BY p.id
        ORDER BY p.order_index ASC, p.created_at DESC
        LIMIT 8
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("SELECT r.*, u.username, u.profile_image 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.product_id = :id 
                       ORDER BY r.created_at DESC");
$stmt->execute([':id' => $product_id]);
$reviews = $stmt->fetchAll();

function getQualityText($rating) {
    if ($rating == 5) return 'ยอดเยี่ยม';
    if ($rating == 4) return 'ดีมาก';
    if ($rating == 3) return 'ดี';
    if ($rating == 2) return 'พอใช้';
    return 'ควรปรับปรุง';
}

$isLoggedIn = isset($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen">
    <!-- 🔸 Navbar -->
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-xl font-bold">E-Commerce</a>
            <div class="flex space-x-6">
                <a href="products.php" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="./login.php" class="hover:underline">เข้าสู่ระบบ</a>
                <a href="./register.php" class="hover:underline">สมัครสมาชิก</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <?php if ($product): ?>
            <!-- 🔹 แสดงรายละเอียดสินค้า -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" 
                         alt="Product Image" 
                         class="w-full h-80 object-cover rounded-lg">
                    <div>
                        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <p class="text-gray-600 mb-1">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                        <p class="text-gray-600 mb-1">หมวดหมู่: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="text-[#FB6F92] text-2xl font-bold mb-3"><?php echo number_format($product['price'], 2); ?> บาท</p>
                        <p class="text-gray-500 mb-3">ขายแล้ว <?php echo $product['purchase_count']; ?> ครั้ง</p>

                        <?php if ($isLoggedIn): ?>
                            <button class="bg-[#FB6F92] hover:bg-[#e35c81] text-white px-6 py-2 rounded-lg transition">
                                ซื้อสินค้า
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="bg-gray-400 text-white px-6 py-2 rounded-lg inline-block">
                                เข้าสู่ระบบก่อนซื้อสินค้า
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                <!-- 🔹 รีวิวสินค้า -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">รีวิวสินค้า</h2>
                    <?php if (empty($reviews)): ?>
                        <p class="text-gray-500">ยังไม่มีรีวิว</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                        <div class="border border-gray-200  mt-3">
                            <div class="flex items-center space-x-3 border-b border-gray-100">
                                <img src="<?php echo htmlspecialchars($r['profile_image'] ?? getDefaultImage()); ?>" 
                                    alt="Profile" 
                                    class="w-10 h-10 ml-2 rounded-full object-cover">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($r['username']); ?></p>
                                    <div class="flex items-center space-x-1">
                                        <?php 
                                            // 🔹 แสดงดาวตามคะแนน เช่น 3 ดาว = เหลือง 3 เทา 2
                                            for ($i = 1; $i <= 5; $i++): 
                                                $starColor = ($i <= $r['rating']) ? '#FFD700' : '#D1D5DB'; // เหลือง vs เทา
                                        ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="<?php echo $starColor; ?>" viewBox="0 0 24 24" stroke-width="1.5" stroke="none" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.24 5.393a.563.563 0 00.475.345l5.848.414a.563.563 0 01.319.982l-4.49 3.865a.563.563 0 00-.182.557l1.34 5.69a.562.562 0 01-.84.61l-4.98-2.93a.562.562 0 00-.566 0l-4.98 2.93a.562.562 0 01-.84-.61l1.34-5.69a.563.563 0 00-.182-.557L2.598 10.633a.563.563 0 01.319-.982l5.848-.414a.563.563 0 00.475-.345L11.48 3.5z" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-sm text-gray-500"><?php echo getQualityText($r['rating']); ?></p>
                                </div>
                            </div>
                            <p class="mt-2 mb-2 ml-3 text-gray-700"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <!-- 🔹 แสดงรายการสินค้า -->
            <h1 class="text-2xl font-bold mb-4">สินค้าแนะนำ</h1>
            <?php if (empty($products)): ?>
                <p class="text-gray-500">ไม่มีสินค้า</p>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($products as $product): ?>
                        <a href="?id=<?php echo $product['id']; ?>" 
                           class="block bg-white p-4 rounded shadow hover:shadow-lg transition">
                            <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" 
                                 alt="Product Image" 
                                 class="w-full h-40 object-cover rounded mb-2">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-gray-600 text-sm">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                            <p class="text-gray-600 text-sm">หมวดหมู่: <?php echo htmlspecialchars($product['category']); ?></p>
                            <p class="text-[#FB6F92] font-bold"><?php echo number_format($product['price'], 2); ?> บาท</p>
                            <p class="text-gray-500 text-sm">ซื้อแล้ว <?php echo $product['purchase_count']; ?> ครั้ง</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
