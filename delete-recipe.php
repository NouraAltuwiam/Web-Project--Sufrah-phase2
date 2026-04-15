<?php
// delete-recipe.php
// Requirement 7a: Deletes a recipe and all associated data and files, then redirects to my-recipes.php

session_start();
require 'dp.php';

// Requirement 5: Only logged-in regular users can delete recipes
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Validate the recipe id from the query string
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
    // Recipe not found or does not belong to this user
    header("Location: my-recipes.php");
    exit();
}

// Delete recipe photo file from server
if (!empty($recipe['photoFileName'])) {
    $paths = [
        "images/" . $recipe['photoFileName'],
        "uploads/recipes/" . $recipe['photoFileName']
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            unlink($p);
        }
    }
}

// Delete recipe video file from server
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "videos/" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }
}

// Requirement 7a: Delete all associated data (order matters for foreign keys)
$pdo->prepare("DELETE FROM comment      WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM likes        WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM favourites   WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM report       WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM ingredients  WHERE recipeID = ?")->execute([$recipeId]);
$pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$recipeId]);

// Finally delete the recipe itself
$pdo->prepare("DELETE FROM recipe WHERE id = ?")->execute([$recipeId]);

// Redirect back to my recipes page
header("Location: my-recipes.php");
exit();
?>