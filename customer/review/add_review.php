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

$error = '';
$success = '';
$tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
$product_id = intval($_GET['product_id'] ?? 0);

if ($product_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $review_image = uploadImage($_FILES['review_image'] ?? null, 'review');

    if ($rating < 1 || $rating > 5) {
        $error = 'กรุณาเลือกคะแนนรีวิวระหว่าง 1-5';
    } elseif (!$product) {
        $error = 'ไม่พบสินค้าที่ต้องการรีวิว';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, review_image, created_at) VALUES (:product_id, :user_id, :rating, :comment, :review_image, NOW())");
            $stmt->execute([
                ':product_id' => $product_id,
                ':user_id' => $_SESSION['user_id'],
                ':rating' => $rating,
                ':comment' => $comment,
                ':review_image' => $review_image ?: null
            ]);
            $success = 'เพิ่มรีวิวสำเร็จ';
            header("Location: ../products.php?tab_id=" . urlencode($tab_id) . "&success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มรีวิว</title>
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
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg mx-auto mt-10">
        <h3 class="text-2xl font-bold mb-4 text-gray-800">เพิ่มรีวิวสำหรับ <?php echo htmlspecialchars($product['name'] ?? 'สินค้า'); ?></h3>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-4" enctype="multipart/form-data">
            <div>
                <label for="rating" class="block text-left text-gray-700 font-semibold">คะแนน *</label>
                <select name="rating" id="rating" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
                    <option value="">เลือกคะแนน</option>
                    <option value="1">1 ดาว</option>
                    <option value="2">2 ดาว</option>
                    <option value="3">3 ดาว</option>
                    <option value="4">4 ดาว</option>
                    <option value="5">5 ดาว</option>
                </select>
            </div>
            <div>
                <label for="comment" class="block text-left text-gray-700 font-semibold">ความคิดเห็น</label>
                <textarea name="comment" id="comment" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="review_image" class="block text-left text-gray-700 font-semibold">รูปสินค้าที่ได้รับ (ไม่จำเป็น)</label>
                <input type="file" name="review_image" id="review_image" accept="image/*" class="w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="flex gap-4 justify-center">
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">ส่งรีวิว</button>
                <a href="../products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition-colors">ยกเลิก</a>
            </div>
        </form>
    </div>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html>