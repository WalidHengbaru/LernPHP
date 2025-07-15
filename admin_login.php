<?php
session_name('ADMIN_SESSION');
session_start();
require 'config.php';

$error = '';

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        $error = 'ฐานข้อมูลไม่พร้อมใช้งาน กรุณารัน init_db.php เพื่อสร้างตาราง';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND admin_level IN ('super_admin', 'regular_admin')");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['admin_level'] = $user['admin_level'];
                header("Location: admin_index.php");
                exit;
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        }
    }
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในระบบ: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>เข้าสู่ระบบแอดมิน</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="form-container" style="max-width: 400px; margin: 40px auto;">
        <h2 style="text-align: center;">เข้าสู่ระบบแอดมิน</h2>
        <?php if ($error): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <label for="username">ชื่อผู้ใช้:</label><br>
            <input type="text" name="username" id="username" required style="width:100%; padding:8px; margin-bottom:12px;" /><br>
            <label for="password">รหัสผ่าน:</label><br>
            <input type="password" name="password" id="password" required style="width:100%; padding:8px; margin-bottom:20px;" /><br>
            <button type="submit" class="button-save" style="width:100%;">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>