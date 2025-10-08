<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_name($_GET['type'] === 'admin' ? 'ADMIN_SESSION' : 'CUSTOMER_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name(($_GET['type'] === 'admin' ? 'ADMIN_SESSION_' : 'CUSTOMER_SESSION_') . $tab_id);
    session_start();
}
require_once 'includes/functions.php';

$type = $_GET['type'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    session_destroy();
    header("Location: " . ($type === 'admin' ? 'admin/admin_login.php?tab_id=' . urlencode($tab_id) : 'customer/login.php?tab_id=' . urlencode($tab_id)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการออกจากระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f5f5f5; }
        .bg-primary { background-color: #FFA9A9; }
        .hover\:bg-primary-dark:hover { background-color: #ff8c8c; }
    </style>
</head>
<body class="bg-[#F5DCE0] min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg text-center">
        <h3 class="text-2xl font-bold mb-4 text-gray-800">ยืนยันการออกจากระบบ</h3>
        <p class="text-gray-600 mb-6">คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?</p>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <div class="flex gap-4 justify-center">
                <button 
                    type="submit" 
                    name="confirm_logout" 
                    class="bg-primary text-white py-2 px-4 rounded hover:bg-primary-dark transition-colors">
                    ออกจากระบบ
                </button>
                <a 
                    href="<?php echo $type === 'admin' ? 'admin/admin_index.php?tab_id=' . urlencode($tab_id) : 'customer/products.php?tab_id=' . urlencode($tab_id); ?>" 
                    class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition-colors">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</body>
</html>