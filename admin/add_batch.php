<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/admin-auth.php');

function clean_text($value)
{
    $value = trim((string)$value);
    $value = strip_tags($value);
    return $value;
}

function redirect_to_add_batch($status, $message)
{
    $safeStatus = urlencode($status);
    $safeMessage = urlencode($message);
    header('Location: ' . appUrl('/farmers/add_batch.php') . '?status=' . $safeStatus . '&message=' . $safeMessage);
    exit();
}

function redirect_to_farmers_index($status, $message)
{
    $safeStatus = urlencode($status);
    $safeMessage = urlencode($message);
    header('Location: ' . appUrl('/farmers/index.php') . '?status=' . $safeStatus . '&message=' . $safeMessage);
    exit();
}

function redirect_to_batches_page($status, $message, $newBatchId = 0)
{
    $safeStatus = urlencode($status);
    $safeMessage = urlencode($message);
    $url = appUrl('/farmers/batches.php') . '?status=' . $safeStatus . '&message=' . $safeMessage;
    if ((int)$newBatchId > 0) {
        $url .= '&new_batch_id=' . (int)$newBatchId;
    }
    header('Location: ' . $url);
    exit();
}

function is_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to_add_batch('error', 'Invalid request method.');
}

if (!$con) {
    redirect_to_add_batch('error', 'Database connection is not available.');
}

$farmerId = isset($_POST['farmer_id']) ? (int)$_POST['farmer_id'] : 0;
$harvestDate = clean_text(isset($_POST['harvest_date']) ? $_POST['harvest_date'] : '');
$quantityInput = clean_text(isset($_POST['quantity_kg']) ? $_POST['quantity_kg'] : '');
$initialMoistureInput = clean_text(isset($_POST['initial_moisture']) ? $_POST['initial_moisture'] : '');
$remainingInput = clean_text(isset($_POST['remaining_qty_kg']) ? $_POST['remaining_qty_kg'] : '');
$dryingMethod = clean_text(isset($_POST['drying_method']) ? $_POST['drying_method'] : '');
$sortingScoreInput = clean_text(isset($_POST['sorting_quality_score']) ? $_POST['sorting_quality_score'] : '');

if ($farmerId <= 0 || $harvestDate === '' || $quantityInput === '') {
    redirect_to_add_batch('error', 'Farmer, harvest date, and quantity are required.');
}

if (!is_valid_date($harvestDate)) {
    redirect_to_add_batch('error', 'Harvest date is invalid.');
}

if (!is_numeric($quantityInput)) {
    redirect_to_add_batch('error', 'Quantity must be a valid number.');
}

$quantityKg = (float)$quantityInput;
if ($quantityKg <= 0) {
    redirect_to_add_batch('error', 'Quantity must be greater than zero.');
}

$remainingQtyKg = $quantityKg;
if ($remainingInput !== '') {
    if (!is_numeric($remainingInput)) {
        redirect_to_add_batch('error', 'Remaining quantity must be a valid number.');
    }

    $remainingQtyKg = (float)$remainingInput;
    if ($remainingQtyKg < 0) {
        redirect_to_add_batch('error', 'Remaining quantity cannot be negative.');
    }
}

$initialMoistureParam = '';
if ($initialMoistureInput !== '') {
    if (!is_numeric($initialMoistureInput)) {
        redirect_to_add_batch('error', 'Initial moisture must be a valid number.');
    }
    $initialMoisture = (float)$initialMoistureInput;
    if ($initialMoisture < 0 || $initialMoisture > 100) {
        redirect_to_add_batch('error', 'Initial moisture must be between 0 and 100.');
    }
    $initialMoistureParam = number_format($initialMoisture, 2, '.', '');
}

$sortingScoreParam = '';
if ($sortingScoreInput !== '') {
    if (!is_numeric($sortingScoreInput)) {
        redirect_to_add_batch('error', 'Sorting quality score must be a valid number.');
    }
    $sortingScore = (float)$sortingScoreInput;
    if ($sortingScore < 0 || $sortingScore > 100) {
        redirect_to_add_batch('error', 'Sorting quality score must be between 0 and 100.');
    }
    $sortingScoreParam = number_format($sortingScore, 2, '.', '');
}

if (strlen($dryingMethod) > 100) {
    redirect_to_add_batch('error', 'Drying method is too long.');
}

$farmerCheckStmt = mysqli_prepare($con, "SELECT id FROM farmers WHERE id = ? LIMIT 1");
if (!$farmerCheckStmt) {
    redirect_to_add_batch('error', 'Unable to validate farmer.');
}
mysqli_stmt_bind_param($farmerCheckStmt, 'i', $farmerId);
mysqli_stmt_execute($farmerCheckStmt);
$farmerResult = mysqli_stmt_get_result($farmerCheckStmt);
$farmerExists = $farmerResult ? mysqli_fetch_assoc($farmerResult) : null;
mysqli_stmt_close($farmerCheckStmt);

if (!$farmerExists) {
    redirect_to_add_batch('error', 'Selected farmer does not exist.');
}

$quantityParam = number_format($quantityKg, 2, '.', '');
$remainingParam = number_format($remainingQtyKg, 2, '.', '');
$dryingMethodParam = $dryingMethod;

$stmt = mysqli_prepare(
    $con,
    "INSERT INTO batches (farmer_id, harvest_date, quantity_kg, initial_moisture, remaining_qty_kg, drying_method, sorting_quality_score, created_at)
     VALUES (?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''), NOW())"
);

if (!$stmt) {
    redirect_to_add_batch('error', 'Unable to prepare batch insert query.');
}

mysqli_stmt_bind_param(
    $stmt,
    'isssdss',
    $farmerId,
    $harvestDate,
    $quantityParam,
    $initialMoistureParam,
    $remainingParam,
    $dryingMethodParam,
    $sortingScoreParam
);

if (mysqli_stmt_execute($stmt)) {
    $newBatchId = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);
    redirect_to_batches_page('success', 'Batch information added successfully.', $newBatchId);
}

mysqli_stmt_close($stmt);
redirect_to_add_batch('error', 'Failed to save batch information. Please try again.');
?>
