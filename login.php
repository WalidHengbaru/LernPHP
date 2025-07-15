<?php
session_start(['name' => 'CUSTOMER_SESSION']);
require 'config.php';

// Check if already logged in
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, admin_level FROM users WHERE email = ? AND admin_level = 'customer'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['admin_level'] = $user['admin_level'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง หรือไม่ใช่ลูกค้า';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ล็อกอิน</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container" style="max-width: 400px;">
        <h2 style="text-align: center;">ล็อกอิน</h2>
        <?php if ($error): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <table>
                <tr>
                    <td><label for="email">อีเมล:</label></td>
                    <td><input type="email" name="email" id="email" required></td>
                </tr>
                <tr>
                    <td><label for="password">รหัสผ่าน:</label></td>
                    <td><input type="password" name="password" id="password" required></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button type="submit" class="button-save">ล็อกอิน</button>
                        <button type="reset" class="button-reset">ล้างข้อมูล</button>
                        <a href="register.php" class="button-register">สมัครสมาชิก</a>
                    </td>
                </tr>
            </table>
        </form>
        <p style="text-align: center; margin-top: 10px;">
            <a href="admin_login.php">ล็อกอินสำหรับผู้ดูแล</a>
        </p>
    </div>
</body>
</html>