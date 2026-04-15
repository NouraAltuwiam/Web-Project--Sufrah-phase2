<?php
// remove-favourite.php
// Requirement 6f: Deletes a recipe from the user's favourites and redirects to user.php

session_start();
require_once 'dp.php';

// Requirement 5: Check that the user is logged in as a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Validate the recipe_id from query string
if (!isset($_GET['recipe_id']) || !is_numeric($_GET['recipe_id'])) {
    header("Location: user.php");
    exit();
}

$recipe_id = (int) $_GET['recipe_id'];

// Delete from favourites only if it belongs to this user
$stmt = $pdo->prepare("DELETE FROM favourites WHERE userID = ? AND recipeID = ?");
$stmt->execute([$user_id, $recipe_id]);

// Redirect back to user page
header("Location: user.php");
exit();
?>