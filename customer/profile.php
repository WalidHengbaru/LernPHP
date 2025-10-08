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
checkAuth('customer');

try {
    $pdo = getPDO(); // Uses getPDO() from db.php via functions.php
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());

if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 'customer') {
    header("Location: login.php?tab_id=" . urlencode($tab_id));
    exit;
}

// ตรวจสอบว่ามี profile_image หรือไม่
$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
$has_profile_image = !empty($columns);

$select_columns = $has_profile_image
    ? "u.username, u.email, u.profile_image, c.name, c.surname, c.telephone"
    : "u.username, u.email, c.name, c.surname, c.telephone";

$stmt = $pdo->prepare("SELECT $select_columns FROM users u 
                       LEFT JOIN customers c ON u.id = c.user_id 
                       WHERE u.id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Migrate address จาก customers ถ้ามี
migrateCustomerAddress($pdo, $_SESSION['user_id'], $user_profile);

// ดึง primary address
$primary_address = getPrimaryAddress($pdo, $_SESSION['user_id']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'change_password')) {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $profile_image = uploadImage($_FILES['profile_image'] ?? null, 'customer_profile') ?: ($user_profile['profile_image'] ?? getDefaultImage());

    if (empty($name) || empty($surname) || empty($email)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$name, $email, $profile_image, $_SESSION['user_id']]);
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, surname = ?, email = ?, telephone = ? WHERE user_id = ?");
            $stmt->execute([$name, $surname, $email, $telephone, $_SESSION['user_id']]);
            $pdo->commit();
            $success = 'อัปเดตข้อมูลสำเร็จ';
            header("Location: profile.php?tab_id=" . urlencode($tab_id));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($new_password !== $confirm_password) {
        $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $success = 'เปลี่ยนรหัสผ่านสำเร็จ';
            header("Location: profile.php?tab_id=" . urlencode($tab_id));
            exit;
        } else {
            $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen">
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-xl font-bold">E-Commerce</a>
            <div class="flex space-x-6">
                <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="profile.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">โปรไฟล์</a>
                <a href="cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ตะกร้าสินค้า</a>
                <a href="favorites.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าที่ถูกใจ</a>
                <a href="review/orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">การสั่งซื้อ</a>
                <a href="../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <?php if ($error): ?><p class="text-red-500"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p class="text-green-500"><?php echo $success; ?></p><?php endif; ?>
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-4">ข้อมูลส่วนตัว</h1>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="name" class="block text-left">ชื่อ</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_profile['name'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="surname" class="block text-left">นามสกุล</label>
                    <input type="text" name="surname" id="surname" value="<?php echo htmlspecialchars($user_profile['surname'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="email" class="block text-left">อีเมล</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="telephone" class="block text-left">เบอร์โทร</label>
                    <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($user_profile['telephone'] ?? ''); ?>" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="profile_image" class="block text-left">รูปโปรไฟล์</label>
                    <img src="<?php echo htmlspecialchars($user_profile['profile_image'] ?? getDefaultImage()); ?>" alt="Profile Image" class="w-32 h-32 bg-white object-cover rounded mb-2">
                    <input type="file" name="profile_image" id="profile_image" accept="image/jpeg,image/png,image/gif" class="w-full p-2 bg-white border border-gray-300 rounded">
                </div>
                <div class="mt-6">
                    <h2 class="text-xl font-bold mb-2">ที่อยู่หลัก</h2>
                    <?php if ($primary_address): ?>
                        <div class="bg-white p-4 rounded shadow mb-4">
                            <p class="font-semibold"><?php echo htmlspecialchars($primary_address['name'] . ' ' . $primary_address['surname']) . ' | ' . htmlspecialchars($primary_address['telephone']); ?></p>
                            <p><?php echo htmlspecialchars($primary_address['address']); ?></p>
                        </div>
                        <a href="addresses.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">จัดการที่อยู่</a>
                    <?php else: ?>
                        <p class="text-red-500">ไม่มีที่อยู่หลัก กรุณาเพิ่มที่อยู่</p>
                        <a href="addresses.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">เพิ่มที่อยู่</a>
                    <?php endif; ?>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43]">บันทึก</button>
                    <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">ยกเลิก</a>
                </div>
            </form>
            <h2 class="text-xl font-bold mt-6 mb-2">เปลี่ยนรหัสผ่าน</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label for="current_password" class="block text-left">รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" id="current_password" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="new_password" class="block text-left">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" id="new_password" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label for="confirm_password" class="block text-left">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43]">เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>