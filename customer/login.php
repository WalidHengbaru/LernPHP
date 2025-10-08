<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name('CUSTOMER_SESSION_' . $tab_id);
    session_start();
}
require_once '../includes/functions.php';

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php?tab_id=" . urlencode($tab_id));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, admin_level, username FROM users WHERE email = :email AND admin_level = 'customer'");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['admin_level'] = $user['admin_level'];
                $_SESSION['username'] = $user['username'];
                header("Location: products.php?tab_id=" . urlencode($tab_id));
                exit;
            } else {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในฐานข้อมูล: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ (ลูกค้า)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] h-screen flex items-center justify-center m-0">
    <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg text-center">
        <h3 class="text-2xl font-bold mb-4">เข้าสู่ระบบ (ลูกค้า)</h3>
        <?php if ($error): ?><p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-left">อีเมล *</label>
                <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="relative">
                <label for="password" class="block text-left">รหัสผ่าน *</label>
                <div class="flex items-center">
                    <input type="password" name="password" id="password" required class="w-full p-2 border border-gray-300 rounded">
                    <div class="ml-2 cursor-pointer" onclick="togglePassword('password')">👁️</div>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <button type="submit" class="bg-[#93B9DD] text-white py-2 px-4 rounded hover:bg-[#78A3D4]">เข้าสู่ระบบ</button>
                <a href="register.php" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43]">สมัครสมาชิกที่นี่</a>
                <a href="reset_password.php?email=<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>&type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#B19CD7] text-white py-2 px-4 rounded hover:bg-[#aF80C0] text-center"">ลืมรหัสผ่าน?</a>
                <button href ="../admin/admin_login.php" class= "bg-[#FFB485] text-white py-2 px-4 rounded hover:bg-[#FF9967]">เข้าสู่ระบบแอดมิน</button>
            </div>
        </form>
    </div>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.type === 'password' ? 'text' : 'password';
            field.type = type;
            document.querySelector(`.password-toggle[onclick="togglePassword('${fieldId}')"]`).textContent = type === 'password' ? '👁️' : '🙈';
        }
        window.onload = () => {
            if (!new URLSearchParams(window.location.search).get('tab_id')) {
                window.location.search = 'tab_id=' + Math.random().toString(36).substr(2, 9);
            }
        };
    </script>
</body>
</html>