<?php
session_start(['name' => 'CUSTOMER_SESSION']);
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['admin_level'], ['customer', 'super_admin', 'regular_admin'])) {
    error_log('Access denied: User not logged in or invalid admin_level (User ID: ' . ($_SESSION['user_id'] ?? 'none') . ')');
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    echo "<p class='alert alert-error'>❌ เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล</p>";
    exit;
}

// Fetch customer data
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    error_log('Invalid or missing customer ID');
    echo "<p class='alert alert-error'>❌ ไม่พบรหัสลูกค้า</p>";
    exit;
}

if ($_SESSION['admin_level'] === 'customer') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $customer = $stmt->fetch();
    if (!$customer) {
        error_log('Access denied: Customer does not have permission to edit this record (ID: ' . $id . ', User ID: ' . $_SESSION['user_id'] . ')');
        echo "<p class='alert alert-error'>❌ คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้</p>";
        exit;
    }
} else {
    $stmt = $pdo->prepare("SELECT c.*, u.admin_level FROM customers c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        error_log('Customer not found (ID: ' . $id . ')');
        echo "<p class='alert alert-error'>❌ ไม่พบข้อมูลลูกค้า</p>";
        exit;
    }
}

$is_admin = in_array($_SESSION['admin_level'], ['super_admin', 'regular_admin']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_admin) {
    $customer['name'] = trim($_POST['name'] ?? '');
    $customer['surname'] = trim($_POST['surname'] ?? '');
    $customer['company'] = trim($_POST['company'] ?? '');
    $customer['address'] = trim($_POST['address'] ?? '');
    $customer['state'] = trim($_POST['state'] ?? '');
    $customer['city'] = trim($_POST['city'] ?? '');
    $customer['province'] = trim($_POST['province'] ?? '');
    $customer['zipcode'] = trim($_POST['zipcode'] ?? '');
    $customer['telephone'] = trim($_POST['telephone'] ?? '');
    $customer['fax'] = trim($_POST['fax'] ?? '');
    $customer['mobile'] = trim($_POST['mobile'] ?? '');
    $customer['sex'] = trim($_POST['sex'] ?? '');
    $customer['more_detail'] = trim($_POST['more_detail'] ?? '');

    // Handle birth date
    $birth_day = filter_input(INPUT_POST, 'birth_day', FILTER_VALIDATE_INT);
    $birth_month = filter_input(INPUT_POST, 'birth_month', FILTER_VALIDATE_INT);
    $birth_year = filter_input(INPUT_POST, 'birth_year', FILTER_VALIDATE_INT);
    if ($birth_day && $birth_month && $birth_year) {
        $gregorian_year = $birth_year - 543;
        $birth = "$gregorian_year-$birth_month-$birth_day";
        if (strtotime($birth)) {
            $customer['birth'] = date('Y-m-d', strtotime($birth));
        } else {
            $customer['birth'] = null;
            $error = 'วันที่เกิดไม่ถูกต้อง';
        }
    } else {
        $customer['birth'] = null;
    }

    $customer['active'] = isset($_POST['active']) ? 1 : 0;

    // Handle image upload
    $profile_image = $customer['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['profile_image'];
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'customer_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $profile_image = $upload_dir . $filename;
            } else {
                $error = 'ไม่สามารถอัปโหลดรูปภาพได้';
            }
        } else {
            $error = 'รูปภาพไม่ถูกต้อง (ต้องเป็น JPEG, PNG, GIF และขนาดไม่เกิน 2MB)';
        }
    }

    // Validate required fields
    if ($customer['name'] === '' || $customer['surname'] === '') {
        $error = 'กรุณากรอกข้อมูลที่จำเป็น: ชื่อ, นามสกุล';
    } else {
        $fields = [];
        $values = ['id' => $id, 'profile_image' => $profile_image];
        foreach ($customer as $key => $value) {
            if ($key !== 'id' && $key !== 'email' && $key !== 'password' && $key !== 'user_id' && $key !== 'admin_level' && $key !== 'created_at') {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }
        }
        $sql = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = :id";
        try {
            $stmt = $pdo->prepare($sql);
            foreach ($values as $key => $value) {
                $type = ($key === 'id' || $key === 'active') ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(":$key", $value ?? null, $type);
            }
            $stmt->execute();
            $success = 'บันทึกข้อมูลสำเร็จ';
            $customer['profile_image'] = $profile_image;
        } catch (PDOException $e) {
            error_log('Failed to update customers table: ' . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . htmlspecialchars($e->getMessage());
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    $error = 'แอดมินไม่ได้รับอนุญาตให้แก้ไขข้อมูลลูกค้า';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin ? 'ดูข้อมูลลูกค้า' : 'โปรไฟล์ของฉัน'; ?></title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            <?php if (!$is_admin): ?>
            const name = document.querySelector('input[name="name"]').value.trim();
            const surname = document.querySelector('input[name="surname"]').value.trim();
            if (!name || !surname) {
                alert('กรุณากรอกข้อมูลที่จำเป็น: ชื่อ, นามสกุล');
                return false;
            }
            <?php endif; ?>
            return true;
        }
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="user-info">
            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username'] ?? 'ผู้ใช้'); ?>
            <?php echo $is_admin ? ' (' . ($_SESSION['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง') . ')' : ''; ?>
        </div>
        <a href="logout.php" class="button button-danger">🚪 ออกจากระบบ</a>
    </div>
    <div class="container">
        <h2><?php echo $is_admin ? 'ดูข้อมูลลูกค้า' : 'โปรไฟล์ของฉัน'; ?></h2>
        <?php if ($error): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (empty($customer)): ?>
            <p class="alert alert-error">❌ ไม่สามารถโหลดข้อมูลลูกค้าได้</p>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" class="form-container" onsubmit="return validateForm()">
                <?php if ($customer['profile_image']): ?>
                    <img src="<?php echo htmlspecialchars($customer['profile_image']); ?>" alt="Profile Image" class="profile-image" style="max-width: 100px; height: auto;">
                <?php else: ?>
                    <span>ไม่มีรูป</span>
                <?php endif; ?>
                <div class="form-group">
                    <label>รูปโปรไฟล์</label>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif" <?php echo $is_admin ? 'disabled' : ''; ?>>
                </div>
                <fieldset>
                    <legend>ข้อมูลบัญชี</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>อีเมล</label>
                            <input value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>ข้อมูลส่วนตัว</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ชื่อ *</label>
                            <input name="name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="form-group">
                            <label>นามสกุล *</label>
                            <input name="surname" value="<?php echo htmlspecialchars($customer['surname'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="form-group">
                            <label>บริษัท</label>
                            <input name="company" value="<?php echo htmlspecialchars($customer['company'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>ที่อยู่จัดส่ง</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ที่อยู่</label>
                            <input name="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>ตำบล/แขวง</label>
                            <input name="state" value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>อำเภอ/เขต</label>
                            <input name="city" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>จังหวัด</label>
                            <select name="province" <?php echo $is_admin ? 'disabled' : ''; ?>>
                                <option value="">-- เลือกจังหวัด --</option>
                                <?php
                                $provinces = ['กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น',
                                              'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่',
                                              'ตรัง', 'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช',
                                              'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์',
                                              'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก',
                                              'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร',
                                              'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย',
                                              'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร',
                                              'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย',
                                              'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชธานี'];
                                foreach ($provinces as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo ($customer['province'] ?? '') === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>รหัสไปรษณีย์</label>
                            <input name="zipcode" value="<?php echo htmlspecialchars($customer['zipcode'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>โทรศัพท์</label>
                            <input name="telephone" value="<?php echo htmlspecialchars($customer['telephone'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>แฟกซ์</label>
                            <input name="fax" value="<?php echo htmlspecialchars($customer['fax'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>มือถือ</label>
                            <input name="mobile" value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>" <?php echo $is_admin ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>เพศ</label>
                            <select name="sex" <?php echo $is_admin ? 'disabled' : ''; ?>>
                                <option value="">-- เลือกเพศ --</option>
                                <option value="M" <?php echo ($customer['sex'] ?? '') === 'M' ? 'selected' : ''; ?>>ชาย</option>
                                <option value="F" <?php echo ($customer['sex'] ?? '') === 'F' ? 'selected' : ''; ?>>หญิง</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>วันเกิด</label>
                            <?php
                            $birthDate = $customer['birth'] ?? '';
                            $day = $birthDate ? date('j', strtotime($birthDate)) : '';
                            $month = $birthDate ? date('n', strtotime($birthDate)) : '';
                            $year = $birthDate ? date('Y', strtotime($birthDate)) + 543 : '';
                            ?>
                            <select name="birth_day" <?php echo $is_admin ? 'disabled' : ''; ?>>
                                <option value="">วัน</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?php echo $d; ?>" <?php echo $d == $day ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="birth_month" <?php echo $is_admin ? 'disabled' : ''; ?>>
                                <option value="">เดือน</option>
                                <?php
                                $thai_months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                                                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                foreach ($thai_months as $i => $m): ?>
                                    <option value="<?php echo $i+1; ?>" <?php echo ($i+1) == $month ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="birth_year" <?php echo $is_admin ? 'disabled' : ''; ?>>
                                <option value="">ปี</option>
                                <?php $thisYear = date('Y') + 543;
                                for ($y = $thisYear - 100; $y <= $thisYear; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>สถานะ</label>
                            <input type="checkbox" name="active" <?php echo ($customer['active'] ?? 0) ? 'checked' : ''; ?> <?php echo $is_admin ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>รายละเอียดเพิ่มเติม</label>
                            <textarea name="more_detail" <?php echo $is_admin ? 'readonly' : ''; ?>><?php echo htmlspecialchars($customer['more_detail'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </fieldset>
                <div class="button-container">
                    <?php if (!$is_admin): ?>
                        <button type="submit" class="button button-primary">บันทึกข้อมูล</button>
                        <button type="reset" class="button button-secondary">ล้างข้อมูล</button>
                    <?php endif; ?>
                    <a href="<?php echo ($_SESSION['admin_level'] === 'customer' ? 'index.php' : 'admin_index.php'); ?>" class="button button-secondary">⬅️ ย้อนกลับ</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>