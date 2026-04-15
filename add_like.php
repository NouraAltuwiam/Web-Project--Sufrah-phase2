<?php
// add_like.php
// Requirement 10d: Adds a like for a recipe and redirects back to view-recipe.php

session_start();
require 'dp.php';

// Requirement 5: Must be logged in as a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in to like recipes."));
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$recipeId = (int) ($_GET['recipe_id'] ?? 0);

if ($recipeId === 0) {
    header("Location: user.php");
    exit();
}

// Verify the recipe exists and the viewer is not the creator (Req 10d)
$stmtCheck = $pdo->prepare("SELECT userID FROM recipe WHERE id = ?");
$stmtCheck->execute([$recipeId]);
$rec = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$rec || (int)$rec['userID'] === $userId) {
    header("Location: view-recipe.php?id={$recipeId}");
    exit();
}

// Only insert if the user has not already liked this recipe (Req 10d)
$stmtExists = $pdo->prepare("SELECT 1 FROM likes WHERE userID = ? AND recipeID = ?");
$stmtExists->execute([$userId, $recipeId]);
if (!$stmtExists->fetchColumn()) {
    $pdo->prepare("INSERT INTO likes (userID, recipeID) VALUES (?, ?)")->execute([$userId, $recipeId]);
}

// Redirect back to view-recipe page for the same recipe
header("Location: view-recipe.php?id={$recipeId}");
exit();
?>