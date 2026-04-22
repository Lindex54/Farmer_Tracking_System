<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/include/dashboard-metrics.php';

requireAdmin(appUrl('/admin/index.php'));

header('Content-Type: application/json; charset=utf-8');
echo json_encode(getDashboardSnapshot($con));
