<?php
// add_comment.php
// Requirement: Adds a new comment for a recipe in the database,
//              then redirects back to view-recipe.php for the same recipe.

session_start();
require 'dp.php';

// Requirement: Must be logged in to comment
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

// Requirement: Insert the new comment into the database
$stmt = $pdo->prepare("INSERT INTO comment (recipeID, userID, comment, date) VALUES (?, ?, ?, CURDATE())");
$stmt->execute([$recipeId, $userId, $comment]);

// Requirement: Redirect back to view-recipe page for this recipe
header("Location: view-recipe.php?id={$recipeId}");
exit();
?>
