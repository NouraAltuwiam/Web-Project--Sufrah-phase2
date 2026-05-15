<?php
session_start();
require_once 'dp.php';

header('Content-Type: application/json; charset=utf-8');

$category_id = $_GET['category_id'] ?? '';

$sql = "SELECT r.id, r.name, r.photoFileName,
               u.firstName, u.lastName, u.photoFileName AS userPhoto,
               c.categoryName, COUNT(l.recipeID) AS totalLikes
        FROM recipe r
        LEFT JOIN user u ON r.userID = u.id
        LEFT JOIN recipecategory c ON r.categoryID = c.id
        LEFT JOIN likes l ON r.id = l.recipeID";

$params = [];

if ($category_id !== '') {
    $sql .= " WHERE r.categoryID = ?";
    $params[] = (int)$category_id;
}

$sql .= " GROUP BY r.id, r.name, r.photoFileName,
                 u.firstName, u.lastName, u.photoFileName, c.categoryName
          ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll());
?>
