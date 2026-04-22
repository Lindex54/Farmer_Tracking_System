<?php
session_start();
include("includes/config.php");
require_once __DIR__ . '/admin/include/audit.php';

$userIdentifier = '';
if (!empty($_SESSION['username'])) {
    $userIdentifier = (string)$_SESSION['username'];
} elseif (!empty($_SESSION['login'])) {
    $userIdentifier = (string)$_SESSION['login'];
}

if ($userIdentifier !== '') {
    writeAuditLog($con, 'user', $userIdentifier, 'logout', 'success', 'Customer signed out.');
}
closeTrackedSession($con);

$_SESSION['login']=="";
date_default_timezone_set('Asia/Kolkata');
$ldate=date( 'd-m-Y h:i:s A', time () );
$logoutEmail = isset($_SESSION['login']) ? (string) $_SESSION['login'] : '';
$stmt = mysqli_prepare($con,"UPDATE userlog SET logout = ? WHERE userEmail = ? ORDER BY id DESC LIMIT 1");
if ($stmt) {
mysqli_stmt_bind_param($stmt, 'ss', $ldate, $logoutEmail);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}
session_unset();
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
document.location="index.php";
</script>
