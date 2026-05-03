<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/../includes/farmer-product-helpers.php';
requireAdmin(appUrl('/admin/index.php'));
ensureFarmerProductTables($con);

$pageTitle = "Today's Orders";
$from = date('Y-m-d') . ' 00:00:00';
$to = date('Y-m-d') . ' 23:59:59';
$where = "WHERE mo.order_date BETWEEN '" . mysqli_real_escape_string($con, $from) . "' AND '" . mysqli_real_escape_string($con, $to) . "'";
include __DIR__ . '/order-list-template.php';
?>
