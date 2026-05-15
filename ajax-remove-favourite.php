<?php
session_start();
require_once 'dp.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(false);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$recipe_id = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;

$stmt = $pdo->prepare("DELETE FROM favourites WHERE userID = ? AND recipeID = ?");
$stmt->execute([$user_id, $recipe_id]);

echo json_encode($stmt->rowCount() > 0);
?>
