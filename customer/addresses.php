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
    $pdo = getPDO(); // Uses getPDO() from db.php
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$from = $_GET['from'] ?? '';
$edit_id = intval($_GET['edit'] ?? 0);
$error = '';
$success = '';

// ดึง addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_primary DESC, created_at DESC");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// ดึงข้อมูลสำหรับแก้ไข
$edit_address = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $edit_id, ':user_id' => $_SESSION['user_id']]);
    $edit_address = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address']) || isset($_POST['edit_address'])) {
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if (empty($name) || empty($surname) || empty($telephone) || empty($address)) {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } else {
            if (isset($_POST['add_address'])) {
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, name, surname, telephone, address, is_primary, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$_SESSION['user_id'], $name, $surname, $telephone, $address]);
                $success = 'เพิ่มที่อยู่สำเร็จ';
            } elseif (isset($_POST['edit_address']) && $edit_id) {
                $stmt = $pdo->prepare("UPDATE addresses SET name = ?, surname = ?, telephone = ?, address = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$name, $surname, $telephone, $address, $edit_id, $_SESSION['user_id']]);
                $success = 'แก้ไขที่อยู่สำเร็จ';
            }
            header("Location: addresses.php?tab_id=" . urlencode($tab_id));
            exit;
        }
    } elseif (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $pdo->prepare("SELECT is_primary FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() == 1) {
            $error = 'ไม่สามารถลบที่อยู่หลักได้ กรุณาตั้งที่อยู่อื่นเป็นหลักก่อน';
        } else {
            $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $success = 'ลบที่อยู่สำเร็จ';
            header("Location: addresses.php?tab_id=" . urlencode($tab_id));
            exit;
        }
    } elseif (isset($_POST['set_primary_id'])) {
        $id = intval($_POST['set_primary_id']);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE addresses SET is_primary = 0 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE addresses SET is_primary = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $pdo->commit();
        $success = 'ตั้งเป็นที่อยู่หลักสำเร็จ';
        header("Location: addresses.php?tab_id=" . urlencode($tab_id));
        exit;
    } elseif (isset($_POST['select_id']) && $from === 'checkout') {
        $_SESSION['selected_address_id'] = intval($_POST['select_id']);
        header("Location: checkout.php?tab_id=" . urlencode($tab_id));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการที่อยู่</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen p-4">
    <nav class="bg-[#FB6F92] text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-xl font-bold">E-Commerce</a>
            <div class="flex space-x-6">
                <a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a>
                <a href="profile.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">โปรไฟล์</a>
                <a href="cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ตะกร้าสินค้า</a>
                <a href="favorites.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าที่ถูกใจ</a>
                <a href="../logout.php?type=customer&tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">ที่อยู่ของฉัน</h1>
        <?php if ($error): ?><p class="text-red-500"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p class="text-green-500"><?php echo $success; ?></p><?php endif; ?>
        
        <!-- รายการที่อยู่ -->
        <?php if (empty($addresses)): ?>
            <p class="text-gray-500">ไม่มีที่อยู่ กรุณาเพิ่มที่อยู่ใหม่</p>
        <?php else: ?>
            <?php foreach ($addresses as $addr): ?>
                <div class="bg-white p-4 mb-4 rounded shadow">
                    <p class="font-semibold"><?php echo htmlspecialchars($addr['name'] . ' ' . $addr['surname']) . ' | ' . htmlspecialchars($addr['telephone']); ?></p>
                    <p><?php echo htmlspecialchars($addr['address']); ?></p>
                    <?php if ($addr['is_primary']): ?><span class="text-green-500 font-semibold">ที่อยู่หลัก</span><?php endif; ?>
                    <div class="flex gap-2 mt-2">
                        <?php if (!$addr['is_primary']): ?>
                            <form method="POST">
                                <input type="hidden" name="set_primary_id" value="<?php echo $addr['id']; ?>">
                                <button type="submit" class="text-blue-500 hover:underline">ตั้งเป็นหลัก</button>
                            </form>
                        <?php endif; ?>
                        <a href="addresses.php?edit=<?php echo $addr['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="text-yellow-500 hover:underline">แก้ไข</a>
                        <form method="POST">
                            <input type="hidden" name="delete_id" value="<?php echo $addr['id']; ?>">
                            <button type="submit" class="text-red-500 hover:underline">ลบ</button>
                        </form>
                        <?php if ($from === 'checkout'): ?>
                            <form method="POST">
                                <input type="hidden" name="select_id" value="<?php echo $addr['id']; ?>">
                                <button type="submit" class="bg-green-500 text-white py-1 px-2 rounded hover:bg-green-600">เลือกที่อยู่นี้</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- ฟอร์มเพิ่ม/แก้ไขที่อยู่ -->
        <h2 class="text-xl font-bold mt-6 mb-2"><?php echo $edit_id ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่'; ?></h2>
        <form method="POST" class="bg-white p-4 rounded shadow space-y-4">
            <input type="hidden" name="<?php echo $edit_id ? 'edit_address' : 'add_address'; ?>" value="1">
            <div>
                <label class="block text-gray-700 font-semibold">ชื่อ</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_address['name'] ?? ($user_profile['name'] ?? '')); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold">นามสกุล</label>
                <input type="text" name="surname" value="<?php echo htmlspecialchars($edit_address['surname'] ?? ($user_profile['surname'] ?? '')); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold">เบอร์โทร</label>
                <input type="text" name="telephone" value="<?php echo htmlspecialchars($edit_address['telephone'] ?? ($user_profile['telephone'] ?? '')); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold">ที่อยู่</label>
                <textarea name="address" class="w-full p-2 border rounded" required><?php echo htmlspecialchars($edit_address['address'] ?? ''); ?></textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600"><?php echo $edit_id ? 'บันทึกการแก้ไข' : 'เพิ่มที่อยู่'; ?></button>
                <a href="addresses.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>