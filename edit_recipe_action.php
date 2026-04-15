<?php
// edit_recipe_action.php
// Requirement 9b: Processes the edit-recipe form, updates DB and files, then redirects to my-recipes.php

session_start();
require 'dp.php';

// Requirement 5: Only logged-in regular users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my-recipes.php");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$recipeId = (int) ($_POST['recipeID'] ?? 0);

if ($recipeId === 0) {
    header("Location: my-recipes.php");
    exit();
}

// Verify the recipe belongs to this user
$stmtCheck = $pdo->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$stmtCheck->execute([$recipeId, $userId]);
$existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    header("Location: my-recipes.php");
    exit();
}

$name        = trim($_POST['name']        ?? '');
$categoryID  = (int) ($_POST['categoryID'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($name === '' || $categoryID === 0 || $description === '') {
    header("Location: edit-recipe.php?id={$recipeId}&error=" . urlencode("Please fill in all required fields."));
    exit();
}

// Requirement 9b: Handle photo - replace if new one uploaded, otherwise keep existing
$photoFileName = $existing['photoFileName'];
if (isset($_FILES['recipePhoto']) && $_FILES['recipePhoto']['error'] === 0) {
    $photoExt      = strtolower(pathinfo($_FILES['recipePhoto']['name'], PATHINFO_EXTENSION));
    $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($photoExt, $allowedImages)) {
        header("Location: edit-recipe.php?id={$recipeId}&error=" . urlencode("Unsupported image type."));
        exit();
    }
    $photoUploadDir = "images/";
    if (!is_dir($photoUploadDir)) {
        mkdir($photoUploadDir, 0777, true);
    }
    // Delete old photo file if it was previously uploaded
    if (!empty($existing['photoFileName'])) {
        $oldPath = "images/" . $existing['photoFileName'];
        if (file_exists($oldPath)) unlink($oldPath);
        $oldPath2 = $photoUploadDir . $existing['photoFileName'];
        if (file_exists($oldPath2)) unlink($oldPath2);
    }
    // Incorporate user and recipe IDs in filename to keep it unique (as required)
    $photoFileName = "recipe_u{$userId}_r{$recipeId}_" . time() . "." . $photoExt;
    move_uploaded_file($_FILES['recipePhoto']['tmp_name'], $photoUploadDir . $photoFileName);
}

// Requirement 9b: Handle video - replace if new one uploaded, otherwise keep existing
$videoFilePath = $existing['videoFilePath'];
if (isset($_FILES['recipeVideo']) && $_FILES['recipeVideo']['error'] === 0) {
    $videoExt      = strtolower(pathinfo($_FILES['recipeVideo']['name'], PATHINFO_EXTENSION));
    $allowedVideos = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    if (in_array($videoExt, $allowedVideos)) {
        $videoUploadDir = "videos/";
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0777, true);
        }
        // Delete old video file
        if (!empty($existing['videoFilePath'])) {
            $oldVideo = $videoUploadDir . $existing['videoFilePath'];
            if (file_exists($oldVideo)) unlink($oldVideo);
        }
        $videoFilePath = "video_u{$userId}_r{$recipeId}_" . time() . "." . $videoExt;
        move_uploaded_file($_FILES['recipeVideo']['tmp_name'], $videoUploadDir . $videoFilePath);
    }
}

// Update the recipe row in the database
$stmtUpdate = $pdo->prepare("
    UPDATE recipe
    SET categoryID = ?, name = ?, description = ?, photoFileName = ?, videoFilePath = ?
    WHERE id = ? AND userID = ?
");
$stmtUpdate->execute([$categoryID, $name, $description, $photoFileName, $videoFilePath, $recipeId, $userId]);

// Replace ingredients: delete old ones and insert the updated set
$pdo->prepare("DELETE FROM ingredients WHERE recipeID = ?")->execute([$recipeId]);
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

// Replace instructions: delete old ones and insert the updated set
$pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$recipeId]);
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