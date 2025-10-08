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

// แก้ query: ลบ LEFT JOIN กับ payments ออก
$stmt = $pdo->prepare("
    SELECT c.product_id, c.quantity, p.stock, p.name, p.sku, p.category, p.price, p.product_image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
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
        <h1 class="text-2xl font-bold mb-4">ตะกร้าสินค้า</h1>
        <div class="bg-white p-4 rounded shadow">
        <?php if (empty($cart_items)): ?>
            <p class="text-gray-500">ตะกร้าสินค้าว่างเปล่า</p>
        <?php else: ?>
            <form method="POST" action="payment/checkout.php?tab_id=<?php echo urlencode($tab_id); ?>">
                <div class="flex justify-between mb-4">
                    <label><input type="checkbox" id="select-all" onclick="toggleAllCheckboxes()" class="mr-2"> เลือกทั้งหมด</label>
                </div>
                <?php foreach ($cart_items as $item): ?>
                    
                    <div class="flex items-center mb-4 p-4 border-b">
                        <input type="checkbox" name="selected_products[]" value="<?php echo $item['product_id']; ?>" class="cart-checkbox mr-4" data-price="<?php echo $item['price']; ?>" data-qty="<?php echo $item['quantity']; ?>" onclick="updateTotal()">
                        <input type="hidden" name="quantities[]" id="quantity-hidden-<?php echo $item['product_id']; ?>" value="<?php echo $item['quantity']; ?>">
                        <img src="<?php echo htmlspecialchars($item['product_image'] ?? getDefaultImage()); ?>" alt="Product Image" class="w-40 h-40 object-cover rounded mr-4">

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600 "><?php echo number_format($item['price'] , 2); ?> บาท</p>
                            <p class="text-gray-600">SKU: <?php echo htmlspecialchars(getDefaultText($item['sku'] ?? '')); ?></p>
                            <p class="text-gray-600 text-sm">หมวดหมู่: <?php echo htmlspecialchars(getDefaultText($item['category'] ?? '')); ?></p>
                            <div class="flex items-center">
                                <p class="text-gray-600 text-sm">จำนวน: </p>
                                <input type="number" id="quantity<?php echo $item['product_id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="w-16 text-center mx-2 border" onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                            </div>
                            <p class="">รวม: <span id="item-total<?php echo $item['product_id']; ?>"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span> บาท</p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mt-4 flex justify-between items-center">
                    <p class="text-xl font-bold" id="total-text">ยอดรวม: 0.00 บาท</p>
                    <button type="submit" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43] mt-4">ชำระเงิน</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <script>
        function updateQuantity(productId, qty) {
    qty = Math.max(1, qty);
    document.getElementById('quantity' + productId).value = qty;

    // Update hidden input
    const hiddenQty = document.getElementById('quantity-hidden-' + productId);
    if (hiddenQty) hiddenQty.value = qty;

    fetch('../api/cart.php?tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', product_id: productId, quantity: qty })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            const checkbox = document.querySelector('input.cart-checkbox[value="' + productId + '"]');
            const pricePerUnit = parseFloat(checkbox.getAttribute('data-price')) || 0;
            const itemTotal = pricePerUnit * qty;
            document.getElementById('item-total' + productId).textContent = itemTotal.toFixed(2) + ' บาท';
            checkbox.setAttribute('data-qty', qty);
            updateTotal();
        } else {
            alert(data.message);
        }
    });
}


        function updateTotal() {
            let total = 0;
            const checkboxes = document.querySelectorAll('.cart-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const selectAll = document.querySelector('#select-all');

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const qty = parseInt(cb.getAttribute('data-qty')) || 1;
                    const pricePerUnit = parseFloat(cb.getAttribute('data-price')) || 0;
                    total += qty * pricePerUnit;
                }
            });

            document.getElementById('total-text').textContent = 'ยอดรวม: ' + total.toFixed(2) + ' บาท';

            if (selectAll) {
                selectAll.checked = allChecked;
            }
        }

        function toggleAllCheckboxes() {
            const selectAll = document.querySelector('#select-all');
            const checkboxes = document.querySelectorAll('.cart-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateTotal();
        }

        function removeFromCart(productId) {
            fetch('../api/cart.php?tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', product_id: productId })
            }).then(res => res.json()).then(data => {
                alert(data.message);
                if (data.status === 'success') location.reload();
            });
        }

        // Initialize total
        updateTotal();
    </script>
    <div class="w-full">
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>