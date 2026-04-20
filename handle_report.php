<?php
// handle_report.php
// Requirement: Handles admin report actions.
//              If action is block: delete all user's recipes and associated data, add user to blocked table, delete from users table.
//              Always deletes the report. Redirects to admin page.

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

// Requirement: If action is block - delete all user data and add to blocked users table
if ($action === 'block') {

    // Get user info before deleting
    $stmtUser = $pdo->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $stmtUser->execute([$ownerID]);
    $userData = $stmtUser->fetch();

    if ($userData) {

        // Get all recipes by this user so we can delete their files
        $stmtRecipes = $pdo->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = ?");
        $stmtRecipes->execute([$ownerID]);
        $recipes = $stmtRecipes->fetchAll();

        foreach ($recipes as $rec) {
            $rID = (int) $rec['id'];

            // Delete recipe photo file
            if (!empty($rec['photoFileName'])) {
                $p = "images/" . $rec['photoFileName'];
                if (file_exists($p)) unlink($p);
            }

            // Delete recipe video file
            if (!empty($rec['videoFilePath'])) {
                $v = "videos/" . $rec['videoFilePath'];
                if (file_exists($v)) unlink($v);
            }

            // Requirement: Delete all data associated with this recipe
            $pdo->prepare("DELETE FROM comment      WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM likes        WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM favourites   WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM report       WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM ingredients  WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$rID]);
        }

        // Requirement: Delete all of the user's recipes
        $pdo->prepare("DELETE FROM recipe WHERE userID = ?")->execute([$ownerID]);

        // Delete remaining user activity records
        $pdo->prepare("DELETE FROM comment    WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM likes      WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM favourites WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM report     WHERE userID = ?")->execute([$ownerID]);

        // Requirement: Add user to the blocked users table
        $pdo->prepare("INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)")
            ->execute([$userData['firstName'], $userData['lastName'], $userData['emailAddress']]);

        // Requirement: Delete the user from the users table
        $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$ownerID]);
    }
}

// Requirement: Always delete the report (whether action was block or dismiss)
try {
    $pdo->prepare("DELETE FROM report WHERE id = ?")->execute([$reportID]);
} catch (Exception $e) {
    // Report may already be deleted by cascade above - safe to ignore
}

// Requirement: Redirect to admin page
header("Location: admin.php");
exit();
?>
