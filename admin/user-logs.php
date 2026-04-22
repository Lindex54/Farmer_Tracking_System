
<?php
header('Location: ' . (function_exists('appUrl') ? appUrl('/admin/audit-logs.php') : 'audit-logs.php'));
exit();
