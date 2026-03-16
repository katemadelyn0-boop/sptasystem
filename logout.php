<?php
require_once 'config/db.php';
require_once 'config/auth.php';
if (isset($_SESSION['user_id'])) logAudit('LOGOUT', 'users', $_SESSION['user_id']);
session_destroy();
header('Location: /spta-system/login.php');
exit;
