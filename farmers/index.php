<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');

if (function_exists('isFarmer') && isFarmer()) {
    header('Location: ' . appUrl('/farmers/overview.php'));
    exit();
}

header('Location: ' . appUrl('/farmers/login.php'));
exit();
?>
