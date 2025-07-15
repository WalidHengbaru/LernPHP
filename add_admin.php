<?php
session_name('ADMIN_SESSION');
session_start();
require 'config.php';

// Check if user is logged in and is super_admin
if (!isset($_SESSION['user_id']) || $_SESSION['admin_level'] !== 'super_admin') {
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $admin_level = trim($_POST['admin_level'] ?? 'regular_admin');

    // Validate inputs
    if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
        $error = 'กรุณากรอกข้อมูลที่จำเป็น: ชื่อผู้ใช้, อีเมล, รหัสผ่าน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif ($password !== $password_confirm) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (!in_array($admin_level, ['super_admin', 'regular_admin'])) {
        $error = 'ระดับแอดมินไม่ถูกต้อง';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            try {
                // Insert new admin
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, admin_level, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $admin_level]);
                $success = 'เพิ่มแอดมินสำเร็จ';
            } catch (PDOException $e) {
                $error = 'เกิดข้อผิดพลาดในการเพิ่มแอดมิน: ' . htmlspecialchars($e->getMessage());
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
    <title>เพิ่มแอดมิน</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            const username = document.querySelector('input[name="username"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value.trim();
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value.trim();
            if (!username || !email || !password || !passwordConfirm) {
                alert('กรุณากรอกข้อมูลที่จำเป็น: ชื่อผู้ใช้, อีเมล, รหัสผ่าน');
                return false;
            }
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('รูปแบบอีเมลไม่ถูกต้อง');
                return false;
            }
            if (password !== passwordConfirm) {
                alert('รหัสผ่านไม่ตรงกัน');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>
            (<?php echo $_SESSION['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?>)
        </div>
        <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
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
            <h2>เพิ่มแอดมิน</h2>
            <?php if ($error): ?>
                <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" class="form-container" onsubmit="return validateForm()">
                <div class="form-grid">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้ *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>อีเมล *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่าน *</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่าน *</label>
                        <input type="password" name="password_confirm" required>
                    </div>
                    <div class="form-group">
                        <label>ระดับแอดมิน *</label>
                        <select name="admin_level" required>
                            <option value="regular_admin">แอดมินรอง</option>
                            <option value="super_admin">แอดมินใหญ่</option>
                        </select>
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit" class="button button-primary">บันทึก</button>
                    <button type="reset" class="button button-secondary">ล้างข้อมูล</button>
                    <a href="admin_index.php#admins" class="button button-secondary">⬅️ ย้อนกลับ</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>