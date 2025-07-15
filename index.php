<?php
session_name('CUSTOMER_SESSION');
session_start();
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
$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();
$customer_id = $customer ? $customer['id'] : '';

// Fetch products (with search filter if provided)
$product_search = trim($_GET['product_search'] ?? '');
$products = [];
if ($product_search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name = :name ORDER BY id DESC");
    $stmt->execute([':name' => $product_search]);
    $products = $stmt->fetchAll();
} else {
    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
}

// Check if favorites table exists and fetch favorites
$favorites = [];
$has_favorites_table = $pdo->query("SHOW TABLES LIKE 'favorites'")->rowCount() > 0;
if ($has_favorites_table) {
    $stmt = $pdo->prepare("SELECT p.* FROM favorites f JOIN products p ON f.product_id = p.id WHERE f.user_id = :user_id ORDER BY f.id DESC");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $favorites = $stmt->fetchAll();
}

// Fetch current user profile
$stmt = $pdo->prepare("SELECT username, email, profile_image FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profile_image = trim($_POST['profile_image'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, profile_image = :profile_image WHERE id = :id");
            $stmt->execute([':username' => $username, ':email' => $email, ':profile_image' => $profile_image, ':id' => $_SESSION['user_id']]);
            $_SESSION['username'] = $username;
            $success = 'อัปเดตโปรไฟล์สำเร็จ';
            $user_profile = ['username' => $username, 'email' => $email, 'profile_image' => $profile_image];
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
    <title>แดชบอร์ด</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            const target = document.getElementById(sectionId);
            if (target) {
                target.style.display = 'block';
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }
        window.onload = () => showSection('dashboard');
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>
    <div class="layout-container">
        <div class="sidebar">
            <h2>เมนู</h2>
            <div class="dashboard">
                <div class="dashboard-card">
                    <a href="#" onclick="showSection('dashboard')" class="button button-primary">🏠 แดชบอร์ด</a>
                </div>
                <div class="dashboard-card">
                    <a href="#" onclick="showSection('profile')" class="button button-primary">👤 โปรไฟล์</a>
                </div>
                <div class="dashboard-card">
                    <a href="#" onclick="showSection('products')" class="button button-primary">📦 สินค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="#" onclick="showSection('favorites')" class="button button-primary">❤️ สินค้าที่ถูกใจ</a>
                </div>
                <div class="dashboard-card">
                    <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </div>
        <div class="content">
            <h2>แดชบอร์ด</h2>
            <div class="content-section" id="dashboard">
                <!-- Products -->
                <h4>รายการสินค้า</h4>
                <?php if (!empty($products)): ?>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>คำอธิบาย</th>
                                <th>ราคา</th>
                                <th>สร้างเมื่อ</th>
                                <th>เพิ่มในรายการโปรด</th>
                            </tr>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td>
                                        <?php if (!empty($p['product_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($p['product_image']); ?>" alt="Product Image" class="profile-image" style="max-width: 50px; height: auto;">
                                        <?php else: ?>
                                            <span>ไม่มีรูป</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                    <td><?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo $p['created_at']; ?></td>
                                    <td>
                                        <a href="favorites.php?action=add&product_id=<?php echo $p['id']; ?>" class="button button-primary">❤️ เพิ่ม</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert alert-error">ไม่มีสินค้า</p>
                <?php endif; ?>
                <!-- Favorites -->
                <h4>สินค้าที่ถูกใจ</h4>
                <?php if ($has_favorites_table): ?>
                    <?php if (!empty($favorites)): ?>
                        <div class="table-container">
                            <table>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รูปภาพ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>คำอธิบาย</th>
                                    <th>ราคา</th>
                                    <th>สร้างเมื่อ</th>
                                    <th>ลบ</th>
                                </tr>
                                <?php foreach ($favorites as $f): ?>
                                    <tr>
                                        <td><?php echo $f['id']; ?></td>
                                        <td>
                                            <?php if (!empty($f['product_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($f['product_image']); ?>" alt="Product Image" class="sell-image" style="max-width: 50px; height: auto;">
                                            <?php else: ?>
                                                <span>ไม่มีรูป</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                                        <td><?php echo htmlspecialchars($f['description'] ?? ''); ?></td>
                                        <td><?php echo number_format($f['price'], 2); ?></td>
                                        <td><?php echo $f['created_at']; ?></td>
                                        <td>
                                            <a href="favorites.php?action=remove&product_id=<?php echo $f['id']; ?>" class="button button-danger">🗑️ ลบ</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="alert alert-error">ไม่มีสินค้าที่ถูกใจ</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="alert alert-warning">ตารางรายการโปรดยังไม่ถูกสร้าง</p>
                <?php endif; ?>
            </div>
            <div class="content-section" id="profile" style="display: none;">
                <h3>โปรไฟล์</h3>
                <?php if ($error): ?>
                    <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>
                <form method="POST" class="form-container">
                    <div class="form-group">
                        <?php if (!empty($user_profile['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user_profile['profile_image']); ?>" alt="Profile Image" class="profile-image" style="max-width: 100px; height: auto;">
                        <?php else: ?>
                            <span>ไม่มีรูปโปรไฟล์</span>
                        <?php endif; ?>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ชื่อผู้ใช้ *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user_profile['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>อีเมล *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>ลิงก์รูปภาพโปรไฟล์</label>
                            <input type="text" name="profile_image" value="<?php echo htmlspecialchars($user_profile['profile_image'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="button-container">
                        <button type="submit" class="button button-primary">บันทึก</button>
                    </div>
                </form>
            </div>
            <div class="content-section" id="products" style="display: none;">
                <h3>รายการสินค้า</h3>
                <form method="get" class="form-container">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ค้นหาสินค้า</label>
                            <input type="text" name="product_search" value="<?php echo htmlspecialchars($product_search); ?>" placeholder="ชื่อสินค้า">
                        </div>
                        <div class="form-group" style="grid-column: span 3; text-align: center;">
                            <button type="submit" class="button button-primary">🔍 ค้นหา</button>
                            <a href="index.php#products" class="button button-secondary">📋 ดูทั้งหมด</a>
                        </div>
                    </div>
                </form>
                <?php if ($product_search !== ''): ?>
                    <h4>ผลลัพธ์สำหรับ "<?php echo htmlspecialchars($product_search); ?>"</h4>
                <?php endif; ?>
                <?php if (!empty($products)): ?>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>คำอธิบาย</th>
                                <th>ราคา</th>
                                <th>สร้างเมื่อ</th>
                                <th>เพิ่มในรายการโปรด</th>
                            </tr>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td>
                                        <?php if (!empty($p['product_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($p['product_image']); ?>" alt="Product Image" class="profile-image" style="max-width: 50px; height: auto;">
                                        <?php else: ?>
                                            <span>ไม่มีรูป</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                    <td><?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo $p['created_at']; ?></td>
                                    <td>
                                        <a href="favorites.php?action=add&product_id=<?php echo $p['id']; ?>" class="button button-primary">❤️ เพิ่ม</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert alert-error">ไม่มีสินค้าที่ตรงกับคำค้น</p>
                <?php endif; ?>
            </div>
            <div class="content-section" id="favorites" style="display: none;">
                <h3>สินค้าที่ถูกใจ</h3>
                <?php if ($has_favorites_table): ?>
                    <?php if (!empty($favorites)): ?>
                        <div class="table-container">
                            <table>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รูปภาพ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>คำอธิบาย</th>
                                    <th>ราคา</th>
                                    <th>สร้างเมื่อ</th>
                                    <th>ลบ</th>
                                </tr>
                                <?php foreach ($favorites as $f): ?>
                                    <tr>
                                        <td><?php echo $f['id']; ?></td>
                                        <td>
                                            <?php if (!empty($f['product_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($f['product_image']); ?>" alt="Product Image" class="sell-image" style="max-width: 50px; height: auto;">
                                            <?php else: ?>
                                                <span>ไม่มีรูป</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                                        <td><?php echo htmlspecialchars($f['description'] ?? ''); ?></td>
                                        <td><?php echo number_format($f['price'], 2); ?></td>
                                        <td><?php echo $f['created_at']; ?></td>
                                        <td>
                                            <a href="favorites.php?action=remove&product_id=<?php echo $f['id']; ?>" class="button button-danger">🗑️ ลบ</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="alert alert-error">ไม่มีสินค้าที่ถูกใจ</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="alert alert-warning">ตารางรายการโปรดยังไม่ถูกสร้าง</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>