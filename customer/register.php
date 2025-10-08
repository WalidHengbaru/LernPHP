<?php
session_name('CUSTOMER_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php';

$pdo = getPDO();
$error = '';
$success = '';
$tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($name) || empty($surname) || empty($telephone) || empty($address)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $pdo->beginTransaction();
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $profile_image = uploadImage($_FILES['profile_image'] ?? null, 'customer_profile') ?: '../assets/Uploads/default.png';
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, admin_level, profile_image, created_at) VALUES (?, ?, ?, 'customer', ?, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $profile_image]);
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, surname, email, telephone, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $name, $surname, $email, $telephone, $address]);
                $pdo->commit();
                $success = 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ';
                header("Location: login.php?tab_id=" . urlencode($tab_id) . "&success=" . urlencode($success));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
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
    <title>สมัครสมาชิก</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full p-6 bg-white rounded-lg shadow-lg">
        <h3 class="text-2xl font-bold text-center mb-6 text-gray-800">สมัครสมาชิก</h3>
        <?php if ($error): ?>
            <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-center mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
            <div>
                <label for="username" class="block text-left text-gray-700 font-semibold">ชื่อผู้ใช้ *</label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="email" class="block text-left text-gray-700 font-semibold">อีเมล *</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="password" class="block text-left text-gray-700 font-semibold">รหัสผ่าน *</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="name" class="block text-left text-gray-700 font-semibold">ชื่อ *</label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="surname" class="block text-left text-gray-700 font-semibold">นามสกุล *</label>
                <input 
                    type="text" 
                    name="surname" 
                    id="surname" 
                    value="<?php echo htmlspecialchars($_POST['surname'] ?? ''); ?>" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="telephone" class="block text-left text-gray-700 font-semibold">เบอร์โทรศัพท์ *</label>
                <input 
                    type="text" 
                    name="telephone" 
                    id="telephone" 
                    value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
            </div>
            <div>
                <label for="address" class="block text-left text-gray-700 font-semibold">ที่อยู่ *</label>
                <textarea 
                    name="address" 
                    id="address" 
                    required 
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="profile_image" class="block text-left text-gray-700 font-semibold">รูปโปรไฟล์ (ไม่จำเป็น)</label>
                <input 
                    type="file" 
                    name="profile_image" 
                    id="profile_image" 
                    accept="image/jpeg,image/png,image/gif" 
                    class="w-full p-2 border border-gray-300 rounded text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="flex gap-4 justify-center">
                <button 
                    type="submit" 
                    class="bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600 transition-colors">
                    สมัคร
                </button>
                <a 
                    href="login.php?tab_id=<?php echo urlencode($tab_id); ?>" 
                    class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition-colors">
                    กลับไปเข้าสู่ระบบ
                </a>
            </div>
        </form>
    </div>
</body>
</html>