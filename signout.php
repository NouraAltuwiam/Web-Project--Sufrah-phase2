<?php
// signout.php
// Requirement: Wipe out all session variables and redirect to homepage.

session_start();
session_unset();
session_destroy();

header("Location: index.php");
exit();
?>
