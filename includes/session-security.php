<?php
require_once dirname(__DIR__) . '/admin/include/audit.php';
require_once __DIR__ . '/user-account-controls.php';

if (!function_exists('ensureTrackedSessionTable')) {
    function ensureTrackedSessionTable($con)
    {
        if (!$con) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS tracked_sessions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(128) NOT NULL,
            actor_type VARCHAR(32) NOT NULL,
            actor_identifier VARCHAR(255) DEFAULT NULL,
            role_label VARCHAR(64) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            last_page VARCHAR(255) DEFAULT NULL,
            login_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            logout_at TIMESTAMP NULL DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_tracked_session (session_id),
            KEY idx_tracked_actor (actor_type, actor_identifier),
            KEY idx_tracked_active (is_active, last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        return mysqli_query($con, $sql) ? true : false;
    }
}

if (!function_exists('currentSessionId')) {
    function currentSessionId()
    {
        return session_id();
    }
}

if (!function_exists('currentRequestPath')) {
    function currentRequestPath()
    {
        return isset($_SERVER['REQUEST_URI']) ? substr((string) $_SERVER['REQUEST_URI'], 0, 255) : '';
    }
}

if (!function_exists('registerTrackedSession')) {
    function registerTrackedSession($con, $actorType, $actorIdentifier, $roleLabel)
    {
        if (!$con || !ensureTrackedSessionTable($con)) {
            return false;
        }

        $sessionId = currentSessionId();
        if ($sessionId === '') {
            return false;
        }

        $actorType = trim((string) $actorType);
        $actorIdentifier = trim((string) $actorIdentifier);
        $roleLabel = trim((string) $roleLabel);
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(trim((string) $_SERVER['HTTP_USER_AGENT']), 0, 255) : '';
        $lastPage = currentRequestPath();

        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO tracked_sessions (session_id, actor_type, actor_identifier, role_label, ip_address, user_agent, last_page, is_active, logout_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NULL)
             ON DUPLICATE KEY UPDATE
                actor_type = VALUES(actor_type),
                actor_identifier = VALUES(actor_identifier),
                role_label = VALUES(role_label),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_page = VALUES(last_page),
                is_active = 1,
                logout_at = NULL,
                last_activity = CURRENT_TIMESTAMP"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sssssss', $sessionId, $actorType, $actorIdentifier, $roleLabel, $ipAddress, $userAgent, $lastPage);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}

if (!function_exists('touchTrackedSession')) {
    function touchTrackedSession($con)
    {
        if (!$con || !ensureTrackedSessionTable($con)) {
            return false;
        }

        $sessionId = currentSessionId();
        if ($sessionId === '') {
            return false;
        }

        $lastPage = currentRequestPath();
        $stmt = mysqli_prepare(
            $con,
            "UPDATE tracked_sessions
             SET last_activity = CURRENT_TIMESTAMP, last_page = ?
             WHERE session_id = ? AND is_active = 1"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $lastPage, $sessionId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}

if (!function_exists('validateTrackedSession')) {
    function validateTrackedSession($con, $actorType, $actorIdentifier)
    {
        if (!$con || !ensureTrackedSessionTable($con)) {
            return false;
        }

        $sessionId = currentSessionId();
        if ($sessionId === '') {
            return false;
        }

        $actorType = trim((string) $actorType);
        $actorIdentifier = trim((string) $actorIdentifier);

        $stmt = mysqli_prepare(
            $con,
            "SELECT id
             FROM tracked_sessions
             WHERE session_id = ? AND actor_type = ? AND actor_identifier = ? AND is_active = 1
             LIMIT 1"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sss', $sessionId, $actorType, $actorIdentifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ? true : false;
    }
}

if (!function_exists('closeTrackedSession')) {
    function closeTrackedSession($con)
    {
        if (!$con || !ensureTrackedSessionTable($con)) {
            return false;
        }

        $sessionId = currentSessionId();
        if ($sessionId === '') {
            return false;
        }

        $stmt = mysqli_prepare(
            $con,
            "UPDATE tracked_sessions
             SET is_active = 0, logout_at = CURRENT_TIMESTAMP, last_activity = CURRENT_TIMESTAMP
             WHERE session_id = ?"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $sessionId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}

if (!function_exists('invalidateCurrentSessionWithAudit')) {
    function invalidateCurrentSessionWithAudit($con, $actorType, $actorIdentifier, $message, $redirectTo)
    {
        closeTrackedSession($con);
        if ($actorIdentifier !== '') {
            writeAuditLog($con, $actorType, $actorIdentifier, 'session_invalidated', 'failed', $message);
        }
        session_unset();
        header('Location: ' . $redirectTo);
        exit();
    }
}

if (!function_exists('requireUserSession')) {
    function requireUserSession($con, $redirectTo = 'login.php')
    {
        $userIdentifier = '';
        $userId = !empty($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
        $userEmail = !empty($_SESSION['login']) ? (string) $_SESSION['login'] : '';
        if (!empty($_SESSION['username'])) {
            $userIdentifier = (string) $_SESSION['username'];
        } elseif (!empty($_SESSION['login'])) {
            $userIdentifier = (string) $_SESSION['login'];
        }

        if (empty($_SESSION['login']) || $userIdentifier === '') {
            header('Location: ' . $redirectTo);
            exit();
        }

        if (!validateTrackedSession($con, 'user', $userIdentifier)) {
            invalidateCurrentSessionWithAudit($con, 'user', $userIdentifier, 'Invalid or expired customer session detected.', $redirectTo);
        }

        $user = null;
        if ($userId > 0) {
            $user = fetchUserById($con, $userId);
        }
        if (!$user && $userEmail !== '') {
            $user = fetchUserByEmail($con, $userEmail);
        }

        if (!$user) {
            closeTrackedSession($con);
            session_unset();
            $_SESSION['errmsg'] = 'Your account could not be found. Please sign in again.';
            header('Location: ' . $redirectTo);
            exit();
        }

        $state = getUserAccessState($con, $user);
        if (!$state['allowed']) {
            closeTrackedSessionsForUser($con, $state['user']);
            writeAuditLog($con, 'user', $userEmail !== '' ? $userEmail : $userIdentifier, 'session_blocked', 'failed', $state['message']);
            session_unset();
            $_SESSION['errmsg'] = $state['message'];
            header('Location: ' . $redirectTo);
            exit();
        }

        touchTrackedSession($con);
    }
}
