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

$error = '';
$success = false;
$product = null;

$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
$has_profile_image = !empty($columns);
$select_columns = $has_profile_image ? "id, username, email, profile_image, admin_level, created_at" : "id, username, email, admin_level, created_at";
$stmt = $pdo->prepare("SELECT $select_columns FROM users WHERE id = :id AND admin_level IN ('super_admin', 'regular_admin')");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT id, name, sku, category, description, full_detail, price, product_image, stock, active FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $full_detail = trim($_POST['full_detail'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $current_image = $_POST['current_image'] ?? '';

    if (empty($name) || empty($sku) || empty($price) || !is_numeric($price) || $price < 0 || !is_numeric($stock) || $stock < 0) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง';
    } else {
        $image_path = $current_image;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $image_path = uploadImage($_FILES['product_image'], 'product');
            if (!$image_path) {
                $error = 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ';
            }
        } elseif (!$current_image) {
            $image_path = getDefaultImage();
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("SELECT 1 FROM products WHERE (sku = :sku) AND id != :id");
                $stmt->execute([':sku' => $sku, ':id' => $id]);
                if ($stmt->fetch()) {
                    $error = 'SKU นี้ถูกใช้งานแล้ว';
                } else {
                    $stmt = $pdo->prepare("UPDATE products SET name = :name, sku = :sku, category = :category, description = :description, full_detail = :full_detail, price = :price, product_image = :product_image, stock = :stock, active = :active WHERE id = :id");
                    $stmt->execute([
                        ':name' => $name,
                        ':sku' => $sku,
                        ':category' => $category,
                        ':description' => $description,
                        ':full_detail' => $full_detail,
                        ':price' => $price,
                        ':product_image' => $image_path,
                        ':stock' => $stock,
                        ':active' => $active,
                        ':id' => $id
                    ]);
                    $success = true;
                }
            } catch (PDOException $e) {
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
    <title>แก้ไขสินค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
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
                <h1 class="text-3xl font-bold mb-6">แก้ไขสินค้า</h1>
                <?php if ($error): ?>
                    <div id="error-alert" class="bg-red-500 text-white p-4 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div id="success-alert" class="bg-green-500 text-white p-4 rounded mb-4">บันทึกการแก้ไขสำเร็จ</div>
                <?php endif; ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <?php if ($product): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_product" value="1">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>">
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700">ชื่อสินค้า *</label>
                                <input type="text" name="name" id="name" required value="<?php echo htmlspecialchars($product['name']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="mb-4">
                                <label for="product_image" class="block text-sm font-medium text-gray-700">รูปสินค้า</label>
                                <img src="<?php echo htmlspecialchars($product['product_image'] ?? getDefaultImage()); ?>" alt="Product Image" class="mt-1 w-32 ml-4 h-32 object-cover rounded">
                                <input type="file" name="product_image" id="product_image" accept="image/jpeg,image/png" class="mt-2 block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <div class="mb-4">
                                <label for="sku" class="block text-sm font-medium text-gray-700">SKU *</label>
                                <input type="text" name="sku" id="sku" required value="<?php echo htmlspecialchars($product['sku']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-gray-700">หมวดหมู่</label>
                                <select name="category" id="category" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <option value="fresh" <?php echo $product['category'] === 'fresh' ? 'selected' : ''; ?>>สินค้าสด</option>
                                    <option value="it" <?php echo $product['category'] === 'it' ? 'selected' : ''; ?>>สินค้า IT</option>
                                    <option value="general" <?php echo $product['category'] === 'general' ? 'selected' : ''; ?>>สินค้าทั่วไป</option>
                                </select>
                            </div>
                            <!-- คำอธิบายสั้น -->
<div class="mb-4">
    <label for="description" class="block text-sm font-medium text-gray-700">คำอธิบายสั้น</label>
    <textarea name="description" id="description" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2"><?php 
        echo $product['description']; // ❌ เอา htmlspecialchars ออก
    ?></textarea>
</div>

<!-- รายละเอียดทั้งหมด -->
<div class="mb-4">
    <label for="full_detail" class="block text-sm font-medium text-gray-700">รายละเอียดทั้งหมด</label>
    <textarea name="full_detail" id="full_detail" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2"><?php 
        echo $product['full_detail']; // ❌ เอา htmlspecialchars ออก
    ?></textarea>
</div>

                            <div class="mb-4">
                                <label for="price" class="block text-sm font-medium text-gray-700">ราคา *</label>
                                <input type="number" name="price" id="price" step="0.01" required value="<?php echo htmlspecialchars($product['price']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="mb-4">
                                <label for="stock" class="block text-sm font-medium text-gray-700">สต็อก *</label>
                                <input type="number" name="stock" id="stock" required value="<?php echo htmlspecialchars($product['stock']); ?>" class="w-[500px] p-2 border border-gray-300 rounded mt-2 ml-4 mb-2">
                            </div>
                            <div class="mb-4">
                                <label for="active" class="block text-sm font-medium text-gray-700">สถานะสินค้า</label>
                                <input type="checkbox" name="active" id="active" <?php echo $product['active'] ? 'checked' : ''; ?> class="ml-4">
                                <label for="active" class="ml-2">แสดงบนหน้าลูกค้า</label>
                            </div>
                            <div class="ml-2 mt-4 mb-4">
                                <input type="submit" value="บันทึกการแก้ไข" class="bg-[#92CA68] text-white py-2 px-4 rounded mb-4 hover:bg-[#76BC43]">
                                <a href="/LearnPHP/admin/admin_index.php?section=products&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#EB4343] text-white py-2 px-4 rounded mb-4 hover:bg-[#E22427]">ยกเลิก</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-red-500">ไม่พบสินค้าที่เลือก</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        ClassicEditor.create(document.querySelector('#description')).catch(error => { console.error(error); });
        ClassicEditor.create(document.querySelector('#full_detail')).catch(error => { console.error(error); });

        <?php if ($success): ?>
        document.getElementById('success-alert').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('success-alert').classList.add('hidden');
            window.location.href = '/LearnPHP/admin/admin_index.php?section=products&tab_id=<?php echo urlencode($tab_id); ?>';
        }, 3000);
        <?php endif; ?>

        <?php if ($error): ?>
        document.getElementById('error-alert').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('error-alert').classList.add('hidden');
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>