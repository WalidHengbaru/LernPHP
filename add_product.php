<?php
session_name('ADMIN_SESSION');
session_start();
require 'config.php';

// Check if user is logged in and is super_admin only
if (!isset($_SESSION['user_id']) || $_SESSION['admin_level'] !== 'super_admin') {
    header("Location: admin_index.php");
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $product_image = null;

    if (empty($name) || empty($description) || $price <= 0) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วนและราคาต้องมากกว่า 0';
    } else {
        // Handle file upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            $file = $_FILES['product_image'];
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $product_image = $filename;
                } else {
                    $error = 'ไม่สามารถอัพโหลดรูปภาพได้';
                }
            } else {
                $error = 'รูปภาพไม่ถูกต้อง (อนุญาตเฉพาะ JPEG, PNG, GIF และขนาดไม่เกิน 5MB)';
            }
        }
        
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, product_image, created_at) VALUES (:name, :description, :price, :product_image, NOW())");
                $stmt->execute([':name' => $name, ':description' => $description, ':price' => $price, ':product_image' => $product_image]);
                $success = 'เพิ่มสินค้าสำเร็จ';
            } catch (PDOException $e) {
                $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้า</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?> (แอดมินใหญ่)
        </div>
    </div>
    <div class="layout-container">
        <div class="sidebar">
            <h2>เมนู</h2>
            <div class="dashboard">
                <div class="dashboard-card">
                    <a href="admin_index.php#dashboard" class="button button-primary">🏠 แดชบอร์ด</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php#profile" class="button button-primary">👤 โปรไฟล์</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php#customers" class="button button-primary">📋 รายชื่อลูกค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php#products" class="button button-primary">📦 รายการสินค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="add_product.php" class="button button-primary">➕ เพิ่มสินค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php#admins" class="button button-primary">👥 รายชื่อแอดมิน</a>
                </div>
                <div class="dashboard-card">
                    <a href="add_admin.php" class="button button-primary">➕ เพิ่มแอดมิน</a>
                </div>
                <div class="dashboard-card">
                    <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </div>
        <div class="content">
            <h2>เพิ่มสินค้า</h2>
            <?php if ($error): ?>
                <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" class="form-container" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>ชื่อสินค้า *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>คำอธิบาย *</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>ราคา *</label>
                        <input type="number" name="price" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>รูปภาพสินค้า</label>
                        <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif">
                        <small>อนุญาตเฉพาะ JPEG, PNG, GIF และขนาดไม่เกิน 5MB</small>
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit" class="button button-primary">บันทึก</button>
                    <a href="admin_index.php#products" class="button button-secondary">⬅️ ย้อนกลับ</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>