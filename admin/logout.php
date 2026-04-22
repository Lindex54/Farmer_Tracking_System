<?php
session_start();
include("include/config.php");
require_once __DIR__ . '/include/audit.php';

$adminIdentifier = '';
if (!empty($_SESSION['admin_name'])) {
    $adminIdentifier = (string)$_SESSION['admin_name'];
} elseif (!empty($_SESSION['alogin'])) {
    $adminIdentifier = (string)$_SESSION['alogin'];
}

if ($adminIdentifier !== '') {
    writeAuditLog($con, 'admin', $adminIdentifier, 'logout', 'success', 'Administrator signed out.');
}
closeTrackedSession($con);

$_SESSION['alogin']=="";
session_unset();
//session_destroy();
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
document.location="index.php";
</script>
