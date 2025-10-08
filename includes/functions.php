<?php
require_once 'db.php'; // Use db.php for getPDO()

function checkAuth($type, $allowed_levels = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_level'])) {
        header("Location: /LearnPHP/" . ($type === 'admin' ? 'admin/admin_login.php' : 'customer/login.php') . "?tab_id=" . urlencode($_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime())));
        exit;
    }
    if (!empty($allowed_levels) && !in_array($_SESSION['admin_level'], $allowed_levels)) {
        header("Location: /LearnPHP/admin/admin_index.php?tab_id=" . urlencode($_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime())));
        exit;
    }
}

function getDefaultImage() {
    $default_image_path = 'C:/xampp/htdocs/LearnPHP/assets/Uploads/Default.png';
    $relative_path = '/LearnPHP/assets/Uploads/Default.png';
    if (file_exists($default_image_path)) {
        return $relative_path;
    }
    return 'https://via.placeholder.com/150x150?text=No+Image';
}

function getDefaultText($value) {
    return $value ?: 'N/A';
}

function uploadImage($file, $type) {
    $upload_dir = 'C:/xampp/htdocs/LearnPHP/assets/Uploads/';
    $relative_path = '/LearnPHP/assets/Uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return getDefaultImage();
    }

    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }

    if ($file['size'] > $max_size) {
        return false;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . uniqid() . '.' . $extension;
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $relative_path . $filename;
    }

    return false;
}

function getPrimaryAddress($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = :user_id AND is_primary = 1 LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function migrateCustomerAddress($pdo, $user_id, $user_profile) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    if ($stmt->fetchColumn() == 0 && isset($user_profile['address'])) {
        $stmt = $pdo->prepare("INSERT INTO addresses (user_id, name, surname, telephone, address, is_primary, created_at) 
                               VALUES (:user_id, :name, :surname, :telephone, :address, 1, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $user_profile['name'] ?? 'N/A',
            ':surname' => $user_profile['surname'] ?? 'N/A',
            ':telephone' => $user_profile['telephone'] ?? 'N/A',
            ':address' => $user_profile['address'] ?? 'N/A'
        ]);
    }
}
?>