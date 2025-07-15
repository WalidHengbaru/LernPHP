<?php
session_start(['name' => 'CUSTOMER_SESSION']);
require 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['admin_level'] === 'customer') {
    header("Location: index.php");
    exit;
}

$pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
    DB_USER, DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

$error = '';

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        $error = 'ฐานข้อมูลไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $password_confirm === '' || $name === '' || $surname === '') {
            $error = 'กรุณากรอกข้อมูลที่จำเป็น: ชื่อผู้ใช้, อีเมล, รหัสผ่าน, ชื่อ, นามสกุล';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'รูปแบบอีเมลไม่ถูกต้อง';
        } elseif ($password !== $password_confirm) {
            $error = 'รหัสผ่านไม่ตรงกัน';
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
            } else {
                // Start transaction to insert user and customer
                $pdo->beginTransaction();
                try {
                    // Insert new user with customer role
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, admin_level, created_at) VALUES (?, ?, ?, 'customer', NOW())");
                    $stmt->execute([$username, $email, $hashed_password]);
                    $user_id = $pdo->lastInsertId();

                    // Insert corresponding customer record
                    $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, surname, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $name, $surname, $email, $hashed_password]);

                    $pdo->commit();
                    header("Location: login.php");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'เกิดข้อผิดพลาดในการสมัคร: ' . htmlspecialchars($e->getMessage());
                }
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
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            const username = document.querySelector('input[name="username"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value.trim();
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value.trim();
            const name = document.querySelector('input[name="name"]').value.trim();
            const surname = document.querySelector('input[name="surname"]').value.trim();
            
            if (!username || !email || !password || !passwordConfirm || !name || !surname) {
                alert('กรุณากรอกข้อมูลที่จำเป็น: ชื่อผู้ใช้, อีเมล, รหัสผ่าน, ชื่อ, นามสกุล');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="form-container" style="max-width: 400px;">
        <h2 style="text-align: center;">สมัครสมาชิก</h2>
        <?php if ($error): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" autocomplete="off" onsubmit="return validateForm()">
            <table>
                <tr>
                    <td><label for="username">ชื่อผู้ใช้ *</label></td>
                    <td><input type="text" name="username" id="username" required></td>
                </tr>
                <tr>
                    <td><label for="email">อีเมล *</label></td>
                    <td><input type="email" name="email" id="email" required></td>
                </tr>
                <tr>
                    <td><label for="name">ชื่อ *</label></td>
                    <td><input type="text" name="name" id="name" required></td>
                </tr>
                <tr>
                    <td><label for="surname">นามสกุล *</label></td>
                    <td><input type="text" name="surname" id="surname" required></td>
                </tr>
                <tr>
                    <td><label for="password">รหัสผ่าน *</label></td>
                    <td><input type="password" name="password" id="password" required></td>
                </tr>
                <tr>
                    <td><label for="password_confirm">ยืนยันรหัสผ่าน *</label></td>
                    <td><input type="password" name="password_confirm" id="password_confirm" required></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button type="submit" class="button-save">สมัครสมาชิก</button>
                        <button type="reset" class="button-reset">ล้างข้อมูล</button>
                        <a href="login.php" class="button-back-form">⬅️ กลับไปล็อกอิน</a>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>