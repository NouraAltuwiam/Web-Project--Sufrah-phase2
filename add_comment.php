<?php
// add_comment.php
// Requirement 10c: Adds a comment for a recipe and redirects back to view-recipe.php

session_start();
require 'dp.php';

// Requirement 5: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("You must be logged in to comment."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$recipeId = (int) ($_POST['recipe_id'] ?? 0);
$comment  = trim($_POST['comment'] ?? '');

if ($recipeId === 0 || $comment === '') {
    header("Location: view-recipe.php?id={$recipeId}");
    exit();
}

// Verify the recipe exists
$stmtCheck = $pdo->prepare("SELECT id FROM recipe WHERE id = ?");
$stmtCheck->execute([$recipeId]);
if (!$stmtCheck->fetch()) {
    header("Location: user.php");
    exit();
}

// Insert the comment with today's date
$stmt = $pdo->prepare("INSERT INTO comment (recipeID, userID, comment, date) VALUES (?, ?, ?, CURDATE())");
$stmt->execute([$recipeId, $userId, $comment]);

// Requirement 10c: Redirect back to view-recipe page for the same recipe
header("Location: view-recipe.php?id={$recipeId}");
exit();
?>