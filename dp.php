<?php
$host = 'sql208.infinityfree.com';
$db   = 'if0_41938939_sufrah';
$user = 'if0_41938939';
$pass = 'كلمة مرور الـ vPanel';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
