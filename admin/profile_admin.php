<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name('ADMIN_SESSION_' . $tab_id);
    session_start();
}
require_once '../includes/functions.php';
checkAuth('admin', ['super_admin', 'regular_admin']);

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

// ตรวจสอบว่ามีฟิลด์ profile_image หรือไม่
$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
$has_profile_image = !empty($columns);

$select_columns = $has_profile_image
    ? "id, username, email, profile_image, admin_level, created_at"
    : "id, username, email, admin_level, created_at";

$stmt = $pdo->prepare("
    SELECT $select_columns 
    FROM users 
    WHERE id = :id 
      AND admin_level IN ('super_admin', 'regular_admin')
");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'อีเมลไม่ถูกต้อง';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        try {
            // รูปโปรไฟล์เริ่มต้น
            $profile_image = $user_profile['profile_image'] ?? getDefaultImage();

            // ถ้ามีการอัปโหลดรูปใหม่
            if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $profile_image = uploadImage($_FILES['profile_image'], 'profile');
            }

            // SQL update
            $sql = "UPDATE users 
                    SET username = :username, 
                        email = :email, 
                        profile_image = :profile_image";
            if (!empty($password)) {
                $sql .= ", password = :password";
            }
            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $params = [
                ':username'      => $username,
                ':email'         => $email,
                ':profile_image' => $profile_image,
                ':id'            => $_SESSION['user_id']
            ];
            if (!empty($password)) {
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $stmt->execute($params);

            $success = 'อัปเดตโปรไฟล์สำเร็จ';
            $user_profile = array_merge($user_profile, [
                'username'      => $username,
                'email'         => $email,
                'profile_image' => $profile_image
            ]);
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์แอดมิน</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] font-sans">
    <div class="flex h-screen">
        <aside class="w-100 h-screen bg-[#FB6F92] text-white p-5 fixed top-0 left-0 overflow-y-auto shadow-lg">
            <div class="text-center mb-6">
                <?php if ($has_profile_image && !empty($user_profile['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['profile_image']); ?>" alt="Profile Image" class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg">
                <?php else: ?>
                    <img src="/LearnPHP/assets/images.png" alt="Default Profile" class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg">
                <?php endif; ?>
                <div class="profile-details mt-2">
                    <h3 class="text-lg"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Unknown'; ?></h3>
                    <p class="text-sm">(<?php echo $_SESSION['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?>)</p>
                    <p class="text-sm">อีเมล: <?php echo htmlspecialchars($user_profile['email'] ?? 'N/A'); ?></p>
                </div>
            </div>
            <h2 class="text-center mb-6 text-xl uppercase">เมนู</h2>
            <div class="flex flex-col gap-4">
                <div class="dashboard-card">
                    <a href="admin_index.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">🏠 แดชบอร์ด</a>
                </div>
                <div class="dashboard-card">
                    <a href="profile_admin.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">👤 โปรไฟล์</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php?section=customers&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">📋 รายชื่อลูกค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php?section=products&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">📦 รายการสินค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php?section=reorder&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">🔄 Re-order</a>
                </div>
                <div class="dashboard-card">
                    <a href="add_product.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">➕ เพิ่มสินค้า</a>
                </div>
                <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                    <div class="dashboard-card">
                        <a href="add_admin.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">➕ เพิ่มแอดมิน</a>
                    </div>
                    <div class="dashboard-card">
                        <a href="admin_index.php?section=admins&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">📋 รายชื่อแอดมิน</a>
                    </div>
                <?php endif; ?>
                <div class="dashboard-card">
                    <a href="admin_index.php?section=paid-orders&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">💸 จ่ายเงินแล้ว</a>
                </div>
                <div class="dashboard-card">
                    <a href="admin_index.php?section=reviews&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">📝 รีวิวสินค้า</a>
                </div>
                <div class="dashboard-card">
                    <a href="../logout.php?type=admin&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#EB4343] hover:bg-[#E22427] text-white py-2 px-4 rounded-lg text-center block transition">🚪 ออกจากระบบ</a>
                </div>
            </div>
        </aside>
        <main class="ml-64 p-6 w-full overflow-y-auto">
            <div class="content">
                <?php if ($error): ?>
                    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white p-4 rounded shadow-lg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div id="success-alert" class="hidden fixed top-4 right-4 bg-[#E18AAA] text-white p-4 rounded shadow-lg">
                    บันทึกโปรไฟล์สำเร็จ
                </div>
                <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
                        <div class="bg-[#FB6F92] py-2 text-center text-white font-bold text-[18px] rounded-t shadow">
                            แก้ไขโปรไฟล์
                        </div>
                        <div class="mt-2">
                        <form method="POST" enctype="multipart/form-data" id="profile-form">
                        <div class="ml-2">
                            <label for="username" class="block text-sm font-medium text-gray-700">ชื่อผู้ใช้ *</label>
                            <input type="text" name="username" id="username" required value="<?php echo htmlspecialchars($user_profile['username']); ?>" class="w-[500px] ml-2 mt-1 p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                        </div>
                        <div class="ml-2">
                            <label for="email" class="block text-sm font-medium text-gray-700">อีเมล *</label>
                            <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($user_profile['email']); ?>" class="w-[500px] ml-2 mt-1 p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                        </div>
                        <div class="ml-2">
                            <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่ (ปล่อยว่างไว้ถ้าไม่เปลี่ยน)</label>
                            <input type="password" name="password" id="password" class="w-[500px] ml-2 mt-1 p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                        </div>
                        <!-- Confirm Password -->
                        <div class="ml-2">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                id="confirm_password" 
                                class="w-[500px] ml-2 mt-1 p-2 border border-gray-300 rounded mt-2 ml-4 mb-2"
                            >
                        </div>
                        <?php if ($has_profile_image): ?>
                            <div class="w-[500px] item-center ml-2">
                                <label class="block text-sm font-medium text-gray-700">รูปโปรไฟล์</label>
                                <img src="<?php echo htmlspecialchars($user_profile['profile_image'] ?? getDefaultImage()); ?>" alt="Profile Image" class="mt-1 w-32 h-32 object-cover rounded-full mx-auto">
                                <input type="file" name="profile_image" class="mt-2 block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        <?php endif; ?>
                        <div class="ml-2 mt-4 mb-4">
                            <input type="submit" name="botton" id="botton" value="บันทึกข้อมูล" class="bg-[#E18AAA] text-white py-2 px-4 rounded mb-4 hover:bg-green-600">
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
        <?php if ($success): ?>
            document.getElementById('success-alert').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('success-alert').classList.add('hidden');
                window.location.href = '/LearnPHP/admin/profile.php?tab_id=<?php echo urlencode($tab_id); ?>';
            }, 1500);
        <?php endif; ?>
        <?php if ($error): ?>
            document.getElementById('error-alert').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('error-alert').classList.add('hidden');
            }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>