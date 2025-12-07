<?php
require_once 'includes/db.php';

$images = [
    'office_01.jpg',
    'office_02.jpg',
    'office_03.jpg',
    'office_04.jpg',
    'office_05.jpg'
];

try {
    $stmt = $pdo->prepare("INSERT INTO why_work_images (image_path, title, description) VALUES (?, ?, ?)");

    foreach ($images as $img) {
        // Check if already exists to avoid duplicates
        $check = $pdo->prepare("SELECT id FROM why_work_images WHERE image_path = ?");
        $check->execute([$img]);
        if ($check->rowCount() == 0) {
            $stmt->execute([$img, 'Office Space', 'A glimpse into our working environment.']);
            echo "Inserted $img\n";
        } else {
            echo "Skipped $img (already exists)\n";
        }
    }
    echo "Done.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>