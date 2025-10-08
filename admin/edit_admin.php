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
checkAuth('admin', ['super_admin']);

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$error = '';
$success = false;
$admin = null;

$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
$has_profile_image = !empty($columns);
$select_columns = $has_profile_image ? "id, username, email, profile_image, admin_level, created_at" : "id, username, email, admin_level, created_at";
$stmt = $pdo->prepare("SELECT $select_columns FROM users WHERE id = :id AND admin_level IN ('super_admin', 'regular_admin')");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_profile) {
    session_destroy();
    header("Location: admin_login.php?tab_id=" . urlencode($tab_id));
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT $select_columns FROM users WHERE id = :id AND admin_level IN ('super_admin', 'regular_admin')");
    $stmt->execute([':id' => $id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $admin_level = trim($_POST['admin_level']);
    if ($username && $email && filter_var($email, FILTER_VALIDATE_EMAIL) && in_array($admin_level, ['super_admin', 'regular_admin'])) {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE (username = :username OR email = :email) AND id != :id");
        $stmt->execute([':username' => $username, ':email' => $email, ':id' => $id]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $profile_image = $admin['profile_image'] ?? getDefaultImage();
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $new_image = uploadImage($_FILES['profile_image'], 'admin_profile');
                if ($new_image) {
                    if ($profile_image && file_exists('C:/xampp/htdocs' . $profile_image) && $profile_image !== getDefaultImage()) {
                        unlink('C:/xampp/htdocs' . $profile_image);
                    }
                    $profile_image = $new_image;
                } else {
                    $error = 'ไม่สามารถอัปโหลดรูปภาพได้';
                }
            }
            if (!$error) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, admin_level = :admin_level, profile_image = :profile_image WHERE id = :id");
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':admin_level' => $admin_level,
                        ':profile_image' => $profile_image,
                        ':id' => $id
                    ]);
                    $success = true;
                } catch (PDOException $e) {
                    $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขแอดมิน</title>
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
        <!-- Main content -->
        <main class="ml-64 p-6 w-full overflow-y-auto">
            <div class="content">
                <?php if ($error): ?>
                    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white p-4 rounded shadow-lg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div id="success-alert" class="hidden fixed top-4 right-4 bg-green-500 text-white p-4 rounded shadow-lg">
                    บันทึกการแก้ไขแอดมินสำเร็จ
                </div>
                <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
                        <div class="bg-[#E18AAA] py-2 text-center text-white font-bold text-[18px] rounded-t shadow">
                            แก้ไขแอดมิน
                        </div>
                        <div class="mt-2">
                            <?php if ($admin): ?>
                            <form method="POST" enctype="multipart/form-data" id="edit-admin-form">
                            <input type="hidden" name="edit_admin" value="1">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($admin['id']); ?>">
                            <div class="ml-2">
                                <label class="block">รูปภาพปัจจุบัน</label>
                                <img src="<?php echo htmlspecialchars($admin['profile_image'] ?? getDefaultImage()); ?>" alt="Profile Image" class="w-32 h-auto mt-2 ml-4 mb-2">
                            </div>
                            <div class="ml-2">
                                <label for="profile_image" class="block">เปลี่ยนรูปภาพ</label>
                                <input type="file" name="profile_image" id="profile_image" accept="image/jpeg,image/png" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="ml-2">
                                <label for="username" class="block">ชื่อผู้ใช้ *</label>
                                <input type="text" name="username" id="username" required value="<?php echo htmlspecialchars($admin['username']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="ml-2">
                                <label for="email" class="block">อีเมล *</label>
                                <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($admin['email']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="ml-2">
                                <label for="admin_level" class="block">ระดับ *</label>
                                <select name="admin_level" id="admin_level" required class="w-[500px] p-2 border border-gray-300 rounded  mt-2 ml-4 mb-2">
                                    <option value="regular_admin" <?php echo $admin['admin_level'] === 'regular_admin' ? 'selected' : ''; ?>>แอดมินรอง</option>
                                    <option value="super_admin" <?php echo $admin['admin_level'] === 'super_admin' ? 'selected' : ''; ?>>แอดมินใหญ่</option>
                                </select>
                            </div>
                            <div class="ml-2 mt-4 mb-4">
                                <input type="submit" name="botton" id="botton" value="บันทึก" class="bg-green-500 text-white py-2 px-4 rounded mb-4 hover:bg-green-600">
                                <a href="/LearnPHP/admin/admin_index.php?section=admins&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-gray-500 text-white py-2 px-4 rounded mb-4 hover:bg-gray-600">ยกเลิก</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-red-500">ไม่พบแอดมินที่เลือก</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        <?php if ($success): ?>
            document.getElementById('success-alert').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('success-alert').classList.add('hidden');
                window.location.href = '/LearnPHP/admin/admin_index.php?section=admins&tab_id=<?php echo urlencode($tab_id); ?>';
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