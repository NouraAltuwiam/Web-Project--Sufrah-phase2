<?php
// delete_recipe.php
session_start();
require 'dp.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'user') {
    echo json_encode(false);
    exit();
}

$viewerId = (int) $_SESSION['user_id'];

if (!isset($_POST['recipe_id']) || !is_numeric($_POST['recipe_id'])) {
    echo json_encode(false);
    exit();
}

$recipeId = (int) $_POST['recipe_id'];

// تأكد إن الوصفة تبع المستخدم الحالي
$stmtOwner = $pdo->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$stmtOwner->execute([$recipeId, $viewerId]);
$recipe = $stmtOwner->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    echo json_encode(false);
    exit();
}

try {
    $pdo->beginTransaction();

    // حذف كل البيانات المرتبطة بالوصفة
    $tables = ['ingredients', 'instructions', 'likes', 'favourites', 'report', 'comment'];
    foreach ($tables as $table) {
        $pdo->prepare("DELETE FROM `$table` WHERE recipeID = ?")->execute([$recipeId]);
    }

    // حذف الوصفة نفسها
    $pdo->prepare("DELETE FROM recipe WHERE id = ?")->execute([$recipeId]);

    $pdo->commit();

    // حذف الملفات من السيرفر
    if (!empty($recipe['photoFileName'])) {
        $photoPath = __DIR__ . '/images/' . $recipe['photoFileName'];
        if (file_exists($photoPath)) unlink($photoPath);
    }

    if (!empty($recipe['videoFilePath'])) {
        $videoPath = __DIR__ . '/videos/' . $recipe['videoFilePath'];
        if (file_exists($videoPath)) unlink($videoPath);
    }

    echo json_encode(true);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(false);
}
