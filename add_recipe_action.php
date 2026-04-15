<?php
// add_recipe_action.php
// Requirement 8b: Processes the add-recipe form, inserts into DB, then redirects to my-recipes.php

session_start();
require 'dp.php';

// Requirement 5: Only logged-in regular users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add-recipe.php");
    exit();
}

$userId      = (int) $_SESSION['user_id'];
$name        = trim($_POST['name']        ?? '');
$categoryID  = (int) ($_POST['categoryID'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($name === '' || $categoryID === 0 || $description === '') {
    header("Location: add-recipe.php?error=" . urlencode("Please fill in all required fields."));
    exit();
}

// Handle recipe photo upload (required)
if (!isset($_FILES['recipePhoto']) || $_FILES['recipePhoto']['error'] !== 0) {
    header("Location: add-recipe.php?error=" . urlencode("Please upload a recipe photo."));
    exit();
}

$photoUploadDir = "images/";
if (!is_dir($photoUploadDir)) {
    mkdir($photoUploadDir, 0777, true);
}
$photoExt      = strtolower(pathinfo($_FILES['recipePhoto']['name'], PATHINFO_EXTENSION));
$allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($photoExt, $allowedImages)) {
    header("Location: add-recipe.php?error=" . urlencode("Unsupported image type."));
    exit();
}
// Incorporate user ID in filename to make it unique (as required)
$photoFileName = "recipe_u{$userId}_" . time() . "_" . uniqid() . "." . $photoExt;
move_uploaded_file($_FILES['recipePhoto']['tmp_name'], $photoUploadDir . $photoFileName);

// Handle optional video upload
$videoFilePath = null;
if (isset($_FILES['recipeVideo']) && $_FILES['recipeVideo']['error'] === 0) {
    $videoUploadDir = "videos/";
    if (!is_dir($videoUploadDir)) {
        mkdir($videoUploadDir, 0777, true);
    }
    $videoExt      = strtolower(pathinfo($_FILES['recipeVideo']['name'], PATHINFO_EXTENSION));
    $allowedVideos = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    if (in_array($videoExt, $allowedVideos)) {
        // Incorporate user ID in filename to make it unique (as required)
        $videoFilePath = "video_u{$userId}_" . time() . "_" . uniqid() . "." . $videoExt;
        move_uploaded_file($_FILES['recipeVideo']['tmp_name'], $videoUploadDir . $videoFilePath);
    }
}

// Insert the recipe into the database
$stmtRecipe = $pdo->prepare("
    INSERT INTO recipe (userID, categoryID, name, description, photoFileName, videoFilePath)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmtRecipe->execute([$userId, $categoryID, $name, $description, $photoFileName, $videoFilePath]);
$recipeId = (int) $pdo->lastInsertId();

// Insert ingredients
$ingNames = $_POST['ing_name'] ?? [];
$ingQtys  = $_POST['ing_qty']  ?? [];
$stmtIng  = $pdo->prepare("INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)");
for ($i = 0; $i < count($ingNames); $i++) {
    $iName = trim($ingNames[$i] ?? '');
    $iQty  = trim($ingQtys[$i]  ?? '');
    if ($iName !== '') {
        $stmtIng->execute([$recipeId, $iName, $iQty]);
    }
}

// Insert instructions with stepOrder
$steps    = $_POST['step'] ?? [];
$stmtStep = $pdo->prepare("INSERT INTO instructions (recipeID, step, stepOrder) VALUES (?, ?, ?)");
foreach ($steps as $order => $stepText) {
    $stepText = trim($stepText);
    if ($stepText !== '') {
        $stmtStep->execute([$recipeId, $stepText, $order + 1]);
    }
}

// Redirect to my recipes page on success
header("Location: my-recipes.php");
exit();
?>