<?php
// unblock_user.php
// Removes a user from the blocked users table.

session_start();
require_once 'dp.php';

// Only admins can unblock users
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php?error=" . urlencode("غير مصرح لك"));
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$blockedID = (int) $_GET['id'];

// Remove the user from the blocked users table
$stmt = $pdo->prepare("DELETE FROM blockeduser WHERE id = ?");
$stmt->execute([$blockedID]);

header("Location: admin.php");
exit();
?>
