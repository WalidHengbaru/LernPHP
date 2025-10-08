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

$section = $_GET['section'] ?? 'dashboard';
$search = trim($_GET['search'] ?? '');
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

try {
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
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงโปรไฟล์ผู้ใช้: ' . htmlspecialchars($e->getMessage());
}

$customers = [];
$products = [];
$admins = [];
$paid_orders = [];
$reviews = [];

try {
    // ดึงข้อมูลสินค้าสำหรับ Dashboard และ Reorder
    if ($section === 'dashboard' || $section === 'reorder') {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY order_index ASC, id DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($products) && !$search) {
            $error = 'ไม่พบข้อมูลสินค้าสำหรับจัดเรียง';
        }
    }
    if ($section === 'customers') {
    try {
        if ($search) {
            // Query ดึงลูกค้า + username ตาม search
            $stmt = $pdo->prepare("
                SELECT c.*, u.username
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.name LIKE :search OR c.email LIKE :search
                ORDER BY c.id
            ");
            $stmt->execute([':search' => "%$search%"]);
        } else {
            $stmt = $pdo->query("
                SELECT c.*, u.username
                FROM customers c
                JOIN users u ON c.user_id = u.id
                ORDER BY c.id
            ");
        }

        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // เตรียม query ดึง addresses
        $addressStmt = $pdo->prepare("
            SELECT *
            FROM addresses
            WHERE user_id = :user_id
            ORDER BY is_primary DESC, created_at DESC
        ");

        // loop ดึง addresses ให้แต่ละ customer
        foreach ($customers as &$customer) {
            $addressStmt->execute([':user_id' => $customer['user_id']]);
            $customer['addresses'] = $addressStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($customers) && !$search) {
            $error = 'ไม่พบข้อมูลลูกค้า';
        }

    } catch (PDOException $e) {
        $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้า: ' . htmlspecialchars($e->getMessage());
    }

    } elseif ($section === 'products') {
        $pdo->beginTransaction();
        try {
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :search ORDER BY order_index ASC, id DESC FOR UPDATE");
                $stmt->execute([':search' => "%$search%"]);
            } else {
                $stmt = $pdo->query("SELECT * FROM products ORDER BY order_index ASC, id DESC FOR UPDATE");
            }
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $pdo->commit();
            if (empty($products) && !$search) {
                $error = 'ไม่พบข้อมูลสินค้า';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า: ' . htmlspecialchars($e->getMessage());
        }
    } elseif ($section === 'paid_orders') {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT o.id AS order_id, o.created_at, pay.payment_method, pay.amount,
                   oi.product_id, oi.quantity, oi.price,
                   p.name AS product_name, p.product_image,
                   u.username
            FROM orders o
            JOIN payments pay ON pay.order_id = o.id
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            JOIN users u ON o.user_id = u.id
            WHERE u.username LIKE :search OR p.name LIKE :search
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $pdo->query("
            SELECT o.id AS order_id, o.created_at, pay.payment_method, pay.amount,
                   oi.product_id, oi.quantity, oi.price,
                   p.name AS product_name, p.product_image,
                   u.username
            FROM orders o
            JOIN payments pay ON pay.order_id = o.id
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ");
    }
    $paid_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

}elseif ($section === 'reviews') {
        if ($search) {
            $stmt = $pdo->prepare("SELECT r.*, p.name AS product_name, u.username 
                                 FROM reviews r 
                                 JOIN products p ON r.product_id = p.id 
                                 JOIN users u ON r.user_id = u.id 
                                 WHERE p.name LIKE :search OR u.username LIKE :search");
            $stmt->execute([':search' => "%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT r.*, p.name AS product_name, u.username 
                               FROM reviews r 
                               JOIN products p ON r.product_id = p.id 
                               JOIN users u ON r.user_id = u.id");
        }
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($reviews) && !$search) {
            $error = 'ไม่พบข้อมูลรีวิว';
        }
    }
    elseif ($section === 'admins') {
    try {
        if ($search) {
            $stmt = $pdo->prepare("
                SELECT id, username, email, admin_level, created_at
                FROM users
                WHERE (username LIKE :search OR email LIKE :search)
                AND admin_level IN ('super_admin', 'regular_admin')
                ORDER BY id ASC
            ");
            $stmt->execute([':search' => "%$search%"]);
        } else {
            $stmt = $pdo->query("
                SELECT id, username, email, admin_level, created_at
                FROM users
                WHERE admin_level IN ('super_admin', 'regular_admin')
                ORDER BY id ASC
            ");
        }
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($admins) && !$search) {
            $error = 'ไม่พบข้อมูลแอดมิน';
        }
    } catch (PDOException $e) {
        $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลแอดมิน: ' . htmlspecialchars($e->getMessage());
    }
}

    // ดึงข้อมูลคำสั่งซื้อ (purchase_data)
    
$stmt = $pdo->query("SELECT p.name, COUNT(DISTINCT oi.id) as purchase_count
                     FROM products p
                     LEFT JOIN order_items oi ON p.id = oi.product_id
                     LEFT JOIN orders o ON oi.order_id = o.id
                     LEFT JOIN payments pay ON pay.order_id = o.id
                     GROUP BY p.id");
$purchase_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรีวิว (review_data)
$stmt = $pdo->query("SELECT rating, COUNT(*) as count
                     FROM reviews
                     GROUP BY rating");
$review_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียม array สำหรับรีวิว
$review_counts = array_fill(1, 5, 0);
foreach ($review_data as $row) {
    $review_counts[(int)$row['rating']] = (int)$row['count'];
}

    // ดึงข้อมูลคำสั่งซื้อ (purchase_data)
$stmt = $pdo->query("SELECT p.name, COUNT(DISTINCT oi.id) as purchase_count
                     FROM products p
                     LEFT JOIN order_items oi ON p.id = oi.product_id
                     LEFT JOIN orders o ON oi.order_id = o.id
                     LEFT JOIN payments pay ON pay.order_id = o.id
                     GROUP BY p.id");
$purchase_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในฐานข้อมูล: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดแอดมิน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
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
                    <a href="admin_index.php?section=paid_orders&tab_id=<?php echo urlencode($tab_id); ?>" class="bg-[#F5DCE0] hover:bg-[#E18AAA] text-[#FB6F92] py-2 px-4 rounded-lg text-center block transition">💸 จ่ายเงินแล้ว</a>
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
            <?php if ($error): ?><p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <?php if ($success): ?><p class="text-green-500 mb-4"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
            <div class="content-section">
                <?php if ($section === 'dashboard'): ?>
                    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
                        <div class="bg-[#E18AAA] py-2 text-center text-white font-bold text-[18px] rounded-t shadow">
                            แดชบอร์ด
                        </div>
                        <div class="mt-2 ml-2">ยินดีต้อนรับสู่แดชบอร์ดแอดมิน</div>
                        <div class="mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ml-2 mr-2">
                                <div class="bg-white p-4 rounded shadow">
                                    <h4 class="text-lg font-semibold">จำนวนลูกค้า</h4>
                                    <p class="text-2xl"><?php echo $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(); ?></p>
                                </div>
                                <div class="bg-white p-4 rounded shadow">
                                    <h4 class="text-lg font-semibold">จำนวนสินค้า</h4>
                                    <p class="text-2xl"><?php echo $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); ?></p>
                                </div>
                                <div class="bg-white p-4 rounded shadow">
                                    <h4 class="text-lg font-semibold">คำสั่งซื้อ</h4>
                                    <p class="text-2xl"><?php echo $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn(); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 ml-2 mr-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white rounded-lg shadow-md p-6">
                                    <h2 class="text-xl font-semibold mb-4">จำนวนรีวิวต่อคะแนน</h2>
                                    <canvas id="reviewChart"></canvas>
                                </div>
                                <div class="bg-white rounded-lg shadow-md p-6">
                                    <h2 class="text-xl font-semibold mb-4">คำสั่งซื้อต่อสินค้า</h2>
                                    <canvas id="purchaseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 mt-6">
                        <div class="bg-[#E18AAA] py-2 text-center text-white font-bold text-[18px] shadow">
                            สินค้าที่เห็น
                        </div>
                        <div class="mt-2 p-4 grid grid-cols-1 grid-cols-2 grid-cols-3 h-100 gap-4">
                            <?php if (empty($products)): ?>
                                <p class="text-red-500">ไม่มีสินค้าให้แสดง</p>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <div class="bg-white p-4 rounded shadow hover:shadow-lg transition">
                                        <img src="<?php echo htmlspecialchars($p['product_image'] ?? '../assets/Uploads/default.png'); ?>" alt="<?php echo htmlspecialchars($p['name'] ?? 'N/A'); ?>" class="w-full h-64 object-cover rounded-t">
                                        <h4 class="text-lg font-semibold mt-2"><?php echo htmlspecialchars($p['name'] ?? 'N/A'); ?></h4>
                                        <p class="text-gray-600">ราคา: <?php echo number_format($p['price'] ?? 0, 2); ?> บาท</p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($section === 'customers'): ?>
                    <?php include 'search/search_customers.php'; ?>
                <?php elseif ($section === 'products'): ?>
                    <?php include 'search/search_products.php'; ?>
                <?php elseif ($section === 'reorder'): ?>
                    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
                        <div class="bg-[#E18AAA] py-2 text-center text-white font-bold text-[18px] rounded-t shadow">
                            จัดเรียงสินค้า
                        </div>
                        <div class="mt-2">
                            <?php if (empty($products)): ?>
                                <p class="text-red-500 mt-2 mb-4">ไม่มีสินค้าสำหรับจัดเรียง</p>
                            <?php else: ?>
                                <div class="ml-5">
                                    <ul 
                                        id="product-list" 
                                        class="w-[500px] mt-2">
                                    <?php foreach ($products as $p): ?>
                                        <li data-id="<?php echo $p['id']; ?>" class="bg-green-50 mt-2 p-2 border border-separate border-spacing-0 border-gray-300 rounded hover:shadow-lg  hover:bg-white cursor-move"><?php echo htmlspecialchars($p['name'] ?? 'N/A'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                </div>
                            <div class="ml-2 mb-4">
                                <button class="bg-[#92CA68] text-white py-2 px-4 rounded mt-4 hover:bg-[#76BC43]" onclick="saveOrder()">บันทึกลำดับ</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'admins' && $_SESSION['admin_level'] === 'super_admin'): ?>
                    <?php include 'search/search_admins.php'; ?>
                <?php elseif ($section === 'paid_orders'): ?>
                    <?php include 'search/search_paid_orders.php'; ?>
                <?php elseif ($section === 'reviews'): ?>
                    <?php include 'search/search_reviews.php'; ?>
                <?php else: ?>
                    <p class="text-red-500 mb-4">ส่วนนี้ไม่สามารถเข้าถึงได้</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    </div>
    <script>
        $(document).ready(function() {
            if ($("#product-list").length) {
                $("#product-list").sortable({
                    update: function(event, ui) {
                        console.log('Product order changed:', $("#product-list").sortable("toArray", { attribute: "data-id" }));
                    }
                }).disableSelection();
            } else {
                console.log('Product list not found');
            }
        });

        function saveOrder() {
            const order = $("#product-list").sortable("toArray", { attribute: "data-id" });
            if (order.length === 0) {
                alert('ไม่มีสินค้าสำหรับจัดเรียง');
                return;
            }
            fetch('save_order.php?tab_id=' + encodeURIComponent(new URLSearchParams(window.location.search).get('tab_id')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            }).then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            }).then(data => {
                alert(data.message || 'บันทึกลำดับสำเร็จ');
                if (data.status === 'success') {
                    location.reload();
                }
            }).catch(error => {
                console.error('Error saving order:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกลำดับ: ' + error.message);
            });
        }

// กราฟรีวิว
        const reviewCtx = document.getElementById('reviewChart').getContext('2d');
        new Chart(reviewCtx, {
            type: 'bar',
            data: {
                labels: ['1 ดาว', '2 ดาว', '3 ดาว', '4 ดาว', '5 ดาว'],
                datasets: [{
                    label: 'จำนวนรีวิว',
                    data: [<?php echo implode(',', array_values($review_counts)); ?>],
                    backgroundColor: '#FFA9A9',
                    borderColor: '#ff8c8c',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // กราฟคำสั่งซื้อ
        const purchaseCtx = document.getElementById('purchaseChart').getContext('2d');
        new Chart(purchaseCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($p) => "'" . addslashes($p['name']) . "'", $purchase_data)); ?>],
                datasets: [{
                    label: 'จำนวนคำสั่งซื้อ',
                    data: [<?php echo implode(',', array_column($purchase_data, 'purchase_count')); ?>],
                    backgroundColor: '#FFA9A9',
                    borderColor: '#ff8c8c',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        window.onload = () => {
            if (!new URLSearchParams(window.location.search).get('tab_id')) {
                window.location.search = 'tab_id=' + Math.random().toString(36).substr(2, 9);
            }
        };
    </script>
</body>
</html>