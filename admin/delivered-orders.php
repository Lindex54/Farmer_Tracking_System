<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/../includes/farmer-product-helpers.php';
requireAdmin(appUrl('/admin/index.php'));
ensureFarmerProductTables($con);

$pageTitle = 'Delivered Orders';
$where = "WHERE mo.order_status = 'Delivered'";
include __DIR__ . '/order-list-template.php';
?>
