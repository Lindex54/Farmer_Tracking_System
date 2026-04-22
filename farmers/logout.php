<?php
session_start();
include('../admin/include/config.php');
require_once __DIR__ . '/../admin/include/audit.php';

$farmerIdentifier = '';
if (!empty($_SESSION['farmer_name'])) {
    $farmerIdentifier = (string)$_SESSION['farmer_name'];
} elseif (!empty($_SESSION['farmer_username'])) {
    $farmerIdentifier = (string)$_SESSION['farmer_username'];
}

if ($farmerIdentifier !== '') {
    writeAuditLog($con, 'farmer', $farmerIdentifier, 'logout', 'success', 'Farmer signed out.');
}
closeTrackedSession($con);

unset(
    $_SESSION['role'],
    $_SESSION['farmer_id'],
    $_SESSION['farmer_username'],
    $_SESSION['farmer_name']
);

session_write_close();

redirectWithFlash(appUrl('/farmers/login.php'), 'success', 'You have successfully logged out.', 'farmer_login');
