<?php
if (!function_exists('userAccountControlColumnExists')) {
    function userAccountControlColumnExists($con, $columnName)
    {
        if (!$con) {
            return false;
        }

        $columnName = trim((string) $columnName);
        if ($columnName === '') {
            return false;
        }

        $safeColumn = mysqli_real_escape_string($con, $columnName);
        $result = mysqli_query($con, "SHOW COLUMNS FROM users LIKE '" . $safeColumn . "'");
        if (!$result) {
            return false;
        }

        $exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);

        return $exists;
    }
}

if (!function_exists('ensureUserAccountControlColumns')) {
    function ensureUserAccountControlColumns($con)
    {
        if (!$con) {
            return false;
        }

        $queries = array();
        if (!userAccountControlColumnExists($con, 'account_status')) {
            $queries[] = "ALTER TABLE users ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER password";
        }
        if (!userAccountControlColumnExists($con, 'suspended_until')) {
            $queries[] = "ALTER TABLE users ADD COLUMN suspended_until DATETIME NULL DEFAULT NULL AFTER account_status";
        }
        if (!userAccountControlColumnExists($con, 'deleted_at')) {
            $queries[] = "ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER suspended_until";
        }

        foreach ($queries as $sql) {
            if (!mysqli_query($con, $sql)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('fetchUserById')) {
    function fetchUserById($con, $userId)
    {
        if (!$con || !ensureUserAccountControlColumns($con)) {
            return null;
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return null;
        }

        $result = mysqli_query($con, "SELECT * FROM users WHERE id = " . $userId . " LIMIT 1");
        if (!$result) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return $row ?: null;
    }
}

if (!function_exists('fetchUserByEmail')) {
    function fetchUserByEmail($con, $email)
    {
        if (!$con || !ensureUserAccountControlColumns($con)) {
            return null;
        }

        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        $safeEmail = mysqli_real_escape_string($con, $email);
        $result = mysqli_query($con, "SELECT * FROM users WHERE email = '" . $safeEmail . "' ORDER BY id DESC LIMIT 1");
        if (!$result) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return $row ?: null;
    }
}

if (!function_exists('normalizeUserAccountStatus')) {
    function normalizeUserAccountStatus($con, array $user)
    {
        if (!$con || !ensureUserAccountControlColumns($con) || empty($user['id'])) {
            return $user;
        }

        $status = isset($user['account_status']) ? strtolower((string) $user['account_status']) : 'active';
        $suspendedUntil = isset($user['suspended_until']) ? trim((string) $user['suspended_until']) : '';

        if ($status === 'suspended' && $suspendedUntil !== '') {
            $expiry = strtotime($suspendedUntil);
            if ($expiry !== false && $expiry <= time()) {
                $userId = (int) $user['id'];
                mysqli_query(
                    $con,
                    "UPDATE users SET account_status = 'active', suspended_until = NULL WHERE id = " . $userId . " AND account_status = 'suspended'"
                );
                $user = fetchUserById($con, $userId);
            }
        }

        return $user;
    }
}

if (!function_exists('getUserAccessState')) {
    function getUserAccessState($con, array $user)
    {
        $user = normalizeUserAccountStatus($con, $user);
        $status = isset($user['account_status']) ? strtolower((string) $user['account_status']) : 'active';
        $suspendedUntil = isset($user['suspended_until']) ? trim((string) $user['suspended_until']) : '';
        $deletedAt = isset($user['deleted_at']) ? trim((string) $user['deleted_at']) : '';

        if ($status === 'deleted' || $deletedAt !== '') {
            return array(
                'allowed' => false,
                'status' => 'deleted',
                'message' => 'This account has been deleted and can no longer be used.',
                'user' => $user,
            );
        }

        if ($status === 'suspended') {
            $message = 'This account is currently suspended.';
            if ($suspendedUntil !== '') {
                $message = 'This account is suspended until ' . date('M d, Y h:i A', strtotime($suspendedUntil)) . '.';
            }

            return array(
                'allowed' => false,
                'status' => 'suspended',
                'message' => $message,
                'user' => $user,
            );
        }

        return array(
            'allowed' => true,
            'status' => 'active',
            'message' => '',
            'user' => $user,
        );
    }
}

if (!function_exists('closeTrackedSessionsForUser')) {
    function closeTrackedSessionsForUser($con, array $user)
    {
        if (!$con || empty($user) || !function_exists('ensureTrackedSessionTable')) {
            return false;
        }

        ensureTrackedSessionTable($con);

        $identifiers = array();
        if (!empty($user['name'])) {
            $identifiers[] = trim((string) $user['name']);
        }
        if (!empty($user['email'])) {
            $identifiers[] = trim((string) $user['email']);
        }

        $identifiers = array_values(array_unique(array_filter($identifiers)));
        if (empty($identifiers)) {
            return false;
        }

        $safeValues = array();
        foreach ($identifiers as $identifier) {
            $safeValues[] = "'" . mysqli_real_escape_string($con, $identifier) . "'";
        }

        $sql = "UPDATE tracked_sessions
                SET is_active = 0, logout_at = CURRENT_TIMESTAMP, last_activity = CURRENT_TIMESTAMP
                WHERE actor_type = 'user' AND actor_identifier IN (" . implode(', ', $safeValues) . ")";

        return mysqli_query($con, $sql) ? true : false;
    }
}

if (!function_exists('suspendUserAccount')) {
    function suspendUserAccount($con, $userId, $untilDateTime)
    {
        if (!$con || !ensureUserAccountControlColumns($con)) {
            return false;
        }

        $userId = (int) $userId;
        $untilDateTime = trim((string) $untilDateTime);
        if ($userId <= 0 || $untilDateTime === '') {
            return false;
        }

        $safeUntil = mysqli_real_escape_string($con, $untilDateTime);
        return mysqli_query(
            $con,
            "UPDATE users SET account_status = 'suspended', suspended_until = '" . $safeUntil . "', deleted_at = NULL WHERE id = " . $userId . " AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
        ) ? true : false;
    }
}

if (!function_exists('reactivateUserAccount')) {
    function reactivateUserAccount($con, $userId)
    {
        if (!$con || !ensureUserAccountControlColumns($con)) {
            return false;
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        return mysqli_query(
            $con,
            "UPDATE users SET account_status = 'active', suspended_until = NULL WHERE id = " . $userId . " AND (account_status <> 'deleted' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'))"
        ) ? true : false;
    }
}

if (!function_exists('deleteUserAccount')) {
    function deleteUserAccount($con, $userId)
    {
        if (!$con || !ensureUserAccountControlColumns($con)) {
            return false;
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        return mysqli_query(
            $con,
            "UPDATE users SET account_status = 'deleted', suspended_until = NULL, deleted_at = NOW() WHERE id = " . $userId
        ) ? true : false;
    }
}
?>
