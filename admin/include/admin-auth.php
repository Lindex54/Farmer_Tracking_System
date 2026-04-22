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

function isFarmer()
{
    if (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'farmer') {
        return true;
    }

    return false;
}

function isAdminOrFarmer()
{
    return isAdmin() || isFarmer();
}

function getCurrentRoleLabel()
{
    if (isFarmer()) {
        return 'Farmer';
    }

    if (isAdmin()) {
        return 'Admin';
    }

    if (!empty($_SESSION['login'])) {
        return 'User';
    }

    return 'Guest';
}

function getCurrentDisplayName()
{
    if (isFarmer()) {
        if (!empty($_SESSION['farmer_name'])) {
            return (string)$_SESSION['farmer_name'];
        }

        if (!empty($_SESSION['farmer_username'])) {
            return (string)$_SESSION['farmer_username'];
        }

        return 'Farmer';
    }

    if (isAdmin()) {
        if (!empty($_SESSION['admin_name'])) {
            return (string)$_SESSION['admin_name'];
        }

        if (!empty($_SESSION['alogin'])) {
            return (string)$_SESSION['alogin'];
        }

        return 'Admin';
    }

    if (!empty($_SESSION['username'])) {
        return (string)$_SESSION['username'];
    }

    return 'Guest';
}

function requireAdmin($redirectTo = null)
{
    global $con;

    if (isAdmin()) {
        $adminIdentifier = '';
        if (!empty($_SESSION['admin_name'])) {
            $adminIdentifier = (string) $_SESSION['admin_name'];
        } elseif (!empty($_SESSION['alogin'])) {
            $adminIdentifier = (string) $_SESSION['alogin'];
        }

        if ($adminIdentifier !== '' && validateTrackedSession($con, 'admin', $adminIdentifier)) {
            touchTrackedSession($con);
            return;
        }
    }

    if ($redirectTo === null || $redirectTo === '') {
        $redirectTo = appUrl('/admin/index.php');
    }

    $adminIdentifier = !empty($_SESSION['alogin']) ? (string) $_SESSION['alogin'] : '';
    invalidateCurrentSessionWithAudit($con, 'admin', $adminIdentifier, 'Unauthorized or invalid administrator session.', $redirectTo);
}

function requireAdminOrFarmer($redirectTo = null)
{
    global $con;

    if (isAdmin()) {
        $adminIdentifier = !empty($_SESSION['admin_name']) ? (string) $_SESSION['admin_name'] : (string) $_SESSION['alogin'];
        if ($adminIdentifier !== '' && validateTrackedSession($con, 'admin', $adminIdentifier)) {
            touchTrackedSession($con);
            return;
        }
    }

    if (isFarmer()) {
        $farmerIdentifier = !empty($_SESSION['farmer_name']) ? (string) $_SESSION['farmer_name'] : (string) $_SESSION['farmer_username'];
        if ($farmerIdentifier !== '' && validateTrackedSession($con, 'farmer', $farmerIdentifier)) {
            touchTrackedSession($con);
            return;
        }
    }

    if ($redirectTo === null || $redirectTo === '') {
        $redirectTo = appUrl('/admin/index.php');
    }

    $actorType = isFarmer() ? 'farmer' : 'admin';
    $actorIdentifier = '';
    if ($actorType === 'farmer') {
        $actorIdentifier = !empty($_SESSION['farmer_name']) ? (string) $_SESSION['farmer_name'] : (string) $_SESSION['farmer_username'];
    } else {
        $actorIdentifier = !empty($_SESSION['admin_name']) ? (string) $_SESSION['admin_name'] : (string) $_SESSION['alogin'];
    }

    invalidateCurrentSessionWithAudit($con, $actorType, $actorIdentifier, 'Unauthorized or invalid protected session.', $redirectTo);
}
?>
