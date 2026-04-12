<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?msg=" . urlencode("You must login as a regular user"));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_GET['recipe_id']) || !is_numeric($_GET['recipe_id'])) {
    header("Location: user.php?msg=" . urlencode("Invalid recipe id"));
    exit();
}

$recipe_id = (int) $_GET['recipe_id'];


$sql_check = "SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);

if (!$stmt_check) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $recipe_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    $sql_delete = "DELETE FROM favourites WHERE userID = ? AND recipeID = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);

    if (!$stmt_delete) {
        die("Query preparation failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_delete, "ii", $user_id, $recipe_id);
    mysqli_stmt_execute($stmt_delete);
}

header("Location: user.php");
exit();
?>