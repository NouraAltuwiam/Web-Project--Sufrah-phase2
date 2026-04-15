<?php
// signout.php
// Requirement 12: Wipe the session and redirect to the homepage

session_start();
session_unset();
session_destroy();

header("Location: index.php");
exit();
?>