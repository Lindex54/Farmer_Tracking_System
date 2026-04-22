<?php
if (!defined('APP_NAME')) {
define('APP_NAME', 'FarmHub');
}
require_once dirname(__DIR__, 2) . '/includes/session-security.php';
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');
define('DB_NAME', 'shopping');
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);
// Check connection
if (mysqli_connect_errno())
{
 echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
?>
