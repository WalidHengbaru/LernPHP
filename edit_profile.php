<?php
session_name('ADMIN_SESSION');
session_start();
require 'config.php';

// Check if user is logged in
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

$stmt = $pdo->prepare("SELECT username, email, profile_image FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profile_image = $_POST['profile_image'] ?? $user_profile['profile_image'];

    if (empty($username) || empty($email)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, profile_image = :profile_image WHERE id = :id");
            $stmt->execute([':username' => $username, ':email' => $email, ':profile_image' => $profile_image, ':id' => $_SESSION['user_id']]);
            $success = 'อัปเดตโปรไฟล์สำเร็จ';
            $_SESSION['username'] = $username; // Update session
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
    <title>แก้ไขโปรไฟล์</title>
    <link rel="stylesheet" href="style.css">
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
                    <a href="admin_index.php#dashboard" class="button button-primary">🏠 แดชบอร์ด</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php#profile" class="button button-primary">👤 โปรไฟล์</a>
                </div>
                <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
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
                <?php endif; ?>
                <div class="dashboard-card">
                    <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </div>
        <div class="content">
            <h2>แก้ไขโปรไฟล์</h2>
            <?php if ($error): ?>
                <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" class="form-container">
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
                    <a href="admin_index.php#profile" class="button button-secondary">⬅️ ย้อนกลับ</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>