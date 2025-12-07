<?php
require_once 'includes/db.php';

$updates = [
    'Ecstasy AI Core' => 'ecstasy_ai_core.png',
    'Ecstasy DeepTech' => 'ecstasy_deeptech.png',
    'Ecstasy PlayTech' => 'ecstasy_playtech.png',
    'Ecstasy FinCore' => 'ecstasy_fincore.png',
    'Ecstasy SmartX' => 'ecstasy_smartx.png',
    'Ecstasy Advanced Labs' => 'ecstasy_advanced_labs.png'
];

try {
    $stmt = $pdo->prepare("UPDATE flagship_products SET image_path = ? WHERE title = ?");

    foreach ($updates as $title => $image) {
        if ($stmt->execute([$image, $title])) {
            echo "Updated image for $title<br>";
        } else {
            echo "Failed to update $title<br>";
        }
    }
    echo "Done.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>