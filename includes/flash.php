<?php
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($status, $message, $scope = 'global')
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = array();
        }

        $_SESSION['flash_messages'][$scope] = array(
            'status' => trim((string) $status),
            'message' => trim((string) $message),
        );

        return true;
    }
}

if (!function_exists('pullFlashMessage')) {
    function pullFlashMessage($scope = 'global')
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        if (
            !isset($_SESSION['flash_messages']) ||
            !is_array($_SESSION['flash_messages']) ||
            !isset($_SESSION['flash_messages'][$scope])
        ) {
            return null;
        }

        $flash = $_SESSION['flash_messages'][$scope];
        unset($_SESSION['flash_messages'][$scope]);

        if (empty($_SESSION['flash_messages'])) {
            unset($_SESSION['flash_messages']);
        }

        return $flash;
    }
}

if (!function_exists('redirectWithFlash')) {
    function redirectWithFlash($url, $status, $message, $scope = 'global')
    {
        setFlashMessage($status, $message, $scope);
        header('Location: ' . $url);
        exit();
    }
}
?>
