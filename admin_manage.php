<?php
session_start(['name' => 'ADMIN_SESSION']);
require 'config.php';

// Check if user is logged in and is super_admin
if (!isset($_SESSION['user_id']) || $_SESSION['admin_level'] !== 'super_admin') {
    header("Location: admin_login.php");
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
$success = '';

/* ---------- Add new admin ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
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
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $pdo->beginTransaction();
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, admin_level, created_at) VALUES (?, ?, ?, 'regular_admin', NOW())");
                $stmt->execute([$username, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, surname, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $name, $surname, $email, $hashed_password]);
                $pdo->commit();
                $success = 'เพิ่มแอดมินเรียบร้อยแล้ว';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'เกิดข้อผิดพลาดในการเพิ่มแอดมิน: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

/* ---------- Delete admin or user ---------- */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT admin_level FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if ($id === $_SESSION['user_id']) {
        $error = 'ไม่สามารถลบตัวเองได้';
    } elseif ($user && $user['admin_level'] === 'super_admin') {
        $error = 'ไม่สามารถลบแอดมินใหญ่ได้';
    } else {
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$id]);
        if ($del->rowCount() > 0) {
            $success = 'ลบผู้ใช้เรียบร้อยแล้ว';
        } else {
            $error = 'ไม่พบผู้ใช้หรือไม่สามารถลบได้';
        }
    }
}

// Fetch admins and users
$admins = $pdo->query("SELECT id, username, email, admin_level, created_at FROM users WHERE admin_level IN ('super_admin', 'regular_admin') ORDER BY id DESC")->fetchAll();
$users = $pdo->query("SELECT id, username, email, admin_level, created_at FROM users WHERE admin_level = 'customer' ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้</title>
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
    <div class="top-bar">
        <div class="left-buttons">
            <span>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?> (แอดมินใหญ่)</span>
            <a href="logout.php"><button class="button-logout">🚪 ออกจากระบบ</button></a>
        </div>
        <div class="add-button-container">
            <a href="admin_index.php" class="button-back-form">⬅️ กลับหน้าหลัก</a>
        </div>
    </div>

    <h2 style="margin-top:30px;">จัดการผู้ใช้</h2>
    <?php if ($error): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green; text-align: center;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <h3>เพิ่มแอดมินใหม่</h3>
    <form method="POST" class="form-container" autocomplete="off" onsubmit="return validateForm()">
        <input type="hidden" name="action" value="add_admin">
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
                <button type="submit" class="button-save">เพิ่มแอดมิน</button>
                <button type="reset" class="button-reset">ล้างข้อมูล</button>
            </td>
        </tr>
    </table>
</form>

<h3 style="margin-top:30px;">รายชื่อแอดมิน</h3>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>ลำดับ</th>
        <th>ชื่อผู้ใช้</th>
        <th>อีเมล</th>
        <th>ระดับ</th>
        <th>สร้างเมื่อ</th>
        <th>ลบ</th>
    </tr>
    <?php foreach ($admins as $a): ?>
        <tr>
            <td><?php echo $a['id']; ?></td>
            <td><?php echo htmlspecialchars($a['username']); ?></td>
            <td><?php echo htmlspecialchars($a['email']); ?></td>
            <td><?php echo $a['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?></td>
            <td><?php echo $a['created_at']; ?></td>
            <td>
                <?php if ($a['admin_level'] === 'regular_admin' && $a['id'] !== $_SESSION['user_id']): ?>
                    <a href="admin_manage.php?delete=<?php echo $a['id']; ?>" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบแอดมินนี้?');">
                        <button class="button-del">🗑️ ลบ</button>
                    </a>
                <?php else: ?>
                    <span>-</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h3 style="margin-top:30px;">รายชื่อผู้ใช้ (ลูกค้า)</h3>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>ลำดับ</th>
        <th>ชื่อผู้ใช้</th>
        <th>อีเมล</th>
        <th>ระดับ</th>
        <th>สร้างเมื่อ</th>
        <th>ลบ</th>
    </tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td>ลูกค้า</td>
            <td><?php echo $u['created_at']; ?></td>
            <td>
                <a href="admin_manage.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?');">
                    <button class="button-del">🗑️ ลบ</button>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>