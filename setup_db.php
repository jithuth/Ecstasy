<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server (no DB selected)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pirnav_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'pirnav_clone' created or already exists.<br>";

    // Select Database
    $pdo->exec("USE pirnav_clone");

    // Create Tables
    // 1. Settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(255) UNIQUE NOT NULL,
        value TEXT
    )");

    // 2. Services
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Clients
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        logo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Carousel
    $pdo->exec("CREATE TABLE IF NOT EXISTS carousel (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Messages
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 6. Analytics Visitors (with Country)
    $pdo->exec("CREATE TABLE IF NOT EXISTS analytics_visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_hash VARCHAR(64) UNIQUE NOT NULL,
        ip_address VARCHAR(45),
        country VARCHAR(100) DEFAULT 'Unknown',
        device_type VARCHAR(50),
        os VARCHAR(50),
        browser VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Analytics Pageviews
    $pdo->exec("CREATE TABLE IF NOT EXISTS analytics_pageviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_id INT NOT NULL,
        page_url VARCHAR(255) NOT NULL,
        page_title VARCHAR(255),
        referrer VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (visitor_id) REFERENCES analytics_visitors(id) ON DELETE CASCADE
    )");

    // 8. Analytics Events
    $pdo->exec("CREATE TABLE IF NOT EXISTS analytics_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_id INT NOT NULL,
        event_category VARCHAR(50) NOT NULL,
        event_action VARCHAR(50) NOT NULL,
        event_label VARCHAR(255),
        event_value DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (visitor_id) REFERENCES analytics_visitors(id) ON DELETE CASCADE
    )");

    // 9. Users (Admin)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add Admin User if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['krish']);
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute(['krish', $password]);
        echo "Admin user 'krish' created.<br>";
    }

    echo "All tables created successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>