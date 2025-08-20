<?php
require_once 'backend/auth/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php?message=logged_out');
exit();
?>
