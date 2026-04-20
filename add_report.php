<?php
// add_report.php
// Requirement: Adds a report for a recipe and user in the database,
//              then redirects back to view-recipe.php for the same recipe.

session_start();
require 'dp.php';

// Requirement: Must be logged in as a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in to report recipes."));
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$recipeId = (int) ($_GET['recipe_id'] ?? 0);

if ($recipeId === 0) {
    header("Location: user.php");
    exit();
}

// Verify the recipe exists and the user is not the creator
$stmtCheck = $pdo->prepare("SELECT userID FROM recipe WHERE id = ?");
$stmtCheck->execute([$recipeId]);
$rec = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$rec || (int)$rec['userID'] === $userId) {
    header("Location: view-recipe.php?id={$recipeId}");
    exit();
}

// Requirement: Add report only if user has not already reported this recipe
$stmtExists = $pdo->prepare("SELECT 1 FROM report WHERE userID = ? AND recipeID = ?");
$stmtExists->execute([$userId, $recipeId]);
if (!$stmtExists->fetchColumn()) {
    $pdo->prepare("INSERT INTO report (userID, recipeID) VALUES (?, ?)")->execute([$userId, $recipeId]);
}

// Requirement: Redirect back to view-recipe page for this recipe
header("Location: view-recipe.php?id={$recipeId}");
exit();
?>
