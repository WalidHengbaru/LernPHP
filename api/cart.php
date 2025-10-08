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
    $quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
    $pdo = getPDO();

    if ($action === 'add' && $product_id) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product && $product['stock'] >= $quantity) {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE quantity = quantity + ?, created_at = NOW()");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มในตะกร้าสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'สินค้าหมดหรือไม่พบ']);
        }
    } elseif ($action === 'update' && $product_id && $quantity > 0) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product && $product['stock'] >= $quantity) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, created_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตจำนวนสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'จำนวนไม่พอหรือไม่พบสินค้า']);
        }
    } elseif ($action === 'remove' && $product_id) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        echo json_encode(['status' => 'success', 'message' => 'ลบออกจากตะกร้าสำเร็จ']);
    } elseif ($action === 'checkout') {
        $selected_address_id = $_SESSION['selected_address_id'] ?? null;
        if (!$selected_address_id) {
            $address = getPrimaryAddress($pdo, $_SESSION['user_id']);
            $selected_address_id = $address['id'] ?? null;
        }
        if (!$selected_address_id) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกที่อยู่']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.stock 
                               FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.user_id = ? AND p.active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll();
        if ($cart_items) {
            try {
                $pdo->beginTransaction();
                foreach ($cart_items as $item) {
                    if ($item['stock'] < $item['quantity']) {
                        throw new Exception('สินค้า ' . htmlspecialchars($item['name']) . ' มีสต็อกไม่เพียงพอ');
                    }
                    $stmt = $pdo->prepare("INSERT INTO payments (user_id, product_id, quantity, name, address, telephone, address_id, payment_method, created_at) 
                                           VALUES (:user_id, :product_id, :quantity, :name, :address, :telephone, :address_id, :payment_method, NOW())");
                    $stmt->execute([
                        ':user_id' => $item['user_id'],
                        ':product_id' => $item['product_id'],
                        ':quantity' => $item['quantity'],
                        ':name' => $item['name'] . ' ' . $item['surname'],
                        ':address' => $item['address'],
                        ':telephone' => $item['telephone'],
                        ':address_id' => $selected_address_id,
                        ':payment_method' => $input['payment_method'] ?? 'cod'
                    ]);
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                unset($_SESSION['selected_address_id']);
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'ชำระเงินสำเร็จ']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ตะกร้าสินค้าว่างเปล่า']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>