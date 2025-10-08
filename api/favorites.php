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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $product_id = $input['product_id'] ?? null;
    $pdo = getPDO();
    
    if ($action === 'toggle' && $product_id) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบสินค้า']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            echo json_encode(['status' => 'success', 'message' => 'ลบออกจากรายการโปรดสำเร็จ']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มในรายการโปรดสำเร็จ']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>