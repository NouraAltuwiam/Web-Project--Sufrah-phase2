<?php
// add_favourite.php
// Requirement: Receives AJAX POST request, adds recipe to favourites, returns true/false as JSON.

session_start();
require 'dp.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(false);
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$recipeId = isset($_POST['recipe_id']) && is_numeric($_POST['recipe_id'])
            ? (int) $_POST['recipe_id'] : 0;

if ($recipeId <= 0) {
    echo json_encode(false);
    exit();
}

// Check not already favourited
$stmt = $pdo->prepare("SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?");
$stmt->execute([$userId, $recipeId]);
if ($stmt->fetchColumn()) {
    echo json_encode(false);
    exit();
}

// Insert favourite
$ins = $pdo->prepare("INSERT INTO favourites (userID, recipeID) VALUES (?, ?)");
$result = $ins->execute([$userId, $recipeId]);

echo json_encode($result);
