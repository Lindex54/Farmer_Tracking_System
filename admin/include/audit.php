<?php
if (!function_exists('ensureAuditLogTable')) {
    function ensureAuditLogTable($con)
    {
        if (!$con) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            actor_type VARCHAR(32) NOT NULL,
            actor_identifier VARCHAR(255) DEFAULT NULL,
            event_type VARCHAR(64) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'info',
            ip_address VARCHAR(45) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_actor_type (actor_type),
            KEY idx_audit_event_type (event_type),
            KEY idx_audit_status (status),
            KEY idx_audit_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        return mysqli_query($con, $sql) ? true : false;
    }
}

if (!function_exists('writeAuditLog')) {
    function writeAuditLog($con, $actorType, $actorIdentifier, $eventType, $status = 'info', $details = null)
    {
        if (!$con || !ensureAuditLogTable($con)) {
            return false;
        }

        $actorType = trim((string)$actorType);
        $actorIdentifier = trim((string)$actorIdentifier);
        $eventType = trim((string)$eventType);
        $status = trim((string)$status);
        $details = $details === null ? null : trim((string)$details);
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';

        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO audit_logs (actor_type, actor_identifier, event_type, status, ip_address, details)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ssssss', $actorType, $actorIdentifier, $eventType, $status, $ipAddress, $details);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}
