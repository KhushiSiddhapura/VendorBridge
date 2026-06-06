<?php

session_start();
$_SESSION = [];
session_destroy();

header('Location: /coupletodo/index.php');
exit();

?>