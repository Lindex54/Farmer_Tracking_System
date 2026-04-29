<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/admin-auth.php');
requireAdminOrFarmer(appUrl('/farmers/login.php'));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'message' => 'Invalid request method.'));
    exit();
}

$batchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;
if ($batchId <= 0) {
    echo json_encode(array('ok' => false, 'message' => 'Invalid batch id.'));
    exit();
}

if (!$con) {
    echo json_encode(array('ok' => false, 'message' => 'Database unavailable.'));
    exit();
}

$isFarmerRequest = function_exists('isFarmer') && isFarmer();
$currentFarmerId = !empty($_SESSION['farmer_id']) ? (int)$_SESSION['farmer_id'] : 0;

if ($isFarmerRequest && $currentFarmerId <= 0) {
    echo json_encode(array('ok' => false, 'message' => 'Farmer account unavailable.'));
    exit();
}

$sql = "UPDATE batches
        SET status = 'complete'
        WHERE batch_id = ?
          AND status = 'drying'
          AND end_time IS NOT NULL
          AND end_time <= NOW()";

if ($isFarmerRequest) {
    $sql .= " AND farmer_id = ?";
}

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    echo json_encode(array('ok' => false, 'message' => 'Prepare failed.'));
    exit();
}

if ($isFarmerRequest) {
    mysqli_stmt_bind_param($stmt, 'ii', $batchId, $currentFarmerId);
} else {
    mysqli_stmt_bind_param($stmt, 'i', $batchId);
}
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(array('ok' => (bool)$ok));
exit();
?>
