<?php
require 'config.php';

/* 1) Connect to MySQL without selecting DB */
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_errno) {
    exit("Connect failed: " . $mysqli->connect_error);
}

/* 2) Create database if it doesn't exist */
$mysqli->query(
    "CREATE DATABASE IF NOT EXISTS " . DB_NAME . "
     CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
) or exit($mysqli->error);

/* 3) Select the database */
$mysqli->select_db(DB_NAME);

/* 4) Create users table for login credentials (if it doesn't exist) */
$mysqli->query("
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_image VARCHAR(255) DEFAULT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    admin_level ENUM('super_admin', 'regular_admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
") or exit($mysqli->error);

/* 5) Create customers table (if it doesn't exist) */
$mysqli->query("
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_image VARCHAR(255) DEFAULT NULL,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    company VARCHAR(100),
    phone VARCHAR(30),
    address TEXT,
    state VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    zipcode VARCHAR(20),
    telephone VARCHAR(30),
    fax VARCHAR(30),
    mobile VARCHAR(30),
    sex CHAR(1),
    birth DATE,
    active TINYINT(1) DEFAULT 0,
    more_detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
") or exit($mysqli->error);

/* 6) Create products table (if it doesn't exist) */
$mysqli->query("
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
") or exit($mysqli->error);

/* 7) Create favorites table (if it doesn't exist) */
$mysqli->query("
CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
") or exit($mysqli->error);

/* 8) Add product_image column to existing products table if it doesn't exist */
$result = $mysqli->query("SHOW COLUMNS FROM products LIKE 'product_image'");
if ($result->num_rows == 0) {
    $mysqli->query("ALTER TABLE products ADD COLUMN product_image VARCHAR(255) DEFAULT NULL AFTER price") or exit($mysqli->error);
}

/* 9) Insert a default super admin user if not exists */
$default_username = 'admin';
$default_email = 'admin@example.com';
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$result = $mysqli->query("SELECT 1 FROM users WHERE username = '$default_username' OR email = '$default_email'");
if ($result->num_rows == 0) {
    $mysqli->query("
    INSERT INTO users (username, email, password, admin_level, created_at)
    VALUES ('$default_username', '$default_email', '$default_password', 'super_admin', NOW())
    ") or exit($mysqli->error);

    /* 10) Insert a default customer record for super admin */
    $admin_user_id = $mysqli->query("SELECT id FROM users WHERE username = '$default_username'")->fetch_assoc()['id'];
    $mysqli->query("
    INSERT INTO customers (user_id, name, surname, email, created_at)
    VALUES ($admin_user_id, 'Admin', 'User', '$default_email', NOW())
    ") or exit($mysqli->error);
}

echo "Database & tables ready with super_admin created ✅";
?>