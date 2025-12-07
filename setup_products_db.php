<?php
require_once 'includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS flagship_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'flagship_products' created successfully.";

    // Pre-seed data if table is empty
    $check = $pdo->query("SELECT COUNT(*) FROM flagship_products")->fetchColumn();
    if ($check == 0) {
        $products = [
            ['Ecstasy AI Core', 'Unified AI automation engine for businesses', ''],
            ['Ecstasy DeepTech', 'Drone, space & semiconductor intelligence platform', ''],
            ['Ecstasy PlayTech', 'AI gaming & creator ecosystem engine', ''],
            ['Ecstasy FinCore', 'Smart fintech, compliance & risk automation suite', ''],
            ['Ecstasy SmartX', 'Smart consumer, IoT & climate-tech innovation hub', ''],
            ['Ecstasy Advanced Labs', 'Engineering the Future, Today', '']
        ];

        $stmt = $pdo->prepare("INSERT INTO flagship_products (title, description, image_path) VALUES (?, ?, ?)");
        foreach ($products as $prod) {
            $stmt->execute($prod);
        }
        echo "<br>Initial products seeded.";
    }

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>