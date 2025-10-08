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

/* -------------------------------
   โหลดสินค้าในตะกร้า / ซื้อเลย
-------------------------------- */
$selected_products = [];
$quantities = [];
$cart_items = [];
$total = 0;

// ดึงข้อมูลจาก session ถ้ามี
if (isset($_SESSION['checkout'])) {
    $selected_products = $_SESSION['checkout']['products']; // associative array
    $address_id = $_SESSION['checkout']['address_id'];

    // ดึงข้อมูลสินค้า
    if (!empty($selected_products)) {
        $placeholders = implode(',', array_fill(0, count($selected_products), '?'));
        $stmt = $pdo->prepare("
            SELECT id AS product_id, name, sku, category, price, product_image, stock
            FROM products
            WHERE id IN ($placeholders) AND active = 1
        ");
        $stmt->execute(array_keys($selected_products));
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ผูกจำนวนสินค้า
        $total = 0;
        foreach ($cart_items as &$item) {
            $pid = $item['product_id'];
            $item['quantity'] = $selected_products[$pid] ?? 1;
            $total += $item['price'] * $item['quantity'];
        }
    }
}


// คำนวณยอดรวมเฉพาะสินค้าที่ quantity > 0
$total = 0;
foreach ($cart_items as $item) {
    if ($item['quantity'] > 0) {
        $total += $item['price'] * $item['quantity'];
    }
}

// กรองเฉพาะสินค้าที่ถูกเลือกจริง
$cart_items = array_filter($cart_items, fn($i) => $i['quantity'] > 0);

// โหลดที่อยู่
$selected_address = null;
if (isset($_SESSION['selected_address_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_SESSION['selected_address_id'], $_SESSION['user_id']]);
    $selected_address = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $selected_address = getPrimaryAddress($pdo, $_SESSION['user_id']);
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_checkout'])) {
    include 'process_payment.php';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
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
                <a href="../review/orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">การสั่งซื้อ</a>
                <a href="../../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <?php if (isset($error) && $error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- ที่อยู่จัดส่ง -->
        <div class="bg-white p-4 mb-4 rounded shadow">
            <h2 class="text-lg font-bold mb-2">ที่อยู่จัดส่ง</h2>
            <?php if ($selected_address): ?>
                <p><strong><?php echo htmlspecialchars($selected_address['name'] . ' ' . $selected_address['surname']); ?></strong></p>
                <p><?php echo htmlspecialchars($selected_address['address']); ?></p>
                <p>โทร: <?php echo htmlspecialchars($selected_address['telephone']); ?></p>
                <a href="../addresses.php?from=checkout&tab_id=<?php echo urlencode($tab_id); ?>" class="text-blue-500 hover:underline">เปลี่ยนที่อยู่</a>
            <?php else: ?>
                <p class="text-red-500">กรุณาเพิ่มที่อยู่จัดส่ง</p>
                <a href="../addresses.php?from=checkout&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">เพิ่มที่อยู่</a>
            <?php endif; ?>
        </div>

        <!-- สินค้าที่เลือก -->
        <div class="bg-white p-4 mb-4 rounded shadow">
            <h2 class="text-lg font-bold mb-2">สินค้าที่เลือก</h2>
            <?php if (empty($cart_items)): ?>
                <p class="text-gray-500">ไม่มีสินค้าในตะกร้า</p>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="flex items-center mb-4" data-pid="<?php echo $item['product_id']; ?>" data-qty="<?php echo $item['quantity']; ?>">
                        <img src="<?php echo htmlspecialchars($item['product_image'] ?? getDefaultImage()); ?>" class="w-16 h-16 object-cover rounded mr-4">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p class="text-gray-600 text-sm">SKU: <?php echo htmlspecialchars($item['sku'] ?? ''); ?></p>
                            <p class="text-gray-600 text-sm">หมวดหมู่: <?php echo htmlspecialchars($item['category'] ?? ''); ?></p>
                            <p class="text-gray-600">จำนวน: <?php echo $item['quantity']; ?> | ราคา: <?php echo number_format($item['price'] * $item['quantity'], 2); ?> บาท</p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p class="text-right font-bold">ยอดรวม: <?php echo number_format($total, 2); ?> บาท</p>
            <?php endif; ?>
        </div>

        <!-- วิธีการชำระเงิน -->
        <div class="bg-white p-4 mb-4 rounded shadow">
            <h2 class="text-lg font-bold mb-2">วิธีการชำระเงิน</h2>
            <form method="POST" onsubmit="return checkBeforeSubmit(event)">
                <?php foreach ($selected_products as $pid => $qty): ?>
                    <input type="hidden" name="selected_products[]" value="<?php echo $pid; ?>">
                    <input type="hidden" name="quantities[]" value="<?php echo $qty; ?>">
                <?php endforeach; ?>
                <label class="block"><input type="radio" name="payment_method" value="cod" checked> จ่ายเงินปลายทาง (COD)</label>
                <label class="block"><input type="radio" name="payment_method" value="qrcode"> QR Code</label>
                <label class="block"><input type="radio" name="payment_method" value="card"> จ่ายผ่านบัตร</label>
                
                <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
                <input type="hidden" name="selected_address_id" value="<?php echo htmlspecialchars($selected_address['id'] ?? ''); ?>">
                <button type="submit" name="confirm_checkout" class="bg-[#92CA68] text-white py-2 px-4 rounded mt-4">ชำระเงิน</button>
            </form>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        function checkBeforeSubmit(event) {
            <?php if (!$selected_address): ?>
                alert('กรุณาเพิ่มที่อยู่จัดส่งก่อนดำเนินการชำระเงิน');
                event.preventDefault();
                return false;
            <?php endif; ?>
            return true;
        }
    </script>
</body>
</html>
