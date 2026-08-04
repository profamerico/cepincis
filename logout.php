<?php
require_once 'controllers/AuthController.php';
header('Content-Type: text/html; charset=UTF-8');


$auth = new AuthController();
$auth->logout();
?>