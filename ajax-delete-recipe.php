<?php
session_start();
require 'dp.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo "false";
    exit();
}

$userId = $_SESSION['user_id'];

if (!isset($_POST['id'])) {
    echo "false";
    exit();
}

$recipeId = $_POST['id'];

// تأكد أن الوصفة له
$stmt = $pdo->prepare("SELECT * FROM recipe WHERE id = ? AND userID = ?");
$stmt->execute([$recipeId, $userId]);
$recipe = $stmt->fetch();

if (!$recipe) {
    echo "false";
    exit();
}

// حذف الملفات
if (!empty($recipe['photoFileName'])) {
    $photo = "images/" . $recipe['photoFileName'];
    if (file_exists($photo)) unlink($photo);
}

if (!empty($recipe['videoFilePath'])) {
    $video = "videos/" . $recipe['videoFilePath'];
    if (file_exists($video)) unlink($video);
}

// حذف البيانات المرتبطة
$pdo->prepare("DELETE FROM comment WHERE recipeID=?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM likes WHERE recipeID=?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM favourites WHERE recipeID=?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM report WHERE recipeID=?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM ingredients WHERE recipeID=?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM instructions WHERE recipeID=?")->execute([$recipeId]);

// حذف الوصفة
$pdo->prepare("DELETE FROM recipe WHERE id=?")->execute([$recipeId]);

echo "true";
?>
