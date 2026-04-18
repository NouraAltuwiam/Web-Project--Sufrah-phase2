<?php
// handle_report.php
// Handles admin report actions: block user or dismiss report
// Requirement 11c: Separate PHP page for processing report actions

session_start();
require_once 'dp.php';

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php?error=" . urlencode("غير مصرح لك"));
    exit();
}

// Must be POST with all required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['action'], $_POST['reportID'], $_POST['recipeID'], $_POST['ownerID'])) {
    header("Location: admin.php");
    exit();
}

$action   = $_POST['action'];
$reportID = (int) $_POST['reportID'];
$recipeID = (int) $_POST['recipeID'];
$ownerID  = (int) $_POST['ownerID'];

// Requirement 11c: If action is block, delete user data and add to blocked table
if ($action === 'block') {

    // Get user info before deleting
    $stmtUser = $pdo->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $stmtUser->execute([$ownerID]);
    $userData = $stmtUser->fetch();

    if ($userData) {

        // Get all recipes by this user to delete their files
        $stmtRecipes = $pdo->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = ?");
        $stmtRecipes->execute([$ownerID]);
        $recipes = $stmtRecipes->fetchAll();

        foreach ($recipes as $rec) {
            $rID = (int) $rec['id'];

            // Delete recipe photo file from server
            if (!empty($rec['photoFileName'])) {
                $photoPath = "images/" . $rec['photoFileName'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            // Delete recipe video file from server
            if (!empty($rec['videoFilePath'])) {
                $videoPath = "videos/" . $rec['videoFilePath'];
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            }

            // Delete all data related to this recipe (order matters due to foreign keys)
            $pdo->prepare("DELETE FROM comment      WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM likes        WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM favourites   WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM report       WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM ingredients  WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$rID]);
        }

        // Delete all recipes by this user
        $pdo->prepare("DELETE FROM recipe WHERE userID = ?")->execute([$ownerID]);
// Delete all recipes by this user
        $pdo->prepare("DELETE FROM recipe WHERE userID = ?")->execute([$ownerID]);

        // ← أضيفي هنا: حذف بيانات المستخدم على وصفات الآخرين
        $pdo->prepare("DELETE FROM comment    WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM likes      WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM favourites WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM report     WHERE userID = ?")->execute([$ownerID]);

        // Add user to the blocked users table
        $stmtBlock = $pdo->prepare("INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)");
        // Add user to the blocked users table
        $stmtBlock = $pdo->prepare("INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)");
        $stmtBlock->execute([$userData['firstName'], $userData['lastName'], $userData['emailAddress']]);

        // Delete user from the users table
        $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$ownerID]);
    }
}

// Always delete the report whether we blocked or dismissed
// (reportID may have already been deleted by the cascade above if action was block)
try {
    $pdo->prepare("DELETE FROM report WHERE id = ?")->execute([$reportID]);
} catch (Exception $e) {
    // Report was already deleted by cascade delete above
}

header("Location: admin.php");
exit();
?>