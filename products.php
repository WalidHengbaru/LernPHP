<?php
session_start(['name' => 'CUSTOMER_SESSION']);
require 'config.php';

// Check if user is logged in and has customer role
if (!isset($_SESSION['user_id']) || $_SESSION['admin_level'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

// Fetch customer data for the logged-in user
$customer_id = '';
$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();
if ($customer) {
    $customer_id = $customer['id'];
}

// Fetch products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด</title>
    <link rel="stylesheet" href="style.css">
    <script>
        document.querySelectorAll('.sidebar a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username'] ?? 'ผู้ใช้'); ?>
        </div>
    </div>
    <div class="layout-container">
        <div class="sidebar">
            <h2>เมนู</h2>
            <div class="dashboard">
                <div class="dashboard-card">
                    <a href="edit_customer.php?id=<?php echo htmlspecialchars($customer_id); ?>" class="button button-primary">👤 โปรไฟล์</a>
                </div>
                <div class="dashboard-card">
                    <a href="#products" class="button button-primary">📦 สินค้าทั้งหมด</a>
                </div>
                <div class="dashboard-card">
                    <a href="favorites.php" class="button button-primary">❤️ ที่ถูกใจ</a>
                </div>
                <div class="dashboard-card">
                    <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </div>
        <div class="content">
            <h2 id="products">สินค้าทั้งหมด</h2>
            <?php if (empty($products)): ?>
                <p class="alert alert-error">ไม่มีสินค้าที่พร้อมจำหน่ายในขณะนี้</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รูปภาพ</th>
                            <th>ชื่อสินค้า</th>
                            <th>คำอธิบาย</th>
                            <th>ราคา</th>
                            <th>เพิ่มในรายการโปรด</th>
                        </tr>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td>
                                    <?php if (!empty($p['product_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($p['product_image']); ?>" alt="Product Image" style="max-width: 50px; height: auto;">
                                    <?php else: ?>
                                        <span>ไม่มีรูป</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                <td><?php echo number_format($p['price'], 2); ?></td>
                                <td>
                                    <a href="favorites.php?action=add&product_id=<?php echo $p['id']; ?>" class="button button-primary">❤️ เพิ่ม</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>