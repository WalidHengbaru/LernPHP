<?php
function getPDO() {
    $host = 'localhost';
    $dbname = 'ecommerce_db';
    $username = 'root';
    $password = '8513';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            admin_level ENUM('super_admin', 'regular_admin', 'customer') NOT NULL DEFAULT 'customer',
            profile_image VARCHAR(255),
            created_at DATETIME NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
            user_id INT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            telephone VARCHAR(20),
            active TINYINT(1) NOT NULL DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            telephone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            sku VARCHAR(50) UNIQUE,
            category VARCHAR(100),
            description TEXT,
            full_detail TEXT,
            price DECIMAL(10,2) NOT NULL,
            product_image VARCHAR(255),
            stock INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            order_index INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
            user_id INT,
            product_id INT,
            quantity INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            telephone VARCHAR(20) NOT NULL,
            address_id INT,
            payment_method ENUM('cod', 'qrcode', 'card') NOT NULL DEFAULT 'cod',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            review_image VARCHAR(255),
            created_at DATETIME NOT NULL,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
            user_id INT,
            product_id INT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS product_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT,
            viewed_at DATETIME NOT NULL,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        try {
            $pdo->exec("ALTER TABLE customers DROP COLUMN address");
        } catch (PDOException $e) {
            // Ignore if column doesn't exist
        }

        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . htmlspecialchars($e->getMessage()));
    }
}
?>