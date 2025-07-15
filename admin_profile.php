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

// Fetch admin data
$stmt = $pdo->prepare("SELECT id, username, email, admin_level, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    error_log('Admin not found for user_id: ' . $_SESSION['user_id']);
    echo "<p class='alert alert-error'>❌ ไม่พบข้อมูลผู้ดูแล</p>";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate inputs
    if ($username === '' || $email === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        // Check for duplicate username or email (excluding current user)
        $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check->execute([$username, $email, $_SESSION['user_id']]);
        if ($check->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว';
        } else {
            // Handle image upload
            $profile_image = $admin['profile_image'] ?? null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file = $_FILES['profile_image'];
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'admin_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $upload_dir = 'uploads/';
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
            }

            if (!$error) {
                // Update user data
                $fields = ['username = :username', 'email = :email'];
                $params = [':username' => $username, ':email' => $email, ':id' => $_SESSION['user_id']];
                if ($password !== '') {
                    $fields[] = 'password = :password';
                    $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($profile_image !== null) {
                    $fields[] = 'profile_image = :profile_image';
                    $params[':profile_image'] = $profile_image;
                }
                $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = 'บันทึกข้อมูลสำเร็จ';
                    $_SESSION['username'] = $username;
                    $admin['username'] = $username;
                    $admin['email'] = $email;
                    $admin['profile_image'] = $profile_image;
                } catch (PDOException $e) {
                    $error = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . htmlspecialchars($e->getMessage());
                }
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
    <title>โปรไฟล์ผู้ดูแล</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            const username = document.querySelector('input[name="username"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            if (!username || !email) {
                alert('กรุณากรอกชื่อผู้ใช้และอีเมล');
                return false;
            }
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('รูปแบบอีเมลไม่ถูกต้อง');
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
    <div class="container">
        <h2>โปรไฟล์ผู้ดูแล</h2>
        <?php if ($error): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (empty($admin)): ?>
            <p class="alert alert-error">❌ ไม่สามารถโหลดข้อมูลผู้ดูแลได้</p>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" class="form-container" onsubmit="return validateForm()">
                <?php if (!empty($admin['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Profile Image" class="profile-image">
                <?php endif; ?>
                <div class="form-group">
                    <label>รูปโปรไฟล์</label>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif">
                </div>
                <fieldset>
                    <legend>ข้อมูลบัญชี</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ชื่อผู้ใช้ *</label>
                            <input name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>อีเมล *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>รหัสผ่านใหม่ (ปล่อยว่างหากไม่เปลี่ยน)</label>
                            <input type="password" name="password">
                        </div>
                    </div>
                </fieldset>
                <div class="button-container">
                    <button type="submit" class="button button-primary">บันทึกข้อมูล</button>
                    <button type="reset" class="button button-secondary">ล้างข้อมูล</button>
                    <a href="admin_index.php" class="button button-secondary">⬅️ ย้อนกลับ</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>