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

// Add to favorites
if (isset($_GET['action']) && $_GET['action'] === 'add' && ctype_digit($_GET['product_id'])) {
    $product_id = (int) $_GET['product_id'];
    $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
    }
    header("Location: favorites.php");
    exit;
}

// Remove from favorites
if (isset($_GET['action']) && $_GET['action'] === 'remove' && ctype_digit($_GET['product_id'])) {
    $product_id = (int) $_GET['product_id'];
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    header("Location: favorites.php");
    exit;
}

// Fetch favorites
$stmt = $pdo->prepare("SELECT p.* FROM products p JOIN favorites f ON p.id = f.product_id WHERE f.user_id = ? ORDER BY f.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการโปรด</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username'] ?? 'ผู้ใช้'); ?>
        </div>
        <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
    </div>
    <div class="container">
        <h2>รายการโปรด</h2>
        <div class="dashboard">
            <div class="dashboard-card">
                <a href="edit_customer.php?id=<?php echo $customers[0]['id'] ?? ''; ?>" class="button button-primary">👤 โปรไฟล์</a>
            </div>
            <div class="dashboard-card">
                <a href="products.php" class="button button-primary">📦 สินค้าทั้งหมด</a>
            </div>
            <div class="dashboard-card">
                <a href="favorites.php" class="button button-primary">❤️ ที่ถูกใจ</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อสินค้า</th>
                    <th>คำอธิบาย</th>
                    <th>ราคา</th>
                    <th>ลบ</th>
                </tr>
                <?php foreach ($favorites as $f): ?>
                    <tr>
                        <td><?php echo $f['id']; ?></td>
                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                        <td><?php echo htmlspecialchars($f['description'] ?? ''); ?></td>
                        <td><?php echo number_format($f['price'], 2); ?></td>
                        <td>
                            <a href="favorites.php?action=remove&product_id=<?php echo $f['id']; ?>" class="button button-danger">🗑️ ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>