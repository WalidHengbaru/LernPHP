<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $file = $_FILES['upload'];
    $upload_dir = 'C:/xampp/htdocs/LearnPHP/assets/Uploads/';
    $relative_path = '/LearnPHP/assets/Uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['error' => ['message' => 'ประเภทไฟล์ไม่ถูกต้อง']]);
        exit;
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['error' => ['message' => 'ไฟล์มีขนาดใหญ่เกินไป']]);
        exit;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'ckeditor_' . uniqid() . '.' . $extension;
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode(['url' => $relative_path . $filename]);
    } else {
        echo json_encode(['error' => ['message' => 'ไม่สามารถอัปโหลดไฟล์ได้']]);
    }
} else {
    echo json_encode(['error' => ['message' => 'คำขอไม่ถูกต้อง']]);
}
?>