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
checkAuth('admin', ['super_admin', 'admin']);

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . htmlspecialchars($e->getMessage()));
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$action = $_GET['action'] ?? '';

if ($action === 'delete_product' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT product_image FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        if ($product && $product['product_image'] && file_exists($product['product_image']) && $product['product_image'] !== getDefaultImage()) {
            unlink($product['product_image']);
        }
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: /LearnPHP/admin/admin_index.php?section=products&success=" . urlencode('ลบสินค้าสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        header("Location: admin_index.php?section=products&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} elseif ($action === 'delete_customer' && isset($_GET['user_id']) && $_SESSION['admin_level'] === 'super_admin') {
    $user_id = intval($_GET['user_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND admin_level = 'customer'");
        $stmt->execute([':id' => $user_id]);
        header("Location: admin_index.php?section=customers&success=" . urlencode('ลบลูกค้าสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        header("Location: admin_index.php?section=customers&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} elseif ($action === 'toggle_product_status' && isset($_GET['id']) && $_SESSION['admin_level'] === 'super_admin') {
    $id = intval($_GET['id']);
    try {
        // สลับสถานะ active
        $stmt = $pdo->prepare("UPDATE products SET active = NOT active WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะสินค้าสำเร็จ']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
} elseif ($action === 'toggle_customer_status' && isset($_POST['user_id']) && $_SESSION['admin_level'] === 'super_admin') {
    $user_id = intval($_POST['user_id']);
    $active = intval($_POST['active']);
    try {
        $stmt = $pdo->prepare("UPDATE customers SET active = :active WHERE user_id = :user_id");
        $stmt->execute([':active' => $active, ':user_id' => $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะลูกค้าสำเร็จ']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
} elseif ($action === 'delete_admin' && isset($_GET['id']) && $_SESSION['admin_level'] === 'super_admin') {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $admin = $stmt->fetch();
        if ($admin && $admin['profile_image'] && file_exists($admin['profile_image']) && $admin['profile_image'] !== getDefaultImage()) {
            unlink($admin['profile_image']);
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND admin_level IN ('super_admin', 'regular_admin')");
        $stmt->execute([':id' => $id]);
        header("Location: admin_index.php?section=admins&success=" . urlencode('ลบแอดมินสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        header("Location: admin_index.php?section=admins&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} elseif ($action === 'delete_order' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin_index.php?section=paid-orders&success=" . urlencode('ลบคำสั่งซื้อสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        header("Location: admin_index.php?section=paid-orders&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} elseif ($action === 'delete_review' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin_index.php?section=reviews&success=" . urlencode('ลบรีวิวสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        header("Location: admin_index.php?section=reviews&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} elseif ($action === 'feature_product' && isset($_GET['id']) && $_SESSION['admin_level'] === 'super_admin') {
    $id = intval($_GET['id']);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE products SET order_index = 0");
        $stmt->execute();
        $stmt = $pdo->prepare("UPDATE products SET order_index = (SELECT MAX(order_index) + 1 FROM products) WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $pdo->commit();
        header("Location: admin_index.php?section=products&success=" . urlencode('ตั้งเป็นสินค้าแนะนำสำเร็จ') . "&tab_id=" . urlencode($tab_id));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: admin_index.php?section=products&error=" . urlencode('เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
} else {
    header("Location: admin_index.php?error=" . urlencode('การกระทำไม่ถูกต้อง') . "&tab_id=" . urlencode($tab_id));
    exit;
}
?>