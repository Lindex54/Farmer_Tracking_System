<?php
require_once __DIR__ . '/audit.php';
require_once dirname(__DIR__, 2) . '/includes/farmer-product-helpers.php';

if (!function_exists('dashboardScalar')) {
    function dashboardScalar($con, $sql)
    {
        if (!$con) {
            return 0;
        }

        $result = mysqli_query($con, $sql);
        if (!$result) {
            return 0;
        }

        $row = mysqli_fetch_row($result);
        mysqli_free_result($result);

        return isset($row[0]) ? $row[0] : 0;
    }
}

if (!function_exists('dashboardRows')) {
    function dashboardRows($con, $sql)
    {
        $rows = array();
        if (!$con) {
            return $rows;
        }

        $result = mysqli_query($con, $sql);
        if (!$result) {
            return $rows;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        mysqli_free_result($result);
        return $rows;
    }
}

if (!function_exists('dashboardDateSeries')) {
    function dashboardDateSeries($days)
    {
        $series = array();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $series[$date] = 0;
        }
        return $series;
    }
}

if (!function_exists('getDashboardSnapshot')) {
    function getDashboardSnapshot($con)
    {
        ensureAuditLogTable($con);
        ensureFarmerProductTables($con);

        $snapshot = array(
            'generatedAt' => date('c'),
            'kpis' => array(
                'products' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM marketplace_products WHERE status = 'published'"),
                'users' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM users"),
                'farmers' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM farmers"),
                'orders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM marketplace_orders"),
                'pendingOrders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM marketplace_orders WHERE order_status IS NULL OR order_status <> 'Delivered'"),
                'deliveredOrders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM marketplace_orders WHERE order_status = 'Delivered'"),
                'batches' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM batches"),
                'dryingBatches' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM batches WHERE status = 'drying'"),
                'todaysLogins' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM audit_logs WHERE event_type LIKE '%login%' AND status = 'success' AND DATE(created_at) = CURDATE()"),
                'revenue' => (float) dashboardScalar($con, "SELECT COALESCE(SUM((quantity * unit_price) + shipping_charge), 0) FROM marketplace_orders")
            ),
            'recentOrders' => array(),
            'ordersTrend' => array(),
            'loginsTrend' => array(),
            'roleLogins' => array(),
            'categoryBreakdown' => array()
        );

        $orderSeries = dashboardDateSeries(7);
        $orderRows = dashboardRows(
            $con,
            "SELECT DATE(order_date) AS order_day, COUNT(*) AS total_orders
             FROM marketplace_orders
             WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(order_date)
             ORDER BY order_day ASC"
        );
        foreach ($orderRows as $row) {
            if (isset($orderSeries[$row['order_day']])) {
                $orderSeries[$row['order_day']] = (int) $row['total_orders'];
            }
        }
        foreach ($orderSeries as $day => $count) {
            $snapshot['ordersTrend'][] = array(
                'label' => date('M d', strtotime($day)),
                'value' => $count
            );
        }

        $loginSeries = dashboardDateSeries(7);
        $loginRows = dashboardRows(
            $con,
            "SELECT DATE(created_at) AS audit_day, COUNT(*) AS total_logins
             FROM audit_logs
             WHERE event_type LIKE '%login%' AND status = 'success'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY audit_day ASC"
        );
        foreach ($loginRows as $row) {
            if (isset($loginSeries[$row['audit_day']])) {
                $loginSeries[$row['audit_day']] = (int) $row['total_logins'];
            }
        }
        foreach ($loginSeries as $day => $count) {
            $snapshot['loginsTrend'][] = array(
                'label' => date('M d', strtotime($day)),
                'value' => $count
            );
        }

        $snapshot['roleLogins'] = dashboardRows(
            $con,
            "SELECT actor_type, COUNT(*) AS total
             FROM audit_logs
             WHERE event_type LIKE '%login%' AND status = 'success'
             GROUP BY actor_type
             ORDER BY total DESC"
        );

        $snapshot['categoryBreakdown'] = dashboardRows(
            $con,
            "SELECT f.name AS label, COUNT(mp.id) AS total
             FROM marketplace_products mp
             INNER JOIN farmers f ON f.id = mp.farmer_id
             WHERE mp.status = 'published'
             GROUP BY f.id, f.name
             ORDER BY total DESC, f.name ASC
             LIMIT 6"
        );

        $snapshot['recentOrders'] = dashboardRows(
            $con,
            "SELECT mo.id, users.name AS customer_name, mp.product_name,
                    mo.quantity, mo.order_status AS orderStatus, mo.order_date AS orderDate,
                    ((mo.quantity * mo.unit_price) + mo.shipping_charge) AS order_total
             FROM marketplace_orders mo
             INNER JOIN users ON users.id = mo.user_id
             INNER JOIN marketplace_products mp ON mp.id = mo.product_id
             ORDER BY mo.order_date DESC
             LIMIT 8"
        );

        return $snapshot;
    }
}
