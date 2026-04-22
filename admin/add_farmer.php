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

function redirect_with_message($status, $message)
{
    redirectWithFlash(appUrl('/farmers/index.php'), $status, $message, 'farmers');
}

function generate_unique_username($con, $baseUsername, $excludeId = 0)
{
    $baseUsername = strtolower(trim((string)$baseUsername));
    $baseUsername = preg_replace('/\s+/', '_', $baseUsername);
    $baseUsername = preg_replace('/[^a-z0-9_]/', '', (string)$baseUsername);
    $baseUsername = trim((string)$baseUsername, '_');
    if ($baseUsername === '') {
        $baseUsername = 'farmer000';
    }

    $username = $baseUsername;
    $suffix = 0;

    $checkStmt = mysqli_prepare($con, "SELECT id FROM farmers WHERE username = ? AND id <> ? LIMIT 1");
    if (!$checkStmt) {
        return $username;
    }

    while (true) {
        mysqli_stmt_bind_param($checkStmt, 'si', $username, $excludeId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) === 0) {
            break;
        }

        mysqli_stmt_free_result($checkStmt);
        $suffix = $suffix + 1;
        $username = $baseUsername . $suffix;
    }

    mysqli_stmt_close($checkStmt);
    return $username;
}

function build_username_base($name, $farmerNumber)
{
    $processedName = strtolower(trim((string)$name));
    $processedName = preg_replace('/\s+/', '_', $processedName);
    $processedName = preg_replace('/[^a-z0-9_]/', '', (string)$processedName);
    $processedName = trim((string)$processedName, '_');
    if ($processedName === '') {
        $processedName = 'farmer';
    }

    $digitsOnly = preg_replace('/\D+/', '', (string)$farmerNumber);
    if ($digitsOnly === '') {
        $digitsOnly = '000';
    }
    $lastThreeDigits = substr(str_pad($digitsOnly, 3, '0', STR_PAD_LEFT), -3);

    return $processedName . $lastThreeDigits;
}

if (!isAdmin()) {
    $_SESSION['errmsg'] = 'Unauthorized access.';
    header('Location: ' . appUrl('/admin/index.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('error', 'Invalid request method.');
}

if (!$con) {
    redirect_with_message('error', 'Database connection is not available.');
}

$name = clean_text(isset($_POST['name']) ? $_POST['name'] : '');
$location = clean_text(isset($_POST['location']) ? $_POST['location'] : '');
$phone = clean_text(isset($_POST['phone']) ? $_POST['phone'] : '');
$farmSizeInput = clean_text(isset($_POST['farm_size']) ? $_POST['farm_size'] : '');
$groupMembership = clean_text(isset($_POST['group_membership']) ? $_POST['group_membership'] : '');

if ($name === '' || $location === '' || $phone === '') {
    redirect_with_message('error', 'Name, location, and phone are required fields.');
}

if (strlen($name) > 100 || strlen($location) > 150 || strlen($phone) > 20 || strlen($groupMembership) > 100) {
    redirect_with_message('error', 'One or more fields exceed the allowed length.');
}

if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    redirect_with_message('error', 'Please enter a valid phone number.');
}

$farmSizeParam = '';
if ($farmSizeInput !== '') {
    if (!is_numeric($farmSizeInput)) {
        redirect_with_message('error', 'Farm size must be a valid number.');
    }

    $farmSize = (float)$farmSizeInput;
    if ($farmSize < 0 || $farmSize > 99999999.99) {
        redirect_with_message('error', 'Farm size is out of allowed range.');
    }

    $farmSizeParam = number_format($farmSize, 2, '.', '');
}

$temporaryUsername = 'tmp_' . uniqid('', true);
$temporaryUsername = str_replace('.', '', $temporaryUsername);

$stmt = mysqli_prepare($con, "INSERT INTO farmers (name, username, location, phone, farm_size, group_membership) VALUES (?, ?, ?, ?, NULLIF(?, ''), ?)");
if (!$stmt) {
    redirect_with_message('error', 'Unable to prepare farmer insert query.');
}

mysqli_stmt_bind_param($stmt, 'ssssss', $name, $temporaryUsername, $location, $phone, $farmSizeParam, $groupMembership);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    $newFarmerId = mysqli_insert_id($con);
    if ($newFarmerId <= 0) {
        redirect_with_message('error', 'Farmer saved, but unable to finalize username.');
    }

    $fetchStmt = mysqli_prepare($con, "SELECT farmer_number, name FROM farmers WHERE id = ? LIMIT 1");
    if (!$fetchStmt) {
        redirect_with_message('error', 'Farmer saved, but unable to fetch farmer number.');
    }

    mysqli_stmt_bind_param($fetchStmt, 'i', $newFarmerId);
    if (!mysqli_stmt_execute($fetchStmt)) {
        mysqli_stmt_close($fetchStmt);
        redirect_with_message('error', 'Farmer saved, but unable to fetch farmer number.');
    }

    $result = mysqli_stmt_get_result($fetchStmt);
    $newFarmer = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($fetchStmt);

    if (!$newFarmer || !isset($newFarmer['farmer_number'])) {
        redirect_with_message('error', 'Farmer saved, but farmer number was not found.');
    }

    $baseUsername = build_username_base($newFarmer['name'], $newFarmer['farmer_number']);
    $finalUsername = generate_unique_username($con, $baseUsername, (int)$newFarmerId);

    $updateStmt = mysqli_prepare($con, "UPDATE farmers SET username = ? WHERE id = ? LIMIT 1");
    if (!$updateStmt) {
        redirect_with_message('error', 'Farmer saved, but unable to update username.');
    }

    mysqli_stmt_bind_param($updateStmt, 'si', $finalUsername, $newFarmerId);
    if (!mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        redirect_with_message('error', 'Farmer saved, but username update failed.');
    }
    mysqli_stmt_close($updateStmt);

    redirect_with_message('success', 'Farmer added successfully.');
}

mysqli_stmt_close($stmt);
redirect_with_message('error', 'Failed to add farmer. Please try again.');
?>
