<?php
session_start(['name' => 'ADMIN_SESSION']);
require 'config.php';

// Check if user is logged in and is super_admin or regular_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['admin_level'], ['super_admin', 'regular_admin'])) {
    header("Location: admin_login.php");
    exit;
}

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

$error = '';
$customer = [
    'user_id' => null,
    'name' => '', 'surname' => '', 'email' => '', 'password' => '',
    'company' => '', 'phone' => '', 'address' => '', 'state' => '',
    'city' => '', 'province' => '', 'zipcode' => '', 'telephone' => '',
    'fax' => '', 'mobile' => '', 'sex' => '', 'birth' => '',
    'active' => 0, 'more_detail' => '', 'profile_image' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อัปเดตค่าจากฟอร์ม
    foreach ($customer as $field => &$value) {
        $value = trim($_POST[$field] ?? '');
    }

    // Combine birth date components
    $birth_day = $_POST['birth_day'] ?? '';
    $birth_month = $_POST['birth_month'] ?? '';
    $birth_year = $_POST['birth_year'] ?? '';
    if ($birth_day && $birth_month && $birth_year) {
        $gregorian_year = $birth_year - 543;
        $birth = "$gregorian_year-$birth_month-$birth_day";
        $customer['birth'] = date('Y-m-d', strtotime($birth));
    } else {
        $customer['birth'] = null;
    }

    $customer['active'] = isset($_POST['active']) ? 1 : 0;

    // Validate required fields
    if ($customer['name'] === '' || $customer['surname'] === '' || $customer['email'] === '' || $customer['password'] === '') {
        $error = 'กรุณากรอกข้อมูลที่จำเป็น: ชื่อ, นามสกุล, อีเมล, รหัสผ่าน';
    } elseif (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif ($customer['password'] !== trim($_POST['password_confirm'] ?? '')) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        // ตรวจสอบ email และ username ซ้ำ
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? OR username = ?");
        $username = preg_replace('/[^a-zA-Z0-9]/', '_', 'customer_' . time());
        $stmt->execute([$customer['email'], $username]);
        if ($stmt->fetch()) {
            $error = 'อีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $pdo->beginTransaction();
            try {
                // สร้างผู้ใช้ใหม่ในตาราง users
                $hashed_password = password_hash($customer['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, admin_level, created_at) VALUES (?, ?, ?, 'customer', NOW())");
                $stmt->execute([$username, $customer['email'], $hashed_password]);
                $customer['user_id'] = $pdo->lastInsertId();

                // เพิ่มข้อมูลลูกค้าในตาราง customers
                $sql = "INSERT INTO customers (
                    user_id, name, surname, email, password, company, phone, address, state,
                    city, province, zipcode, telephone, fax, mobile, sex, birth,
                    active, more_detail, created_at
                ) VALUES (
                    :user_id, :name, :surname, :email, :password, :company, :phone, :address, :state,
                    :city, :province, :zipcode, :telephone, :fax, :mobile, :sex, :birth,
                    :active, :more_detail, NOW()
                )";
                $stmt = $pdo->prepare($sql);
                foreach ($customer as $key => $value) {
                    $type = ($key === 'user_id' || $key === 'active') ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindValue(":$key", $value ?? null, $type);
                }
                $stmt->execute();
                $pdo->commit();
                header("Location: admin_index.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มลูกค้า</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            const name = document.querySelector('input[name="name"]').value.trim();
            const surname = document.querySelector('input[name="surname"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value.trim();
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value.trim();
            if (!name || !surname || !email || !password || !passwordConfirm) {
                alert('กรุณากรอกข้อมูลที่จำเป็น: ชื่อ, นามสกุล, อีเมล, รหัสผ่าน');
                return false;
            }
            if (password !== passwordConfirm) {
                alert('รหัสผ่านไม่ตรงกัน');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="left-buttons">
            <span>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?>)</span>
            <a href="logout.php"><button class="button-logout">🚪 ออกจากระบบ</button></a>
        </div>
    </div>
    <h2 style="text-align:center;">เพิ่มลูกค้า</h2>

    <?php if ($error): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="form-container" onsubmit="return validateForm()">
        <fieldset>
            <legend>Account Information</legend>
            <table>
                <tr><td>อีเมล *</td><td><input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required></td></tr>
                <tr><td>รหัสผ่าน *</td><td><input type="password" name="password" required></td></tr>
                <tr><td>ยืนยันรหัสผ่าน *</td><td><input type="password" name="password_confirm" required></td></tr>
            </table>
        </fieldset>

        <fieldset>
            <legend>Profile Information</legend>
            <table>
                <tr><td>ชื่อ *</td><td><input name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required></td></tr>
                <tr><td>นามสกุล *</td><td><input name="surname" value="<?php echo htmlspecialchars($customer['surname']); ?>" required></td></tr>
                <tr><td>บริษัท</td><td><input name="company" value="<?php echo htmlspecialchars($customer['company']); ?>"></td></tr>
            </table>
        </fieldset>

        <fieldset>
            <legend>Shipping Address</legend>
            <table>
                <tr><td>ที่อยู่</td><td><input name="address" value="<?php echo htmlspecialchars($customer['address']); ?>"></td></tr>
                <tr><td>ตำบล/แขวง</td><td><input name="state" value="<?php echo htmlspecialchars($customer['state']); ?>"></td></tr>
                <tr><td>อำเภอ/เขต</td><td><input name="city" value="<?php echo htmlspecialchars($customer['city']); ?>"></td></tr>
                <tr><td>จังหวัด</td><td>
                    <select name="province">
                        <option value="">-- เลือกจังหวัด --</option>
                        <?php
                        $provinces = ['กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร','ขอนแก่น',
                                      'จันทบุรี','ฉะเชิงเทรา','ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่',
                                      'ตรัง', 'ตราด', 'ตาก','นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 
                                      'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน','บึงกาฬ', 'บุรีรัมย์','ปทุมธานี', 'ประจวบคีรีขันธ์', 
                                      'ปราจีนบุรี', 'ปัตตานี','พระนครศรีอยุธยา', 'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 
                                      'เพชรบุรี', 'เพชรบูรณ์', 'แพร่','ภูเก็ต','มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร', 
                                      'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี','ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย',
                                      'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 
                                      'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 
                                      'หนองบัวลำภู','อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชธานี'];
                        foreach ($provinces as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo $customer['province'] === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td></tr>
                <tr><td>รหัสไปรษณีย์</td><td><input name="zipcode" value="<?php echo htmlspecialchars($customer['zipcode']); ?>"></td></tr>
                <tr><td>โทรศัพท์</td><td><input name="telephone" value="<?php echo htmlspecialchars($customer['telephone']); ?>"></td></tr>
                <tr><td>แฟกซ์</td><td><input name="fax" value="<?php echo htmlspecialchars($customer['fax']); ?>"></td></tr>
                <tr><td>มือถือ</td><td><input name="mobile" value="<?php echo htmlspecialchars($customer['mobile']); ?>"></td></tr>
                <tr>
                    <td>เพศ / Sex:</td>
                    <td>
                        <select name="sex">
                            <option value="">-- เลือกเพศ --</option>
                            <option value="M" <?php echo $customer['sex'] === 'M' ? 'selected' : ''; ?>>ชาย</option>
                            <option value="F" <?php echo $customer['sex'] === 'F' ? 'selected' : ''; ?>>หญิง</option>
                        </select>
                    </td>
                </tr>
                <tr><td>วันเกิด</td><td>
                    <?php
                    $birthDate = $customer['birth'] ?: '';
                    $day = $birthDate ? date('j', strtotime($birthDate)) : '';
                    $month = $birthDate ? date('n', strtotime($birthDate)) : '';
                    $year = $birthDate ? date('Y', strtotime($birthDate)) + 543 : '';
                    ?>
                    <select name="birth_day">
                        <option value="">วัน</option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo $d; ?>" <?php echo $d == $day ? 'selected' : ''; ?>><?php echo $d; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="birth_month">
                        <option value="">เดือน</option>
                        <?php
                        $thai_months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
                                        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                        foreach ($thai_months as $i => $m): ?>
                            <option value="<?php echo $i+1; ?>" <?php echo ($i+1) == $month ? 'selected' : ''; ?>><?php echo $m; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="birth_year">
                        <option value="">ปี</option>
                        <?php $thisYear = date('Y') + 543;
                        for ($y = $thisYear - 100; $y <= $thisYear; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </td></tr>
                <tr><td>Active</td><td><input type="checkbox" name="active" <?php echo $customer['active'] ? 'checked' : ''; ?>></td></tr>
                <tr><td>รายละเอียดเพิ่มเติม</td><td><textarea name="more_detail"><?php echo htmlspecialchars($customer['more_detail']); ?></textarea></td></tr>
                <tr>
                    <td>รูปภาพโปรไฟล์</td>
                    <td>
                        <?php if (!empty($customer['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($customer['profile_image']); ?>" alt="Profile Image" style="max-width: 100px; height: auto;">
                        <?php else: ?>
                            <span>ไม่มีรูป</span>
                        <?php endif; ?>
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif">
                    </td>
                </tr>
            </table>
        </fieldset>

        <div class="button-container">
            <button type="submit" class="button-save">บันทึก数据</button>
            <button type="reset" class="button-reset">ล้างข้อมูล</button>
            <a href="admin_index.php" class="button-back-form">⬅️ ย้อนกลับ</a>
        </div>
    </form>
</body>
</html>