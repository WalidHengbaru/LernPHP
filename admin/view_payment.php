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

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$stmt = $pdo->prepare("
    SELECT 
        p.id AS payment_id,
        p.amount,
        p.payment_method,
        p.status AS payment_status,
        o.id AS order_id,
        o.status AS order_status,
        o.created_at,
        u.username,
        u.email,
        a.name,
        a.surname,
        a.telephone,
        a.address
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON a.user_id = u.id AND a.is_primary = 1
    ORDER BY p.id DESC
");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ดูข้อมูลการชำระเงิน</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10">
<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-semibold text-blue-600 mb-6">ข้อมูลการชำระเงิน</h2>

    <div class="w-full overflow-x-auto">
        <table class="w-full border border-gray-300 text-center">
            <thead class="bg-blue-600 text-white">
                <tr class="grid grid-cols-[60px_60px_80px_200px_100px_1fr_100px_100px_100px_100px]">
                    <th class="px-1 py-2 border-r border-gray-300">รหัสชำระ</th>
                    <th class="px-1 py-2 border-r border-gray-300">รหัสคำสั่งซื้อ</th>
                    <th class="px-1 py-2 border-r border-gray-300">ชื่อลูกค้า</th>
                    <th class="px-1 py-2 border-r border-gray-300">อีเมล</th>
                    <th class="px-1 py-2 border-r border-gray-300">เบอร์โทร</th>
                    <th class="px-1 py-2 border-r border-gray-300">ที่อยู่</th>
                    <th class="px-1 py-2 border-r border-gray-300">ยอดชำระ (บาท)</th>
                    <th class="px-1 py-2 border-r border-gray-300">ช่องทาง</th>
                    <th class="px-1 py-2 border-r border-gray-300">สถานะการชำระ</th>
                    <th class="px-1 py-2">วันที่สั่งซื้อ</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($payments) > 0): ?>
                <?php foreach ($payments as $p): ?>
                    <tr class="grid grid-cols-[60px_60px_80px_200px_100px_1fr_100px_100px_100px_100px] border-t border-gray-200">
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($p['payment_id']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($p['order_id']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars(trim($p['name'].' '.$p['surname'])) ?: htmlspecialchars($p['username']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($p['email']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($p['telephone'] ?? '-'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300" title="<?php echo htmlspecialchars($p['address'] ?? '-'); ?>">
                            <?php echo htmlspecialchars($p['address'] ?? '-'); ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300 font-semibold text-green-600"><?php echo number_format($p['amount'],2); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($p['payment_method']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php 
                            $status = htmlspecialchars($p['payment_status']);
                            $color = match ($status) {
                                'paid' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'failed' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $color; ?>"><?php echo ucfirst($status); ?></span>
                        </td>
                        <td class="px-1 py-2 text-gray-600"><?php echo htmlspecialchars($p['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="py-6 text-center text-gray-500">ไม่มีข้อมูลการชำระเงิน</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
