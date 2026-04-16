<?php
session_start();
error_reporting(0);
include('include/config.php');

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

$stmt = mysqli_prepare(
    $con,
    "UPDATE batches
     SET status = 'complete'
     WHERE batch_id = ?
       AND status = 'drying'
       AND end_time IS NOT NULL
       AND end_time <= NOW()"
);

if (!$stmt) {
    echo json_encode(array('ok' => false, 'message' => 'Prepare failed.'));
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $batchId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(array('ok' => (bool)$ok));
exit();
?>
