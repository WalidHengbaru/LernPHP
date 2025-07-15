<?php
session_name('ADMIN_SESSION');
session_start();
require 'config.php';

// Check if user is logged in and is super_admin or regular_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['admin_level'], ['super_admin', 'regular_admin'])) {
    header("Location: admin_login.php");
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

// Fetch current user profile
$stmt = $pdo->prepare("SELECT username, email, profile_image FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch();

// Fetch data for customers, products, and admins (only for super_admin)
$customers = [];
$products = [];
$admins = [];

try {
    if ($_SESSION['admin_level'] === 'super_admin') {
        // Fetch customers, excluding those with admin roles
        $stmt = $pdo->prepare("SELECT c.id, c.name, c.surname, c.email, c.telephone, c.address, c.created_at 
                              FROM customers c 
                              LEFT JOIN users u ON c.user_id = u.id 
                              WHERE u.admin_level IS NULL OR u.admin_level NOT IN ('super_admin', 'regular_admin') 
                              ORDER BY c.id DESC");
        $stmt->execute();
        $customers = $stmt->fetchAll();

        $product_search = trim($_GET['product_search'] ?? '');
        if ($product_search !== '') {
            $stmt = $pdo->prepare("SELECT id, name, description, price, created_at FROM products WHERE name = :name ORDER BY id DESC");
            $stmt->execute([':name' => $product_search]);
            $products = $stmt->fetchAll();
        } else {
            $products = $pdo->query("SELECT id, name, description, price, created_at FROM products ORDER BY id DESC")->fetchAll();
        }
        $admins = $pdo->query("SELECT id, username, email, admin_level, created_at FROM users WHERE admin_level IN ('super_admin', 'regular_admin') ORDER BY id DESC")->fetchAll();
    } else {
        // Fetch customers, excluding those with admin roles (for regular_admin)
        $stmt = $pdo->prepare("SELECT c.id, c.name, c.surname, c.email, c.telephone, c.address, c.created_at 
                              FROM customers c 
                              LEFT JOIN users u ON c.user_id = u.id 
                              WHERE u.admin_level IS NULL OR u.admin_level NOT IN ('super_admin', 'regular_admin') 
                              ORDER BY c.id DESC");
        $stmt->execute();
        $customers = $stmt->fetchAll();

        $product_search = trim($_GET['product_search'] ?? '');
        if ($product_search !== '') {
            $stmt = $pdo->prepare("SELECT id, name, description, price, created_at FROM products WHERE name = :name ORDER BY id DESC");
            $stmt->execute([':name' => $product_search]);
            $products = $stmt->fetchAll();
        } else {
            $products = $pdo->query("SELECT id, name, description, price, created_at FROM products ORDER BY id DESC")->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log('Database query failed: ' . $e->getMessage());
    echo "<p class='alert alert-error'>❌ เกิดข้อผิดพลาดในการดึงข้อมูล: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profile_image = null;

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['profile_image'];
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'admin_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $profile_image = $upload_dir . $filename;
            } else {
                $error = 'ไม่สามารถอัปโหลดรูปภาพได้';
            }
        } else {
            $error = 'รูปภาพไม่ถูกต้อง (ต้องเป็น JPEG, PNG, GIF และขนาดไม่เกิน 2MB)';
        }
    } else {
        $profile_image = $user_profile['profile_image'] ?? null;
    }

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
    <title>แดชบอร์ดผู้ดูแล</title>
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
            (<?php echo $_SESSION['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?>)
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
                    <a href="#" onclick="showSection('customers')" class="button button-primary">📋 รายชื่อลูกค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="#" onclick="showSection('products')" class="button button-primary">📦 รายการสินค้า</a>
                </div>
                <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                    <div class="dashboard-card">
                        <a href="add_product.php" class="button button-primary">➕ เพิ่มสินค้า</a>
                    </div>
                    <div class="dashboard-card">
                        <a href="#" onclick="showSection('admins')" class="button button-primary">👥 รายชื่อแอดมิน</a>
                    </div>
                    <div class="dashboard-card">
                        <a href="add_admin.php" class="button button-primary">➕ เพิ่มแอดมิน</a>
                    </div>
                <?php endif; ?>
                <div class="dashboard-card">
                    <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </div>
        <div class="content">
            <h2>ค้นหาสินค้าหรือลูกค้า</h2>
            <form method="get" action="search.php" class="form-container">
                <div class="form-grid">
                    <div class="form-group">
                        <label>คำค้น</label>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 3; text-align: center;">
                        <button type="submit" class="button button-primary">🔍 ค้นหา</button>
                        <a href="admin_index.php" class="button button-secondary">📋 ดูทั้งหมด</a>
                    </div>
                </div>
            </form>
            <div class="content-section" id="dashboard">
                <h3>แดชบอร์ด</h3>
                <!-- Customers -->
                <h4>รายชื่อลูกค้า</h4>
                <?php if (empty($customers)): ?>
                    <p class="alert alert-error">ไม่พบข้อมูลลูกค้า</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="styled-table">
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>อีเมล</th>
                                <th>โทรศัพท์</th>
                                <th>ที่อยู่</th>
                                <th>สร้างเมื่อ</th>
                                <th>แก้ไข</th>
                            </tr>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telephone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($c['address'] ?? ''); ?></td>
                                    <td><?php echo $c['created_at']; ?></td>
                                    <td>
                                        <a href="edit_customer.php?id=<?php echo $c['id']; ?>" class="button button-primary">✏️ แก้ไข</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                <!-- Products -->
                <h4>รายการสินค้า</h4>
                <?php if (empty($products)): ?>
                    <p class="alert alert-error">ไม่พบข้อมูลสินค้า</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อสินค้า</th>
                                <th>คำอธิบาย</th>
                                <th>ราคา</th>
                                <th>สร้างเมื่อ</th>
                                <th>รูปภาพ</th>
                            </tr>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                    <td><?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo $p['created_at']; ?></td>
                                    <td>
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="รูปภาพสินค้า" style="max-width: 80px; height: auto;">
                                        <?php else: ?>
                                            <span>ไม่มีรูปภาพ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                <!-- Admins (for super_admin only) -->
                <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                    <h4>รายชื่อแอดมิน</h4>
                    <?php if (empty($admins)): ?>
                        <p class="alert alert-error">ไม่พบข้อมูลแอดมิน</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>ระดับ</th>
                                    <th>สร้างเมื่อ</th>
                                </tr>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td><?php echo $a['id']; ?></td>
                                        <td><?php echo htmlspecialchars($a['username']); ?></td>
                                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                                        <td><?php echo $a['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?></td>
                                        <td><?php echo $a['created_at']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
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
                <form method="POST" enctype="multipart/form-data" class="form-container">
                    <div class="form-group">
                        <?php if (!empty($user_profile['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user_profile['profile_image']); ?>" alt="Profile Image" class="profile-image" style="max-width: 100px; height: auto;">
                        <?php else: ?>
                            <span>ไม่มีรูปโปรไฟล์</span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>รูปโปรไฟล์</label>
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <fieldset>
                        <legend>ข้อมูลบัญชี</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>ชื่อผู้ใช้ *</label>
                                <input name="username" value="<?php echo htmlspecialchars($user_profile['username'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>อีเมล *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </fieldset>
                    <div class="button-container">
                        <button type="submit" class="button button-primary">บันทึก</button>
                    </div>
                </form>
            </div>
            <div class="content-section" id="customers" style="display: none;">
                <h3>รายชื่อลูกค้า</h3>
                <?php if (empty($customers)): ?>
                    <p class="alert alert-error">ไม่พบข้อมูลลูกค้า</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="styled-table">
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>อีเมล</th>
                                <th>โทรศัพท์</th>
                                <th>ที่อยู่</th>
                                <th>สร้างเมื่อ</th>
                                <th>แก้ไข</th>
                            </tr>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telephone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($c['address'] ?? ''); ?></td>
                                    <td><?php echo $c['created_at']; ?></td>
                                    <td>
                                        <a href="edit_customer.php?id=<?php echo $c['id']; ?>" class="button button-primary">✏️ แก้ไข</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="content-section" id="products" style="display: none;">
                <?php if ($product_search !== ''): ?>
                    <h4>ผลลัพธ์สำหรับ "<?php echo htmlspecialchars($product_search); ?>"</h4>
                <?php endif; ?>
                <?php if (!empty($products)): ?>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>ลำดับ</th>
                                <th>ชื่อสินค้า</th>
                                <th>คำอธิบาย</th>
                                <th>ราคา</th>
                                <th>สร้างเมื่อ</th>
                            </tr>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                    <td><?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo $p['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert alert-error">ไม่มีสินค้าที่ตรงกับคำค้น</p>
                <?php endif; ?>
            </div>
            <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                <div class="content-section" id="admins" style="display: none;">
                    <h3>รายชื่อแอดมิน</h3>
                    <?php if (empty($admins)): ?>
                        <p class="alert alert-error">ไม่พบข้อมูลแอดมิน</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>ระดับ</th>
                                    <th>สร้างเมื่อ</th>
                                </tr>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td><?php echo $a['id']; ?></td>
                                        <td><?php echo htmlspecialchars($a['username']); ?></td>
                                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                                        <td><?php echo $a['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?></td>
                                        <td><?php echo $a['created_at']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>