<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE analytics_visitors ADD COLUMN country VARCHAR(100) DEFAULT 'Unknown'");
    echo "Successfully added 'country' column to analytics_visitors table.";
} catch (PDOException $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>