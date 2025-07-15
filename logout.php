<?php
// ตรวจสอบ session name ก่อนเริ่ม session
$sessionName = null;
if (isset($_COOKIE['CUSTOMER_SESSION'])) {
    $sessionName = 'CUSTOMER_SESSION';
} elseif (isset($_COOKIE['ADMIN_SESSION'])) {
    $sessionName = 'ADMIN_SESSION';
}

// ถ้าเจอ session name ที่ถูกต้อง ให้เริ่ม session นั้น
if ($sessionName) {
    session_name($sessionName);
    session_start();

    // ลบข้อมูล session ทั้งหมด
    $_SESSION = [];
    session_unset();
    session_destroy();

    // ลบ cookie session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie($sessionName, '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // redirect ไปหน้า login ตาม session ที่ logout
    if ($sessionName === 'ADMIN_SESSION') {
        header("Location: admin_login.php");
    } else {
        header("Location: login.php");
    }
    exit;
} else {
    // กรณีไม่มี session cookie ใดๆ ก็ redirect ไป login ปกติ
    header("Location: login.php");
    exit;
}