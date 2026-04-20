<?php
// remove-favourite.php
// Requirement: Deletes a recipe from the user's favourites in the database,
//              then redirects to user.php.

session_start();
require_once 'dp.php';

// Requirement: Only logged-in regular users can remove favourites
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_GET['recipe_id']) || !is_numeric($_GET['recipe_id'])) {
    header("Location: user.php");
    exit();
}

$recipe_id = (int) $_GET['recipe_id'];

// Requirement: Delete the corresponding recipe from the user's favourites
$stmt = $pdo->prepare("DELETE FROM favourites WHERE userID = ? AND recipeID = ?");
$stmt->execute([$user_id, $recipe_id]);

// Requirement: Redirect back to user page
header("Location: user.php");
exit();
?>
