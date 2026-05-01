<?php
// handle_report.php
// Phase 3: Returns JSON true/false instead of redirecting (AJAX support)

session_start();
require_once 'dp.php';

header('Content-Type: application/json');

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(false);
    exit();
}

// Must be POST with all required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['action'], $_POST['reportID'], $_POST['recipeID'], $_POST['ownerID'])) {
    echo json_encode(false);
    exit();
}

$action   = $_POST['action'];
$reportID = (int) $_POST['reportID'];
$recipeID = (int) $_POST['recipeID'];
$ownerID  = (int) $_POST['ownerID'];

// Requirement: If action is block - delete all user data and add to blocked users table
if ($action === 'block') {

    $stmtUser = $pdo->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $stmtUser->execute([$ownerID]);
    $userData = $stmtUser->fetch();

    if ($userData) {

        $stmtRecipes = $pdo->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = ?");
        $stmtRecipes->execute([$ownerID]);
        $recipes = $stmtRecipes->fetchAll();

        foreach ($recipes as $rec) {
            $rID = (int) $rec['id'];

            if (!empty($rec['photoFileName'])) {
                $p = "images/" . $rec['photoFileName'];
                if (file_exists($p)) unlink($p);
            }

            if (!empty($rec['videoFilePath'])) {
                $v = "videos/" . $rec['videoFilePath'];
                if (file_exists($v)) unlink($v);
            }

            $pdo->prepare("DELETE FROM comment      WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM likes        WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM favourites   WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM report       WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM ingredients  WHERE recipeID = ?")->execute([$rID]);
            $pdo->prepare("DELETE FROM instructions WHERE recipeID = ?")->execute([$rID]);
        }

        $pdo->prepare("DELETE FROM recipe WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM comment    WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM likes      WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM favourites WHERE userID = ?")->execute([$ownerID]);
        $pdo->prepare("DELETE FROM report     WHERE userID = ?")->execute([$ownerID]);

        $pdo->prepare("INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)")
            ->execute([$userData['firstName'], $userData['lastName'], $userData['emailAddress']]);

        $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$ownerID]);
    }
}

// Always delete the report
try {
    $pdo->prepare("DELETE FROM report WHERE id = ?")->execute([$reportID]);
} catch (Exception $e) {
    // Report may already be deleted by cascade above
}

// Phase 3: Return true on success
echo json_encode(true);
exit();
?>
