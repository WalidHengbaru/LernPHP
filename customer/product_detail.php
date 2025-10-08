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

$product_id = intval($_GET['id'] ?? 0);
$product = null;
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

$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
$has_profile_image = !empty($columns);
$select_columns = $has_profile_image ? "u.username, u.email, u.profile_image, c.name, c.surname" : "u.username, u.email, c.name, c.surname";
$stmt = $pdo->prepare("SELECT $select_columns FROM users u LEFT JOIN customers c ON u.id = c.user_id WHERE u.id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

$allowed_tags = '<p><br><b><strong><i><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><span><div><img><a><table><tr><td><th><blockquote>';

function renderSafeHTML($html, $allowed_tags) {
    return strip_tags($html, $allowed_tags);
}

$stmt = $pdo->prepare("SELECT r.*, u.username, u.profile_image FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = :id ORDER BY r.created_at DESC");
$stmt->execute([':id' => $product_id]);
$reviews = $stmt->fetchAll();

function getQualityText($rating) {
    if ($rating == 5) return 'ยอดเยี่ยม';
    if ($rating == 4) return 'ดีมาก';
    if ($rating == 3) return 'ดี';
    if ($rating == 2) return 'พอใช้';
    return 'ควรปรับปรุง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดสินค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen">
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-xl font-bold">E-Commerce</a>
            <div class="flex items-center space-x-6">
                <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="profile.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">โปรไฟล์</a>
                <a href="cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ตะกร้าสินค้า</a>
                <a href="favorites.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าที่ถูกใจ</a>
                <a href="review/orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">การสั่งซื้อ</a>
                <div class="flex items-center">
                    <img src="<?php echo htmlspecialchars($user_profile['profile_image'] ?? getDefaultImage()); ?>" alt="Profile Image" class="w-8 h-8 rounded-full mr-2">
                    <span><?php echo htmlspecialchars($user_profile['name'] . ' ' . $user_profile['surname']); ?></span>
                </div>
                <a href="../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6">
        <?php if ($product): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col md:flex-row">
                    <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" alt="Product Image" class="w-full md:w-2/5 h-86 object-cover rounded-lg">
                    <div class="md:ml-6 mt-4 md:mt-0 flex-1">
                        <div>
                            <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <div class="flex justify-between items-center mb-2 text-[20px]">
                                <p class="text-primary font-bold text-xl mt-2"><?php echo number_format($product['price'], 2); ?> บาท</p>
                                <p class="text-gray-600 text-[16px]">ซื้อแล้ว: <?php echo $product['purchase_count']; ?> ครั้ง</p>
                            </div>
                            <div class="mb-2 text-gray-700 text-base">
                                <?php echo renderSafeHTML($product['description'] ?? 'ไม่มีรายละเอียดเพิ่มเติม', $allowed_tags); ?>
                            </div>
                            <p class="text-gray-600">SKU: <?php echo htmlspecialchars(getDefaultText($product['sku'])); ?></p>
                            <p class="text-gray-600">หมวดหมู่: <?php echo htmlspecialchars(getDefaultText($product['category'])); ?></p>
                            <div class="mt-4">
                                <label for="quantity" class="block text-left">จำนวน:</label>
                                <div class="flex items-center">
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-16 p-2 border border-gray-300 text-center" onchange="updatePrice()">
                                </div>
                            </div>
                            <p class="text-primary font-bold text-xl mt-2" id="total-price"><?php echo number_format($product['price'], 2); ?> บาท</p>
                            <div class="mt-4 flex space-x-4">
                                <button onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('quantity').value)" class="bg-[#93B9DD] text-white py-2 px-4 rounded hover:bg-[#78A3D4]">เพิ่มลงตะกร้า</button>
                                <button onclick="toggleFavorite(<?php echo $product['id']; ?>)" class="bg-[#B19CD7] text-white py-2 px-4 rounded hover:bg-[#aF80C0]"><?php echo $pdo->query("SELECT 1 FROM favorites WHERE user_id = {$_SESSION['user_id']} AND product_id = {$product['id']}")->fetch() ? 'ลบจากรายการโปรด' : 'เพิ่มในรายการโปรด ❤️'; ?></button>
                                <a href="checkout.php?product_id=<?php echo $product['id']; ?>&quantity=1&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43]" id="buy-now">ซื้อเลย (<span id="buy-now-price"><?php echo number_format($product['price'], 2); ?></span> บาท)</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 mt-4 prose max-w-none">
                    <?php echo renderSafeHTML($product['full_detail'] ?? 'ไม่มีรายละเอียดเพิ่มเติม', $allowed_tags); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 mt-4">
                <h2 class="text-xl font-bold mb-4">รีวิวสินค้า</h2>
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
        <?php else: ?>
            <p class="text-center text-red-500">ไม่พบสินค้า</p>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
    <script>
        const pricePerUnit = <?php echo $product['price']; ?>;
        function updatePrice() {
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1) document.getElementById('quantity').value = 1;
            if (quantity > <?php echo $product['stock']; ?>) document.getElementById('quantity').value = <?php echo $product['stock']; ?>;
            const total = (quantity * pricePerUnit).toFixed(2);
            document.getElementById('total-price').textContent = total + ' บาท';
            document.getElementById('buy-now-price').textContent = total;
            document.getElementById('buy-now').href = 'checkout.php?product_id=<?php echo $product['id']; ?>&quantity=' + quantity + '&tab_id=<?php echo urlencode($tab_id); ?>';
        }
        function increaseQuantity() {
            const input = document.getElementById('quantity');
            input.value = parseInt(input.value) + 1;
            updatePrice();
        }
        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updatePrice();
            }
        }
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
        function addToCart(productId, quantity) {
            fetch('../api/cart.php?tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, quantity: parseInt(quantity) })
            }).then(res => res.json()).then(data => {
                alert(data.message);
                if (data.status === 'success') location.reload();
            });
        }
    </script>
</body>
</html>
?>