<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAppBasePath()
{
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
    $basePath = preg_replace('#/(admin|farmers)(/.*)?$#', '', $scriptName);

    if (!is_string($basePath)) {
        return '';
    }

    $basePath = rtrim($basePath, '/');
    return $basePath === '/' ? '' : $basePath;
}

function appUrl($path)
{
    $path = (string)$path;
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }

    return getAppBasePath() . $path;
}

function isAdmin()
{
    if (!empty($_SESSION['alogin'])) {
        return true;
    }

    if (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin') {
        return true;
    }

    return false;
}

function requireAdmin($redirectTo = null)
{
    if (isAdmin()) {
        return;
    }

    if ($redirectTo === null || $redirectTo === '') {
        $redirectTo = appUrl('/admin/index.php');
    }

    $_SESSION['errmsg'] = 'Unauthorized access.';
    header('Location: ' . $redirectTo);
    exit();
}
?>
