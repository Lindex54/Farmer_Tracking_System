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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = clean_text(isset($_POST['name']) ? $_POST['name'] : '');
$location = clean_text(isset($_POST['location']) ? $_POST['location'] : '');
$phone = clean_text(isset($_POST['phone']) ? $_POST['phone'] : '');
$farmSizeInput = clean_text(isset($_POST['farm_size']) ? $_POST['farm_size'] : '');
$groupMembership = clean_text(isset($_POST['group_membership']) ? $_POST['group_membership'] : '');

if ($id <= 0) {
    redirect_with_message('error', 'Invalid farmer selected.');
}

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

$stmt = mysqli_prepare($con, "UPDATE farmers SET name = ?, location = ?, phone = ?, farm_size = NULLIF(?, ''), group_membership = ? WHERE id = ?");
if (!$stmt) {
    redirect_with_message('error', 'Unable to prepare farmer update query.');
}

mysqli_stmt_bind_param($stmt, 'sssssi', $name, $location, $phone, $farmSizeParam, $groupMembership, $id);

if (mysqli_stmt_execute($stmt)) {
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if ($affectedRows >= 0) {
        redirect_with_message('success', 'Farmer information updated successfully.');
    }
}

mysqli_stmt_close($stmt);
redirect_with_message('error', 'Failed to update farmer information. Please try again.');
?>
