<?php
if (!function_exists('dbFetchOne')) {
    function dbFetchOne($con, $sql, $types = '', $params = array())
    {
        if (!$con) {
            return null;
        }

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return null;
        }

        if ($types !== '' && !empty($params)) {
            $bindParams = array($types);
            foreach ($params as $index => $value) {
                $bindParams[] = &$params[$index];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt), $bindParams));
        }

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return null;
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('dbExecute')) {
    function dbExecute($con, $sql, $types = '', $params = array())
    {
        if (!$con) {
            return false;
        }

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return false;
        }

        if ($types !== '' && !empty($params)) {
            $bindParams = array($types);
            foreach ($params as $index => $value) {
                $bindParams[] = &$params[$index];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt), $bindParams));
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}
?>
