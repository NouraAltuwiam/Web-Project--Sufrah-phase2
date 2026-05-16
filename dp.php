<?php
$host = 'sql303.infinityfree.com';
$db   = 'if0_41939924_sufrah';
$user = 'if0_41939924';
$pass = 'XADHs6Pes4tAiv';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
