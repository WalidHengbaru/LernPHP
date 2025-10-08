<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    $tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
    session_name('CUSTOMER_SESSION_' . $tab_id);
    session_start();
}
require_once '../../includes/functions.php';
checkAuth('customer');

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การชำระเงินสำเร็จ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F5DCE0] min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow text-center">
        <h1 class="text-2xl font-bold mb-4">การสั่งซื้อสำเร็จ!</h1>
        <p class="mb-4"><?php echo htmlspecialchars($success); ?></p>
        <p>คุณสามารถดูรายการสั่งซื้อได้ใน <a href="../review/orders.php?tab_id=<?php echo urlencode($tab_id); ?>" class="text-blue-500 hover:underline">การสั่งซื้อ</a></p>
        <a href="../products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#92CA68] text-white py-2 px-4 rounded hover:bg-[#76BC43]">กลับไปช้อปปิ้ง</a>
    </div>
    <script>
        alert('<?php echo htmlspecialchars($success); ?>');
    </script>
</body>
</html>