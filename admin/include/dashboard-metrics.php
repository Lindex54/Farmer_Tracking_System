<?php
require_once __DIR__ . '/audit.php';

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

        $snapshot = array(
            'generatedAt' => date('c'),
            'kpis' => array(
                'products' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM products"),
                'users' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM users"),
                'farmers' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM farmers"),
                'orders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM orders"),
                'pendingOrders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM orders WHERE orderStatus IS NULL OR orderStatus <> 'Delivered'"),
                'deliveredOrders' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM orders WHERE orderStatus = 'Delivered'"),
                'batches' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM batches"),
                'dryingBatches' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM batches WHERE status = 'drying'"),
                'todaysLogins' => (int) dashboardScalar($con, "SELECT COUNT(*) FROM audit_logs WHERE event_type LIKE '%login%' AND status = 'success' AND DATE(created_at) = CURDATE()"),
                'revenue' => (float) dashboardScalar($con, "SELECT COALESCE(SUM((orders.quantity * products.productPrice) + products.shippingCharge), 0) FROM orders INNER JOIN products ON products.id = orders.productId")
            ),
            'recentOrders' => array(),
            'recentAudit' => array(),
            'ordersTrend' => array(),
            'loginsTrend' => array(),
            'roleLogins' => array(),
            'categoryBreakdown' => array()
        );

        $orderSeries = dashboardDateSeries(7);
        $orderRows = dashboardRows(
            $con,
            "SELECT DATE(orderDate) AS order_day, COUNT(*) AS total_orders
             FROM orders
             WHERE orderDate >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(orderDate)
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
            "SELECT category.categoryName AS label, COUNT(products.id) AS total
             FROM category
             LEFT JOIN products ON products.category = category.id
             GROUP BY category.id, category.categoryName
             ORDER BY total DESC, category.categoryName ASC
             LIMIT 6"
        );

        $snapshot['recentOrders'] = dashboardRows(
            $con,
            "SELECT orders.id, users.name AS customer_name, products.productName AS product_name,
                    orders.quantity, orders.orderStatus, orders.orderDate,
                    ((orders.quantity * products.productPrice) + products.shippingCharge) AS order_total
             FROM orders
             INNER JOIN users ON users.id = orders.userId
             INNER JOIN products ON products.id = orders.productId
             ORDER BY orders.orderDate DESC
             LIMIT 8"
        );

        $snapshot['recentAudit'] = dashboardRows(
            $con,
            "SELECT actor_type, actor_identifier, event_type, status, ip_address, details, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT 10"
        );

        return $snapshot;
    }
}
