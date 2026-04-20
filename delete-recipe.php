<?php
// delete-recipe.php
// Requirement: Deletes a recipe and all its associated data and files,
//              then redirects to my-recipes.php.

session_start();
require 'dp.php';

// Requirement: Only logged-in regular users can delete recipes
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Requirement: Check the recipe ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my-recipes.php");
    exit();
}

$recipeId = (int) $_GET['id'];

// Verify the recipe belongs to this user before deleting
$stmt = $pdo->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$stmt->execute([$recipeId, $userId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: my-recipes.php");
    exit();
}

// Requirement: Delete recipe photo file from server
if (!empty($recipe['photoFileName'])) {
    $photoPath = "images/" . $recipe['photoFileName'];
    if (file_exists($photoPath)) unlink($photoPath);
}

// Requirement: Delete recipe video file from server
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "videos/" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) unlink($videoPath);
}

// Requirement: Delete all associated data - ingredients, instructions, comments, likes, favourites, reports
$pdo->prepare("DELETE FROM comment      WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM likes        WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM favourites   WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM report       WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM ingredients  WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$recipeId]);

// Requirement: Delete the recipe itself from the database
$pdo->prepare("DELETE FROM recipe WHERE id = ?")->execute([$recipeId]);

// Requirement: Redirect to my-recipes page
header("Location: my-recipes.php");
exit();
?>
