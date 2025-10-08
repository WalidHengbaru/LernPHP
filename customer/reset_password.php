<?php
session_name('CUSTOMER_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php';

$pdo = getPDO();
$error = '';
$success = '';
$reset_email = $_GET['email'] ?? '';
$reset_password = '';
$user_type = $_GET['type'] ?? 'customer';
$tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } else {
        $admin_levels = $user_type === 'admin' ? ['super_admin', 'regular_admin'] : ['customer'];
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? AND admin_level IN ('" . implode("','", $admin_levels) . "')");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $reset_password = bin2hex(random_bytes(4));
            $hashed_password = password_hash($reset_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            $reset_email = $user['email'];
            $success = 'รหัสผ่านถูกรีเซ็ตสำเร็จ';
        } else {
            $error = 'ไม่พบอีเมลนี้ในระบบสำหรับ ' . ($user_type === 'admin' ? 'แอดมิน' : 'ลูกค้า');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg text-center">
        <h3 class="text-2xl font-bold mb-4 text-gray-800">รีเซ็ตรหัสผ่าน <?php echo $user_type === 'admin' ? 'แอดมิน' : 'ลูกค้า'; ?></h3>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="text-green-500 mb-4">
                <p><?php echo htmlspecialchars($success); ?></p>
                <div class="mt-2">
                    <p><strong>อีเมลที่ใช้รีเซ็ต:</strong> <?php echo htmlspecialchars($reset_email); ?></p>
                    <p><strong>รหัสผ่านใหม่:</strong> <?php echo htmlspecialchars($reset_password); ?></p>
                    <p>กรุณาจดรหัสผ่านนี้และเข้าสู่ระบบเพื่อเปลี่ยนรหัสผ่านใหม่ในโปรไฟล์</p>
                </div>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($user_type); ?>">
            <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
            <div>
                <label for="email" class="block text-left text-gray-700 font-semibold">อีเมล *</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    required 
                    value="<?php echo htmlspecialchars($reset_email); ?>" 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div class="flex gap-4 justify-center">
                <button 
                    type="submit" 
                    class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                    รีเซ็ตรหัสผ่าน
                </button>
                <a 
                    href="<?php echo $user_type === 'admin' ? '../admin/admin_login.php?tab_id=' . urlencode($tab_id) : 'login.php?tab_id=' . urlencode($tab_id); ?>" 
                    class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition-colors">
                    กลับไปเข้าสู่ระบบ
                </a>
            </div>
        </form>
    </div>
</body>
</html>