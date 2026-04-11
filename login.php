<?php
session_start();

// هذا مؤقت للتجربة فقط - يسجل دخول كأدمن مباشرة
$_SESSION['userID'] = 1;
$_SESSION['userType'] = 'admin';

header("Location: admin.php");
exit();
?>