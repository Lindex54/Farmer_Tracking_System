<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/include/dashboard-metrics.php';

requireAdmin(appUrl('/admin/index.php'));
$activePage = 'dashboard';
$snapshot = getDashboardSnapshot($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Admin Dashboard</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="css/dashboard.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
</head>
<body>
<?php include('include/header.php'); ?>
	<div class="wrapper">
		<div class="container">
			<div class="row">
<?php include('include/sidebar.php'); ?>
				<div class="span9">
					<div class="content dashboard-shell" id="dashboard-app" data-dashboard-endpoint="<?php echo htmlentities(appUrl('/admin/dashboard-data.php')); ?>">
						<div class="dashboard-hero">
							<div>
								<h1>Operations Dashboard</h1>
								<p>Control the marketplace from one polished command center.</p>
							</div>
							<div class="dashboard-live-badge">
								Live data
								<div id="dashboard-last-updated" style="font-size:12px;font-weight:400;margin-top:4px;"><?php echo htmlentities(date('M d, Y H:i:s')); ?></div>
							</div>
						</div>

						<div class="dashboard-grid">
							<div class="dashboard-kpi-grid">
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Products</div>
									<div class="dashboard-kpi-value" data-kpi="products"><?php echo (int) $snapshot['kpis']['products']; ?></div>
									<div class="dashboard-kpi-sub">Live catalog items in the marketplace</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Users</div>
									<div class="dashboard-kpi-value" data-kpi="users"><?php echo (int) $snapshot['kpis']['users']; ?></div>
									<div class="dashboard-kpi-sub">Registered customer accounts</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Farmers</div>
									<div class="dashboard-kpi-value" data-kpi="farmers"><?php echo (int) $snapshot['kpis']['farmers']; ?></div>
									<div class="dashboard-kpi-sub">Farmer profiles using the portal</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Revenue</div>
									<div class="dashboard-kpi-value" data-kpi-money="revenue"><?php echo number_format((float) $snapshot['kpis']['revenue']); ?></div>
									<div class="dashboard-kpi-sub">Estimated gross order value</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Orders</div>
									<div class="dashboard-kpi-value" data-kpi="orders"><?php echo (int) $snapshot['kpis']['orders']; ?></div>
									<div class="dashboard-kpi-sub">All orders recorded in the system</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Pending Orders</div>
									<div class="dashboard-kpi-value" data-kpi="pendingOrders"><?php echo (int) $snapshot['kpis']['pendingOrders']; ?></div>
									<div class="dashboard-kpi-sub">Orders still awaiting completion</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Drying Batches</div>
									<div class="dashboard-kpi-value" data-kpi="dryingBatches"><?php echo (int) $snapshot['kpis']['dryingBatches']; ?></div>
									<div class="dashboard-kpi-sub">Batches currently in active drying</div>
								</div>
								<div class="dashboard-card dashboard-kpi">
									<div class="dashboard-kpi-label">Today's Logins</div>
									<div class="dashboard-kpi-value" data-kpi="todaysLogins"><?php echo (int) $snapshot['kpis']['todaysLogins']; ?></div>
									<div class="dashboard-kpi-sub">Successful access events recorded today</div>
								</div>
							</div>

							<div class="dashboard-card dashboard-chart-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Orders Trend</h3>
										<span class="muted">Last 7 days</span>
									</div>
									<div id="orders-trend-chart" class="chart-placeholder"></div>
								</div>
							</div>

							<div class="dashboard-card dashboard-chart-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Login Activity</h3>
										<span class="muted">Successful sign-ins</span>
									</div>
									<div id="logins-trend-chart" class="chart-placeholder"></div>
								</div>
							</div>

							<div class="dashboard-card dashboard-wide-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Recent Orders</h3>
										<a href="<?php echo htmlentities(appUrl('/admin/todays-orders.php')); ?>">Open orders</a>
									</div>
									<div style="overflow:auto;">
										<table class="dashboard-table" id="recent-orders-table">
											<thead>
												<tr>
													<th>Order</th>
													<th>Customer</th>
													<th>Product</th>
													<th>Qty</th>
													<th>Status</th>
													<th>Total</th>
													<th>Date</th>
												</tr>
											</thead>
											<tbody></tbody>
										</table>
									</div>
								</div>
							</div>

							<div class="dashboard-card dashboard-side-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Role Login Mix</h3>
										<a href="<?php echo htmlentities(appUrl('/admin/audit-logs.php')); ?>">View audit logs</a>
									</div>
									<div id="role-logins-feed" class="dashboard-feed"></div>
								</div>
							</div>

							<div class="dashboard-card dashboard-side-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Catalog Breakdown</h3>
										<span class="muted">Products by category</span>
									</div>
									<div id="category-breakdown-feed" class="dashboard-feed"></div>
								</div>
							</div>

							<div class="dashboard-card dashboard-wide-card">
								<div class="dashboard-panel">
									<div class="dashboard-panel-header">
										<h3>Recent Audit Trail</h3>
										<a href="<?php echo htmlentities(appUrl('/admin/audit-logs.php')); ?>">Open full log</a>
									</div>
									<div id="audit-feed" class="dashboard-feed"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php include('include/footer.php'); ?>

	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
	<script>
	(function () {
		var app = $('#dashboard-app');
		var endpoint = app.data('dashboard-endpoint');

		function formatMoney(value) {
			var numeric = parseFloat(value || 0);
			return 'UGX ' + numeric.toLocaleString();
		}

		function statusClass(status) {
			if (status === 'success') {
				return 'success';
			}
			if (status === 'failed' || status === 'failure' || status === 'error') {
				return 'failed';
			}
			return 'info';
		}

		function plotLineChart(target, data, color) {
			var points = [];
			var ticks = [];
			$.each(data || [], function (index, item) {
				points.push([index, parseInt(item.value, 10) || 0]);
				ticks.push([index, item.label]);
			});

			$.plot($(target), [{ data: points, color: color, lines: { show: true, fill: 0.08 }, points: { show: true, radius: 4 } }], {
				grid: { borderColor: '#e6ece7', borderWidth: 1, hoverable: true, backgroundColor: '#fff' },
				xaxis: { ticks: ticks, tickLength: 0, color: '#dfe7df' },
				yaxis: { min: 0, tickDecimals: 0, color: '#dfe7df' }
			});
		}

		function renderOrders(rows) {
			var tbody = $('#recent-orders-table tbody');
			tbody.empty();
			if (!rows || !rows.length) {
				tbody.append('<tr><td colspan="7" class="dashboard-empty">No recent orders found.</td></tr>');
				return;
			}

			$.each(rows, function (_, row) {
				var status = row.orderStatus ? row.orderStatus : 'Pending';
				tbody.append(
					'<tr>' +
						'<td>#' + row.id + '</td>' +
						'<td>' + $('<div>').text(row.customer_name || 'Unknown').html() + '</td>' +
						'<td>' + $('<div>').text(row.product_name || 'Unknown').html() + '</td>' +
						'<td>' + row.quantity + '</td>' +
						'<td><span class="status-pill ' + statusClass(String(status).toLowerCase()) + '">' + $('<div>').text(status).html() + '</span></td>' +
						'<td>' + formatMoney(row.order_total) + '</td>' +
						'<td>' + $('<div>').text(row.orderDate).html() + '</td>' +
					'</tr>'
				);
			});
		}

		function renderRoleLogins(rows) {
			var container = $('#role-logins-feed');
			container.empty();
			if (!rows || !rows.length) {
				container.html('<div class="dashboard-empty">No login activity yet.</div>');
				return;
			}

			$.each(rows, function (_, row) {
				container.append(
					'<div class="dashboard-feed-item">' +
						'<strong>' + $('<div>').text(row.actor_type).html() + '</strong>' +
						'<div class="dashboard-feed-meta"><span>Successful logins</span><span>' + row.total + '</span></div>' +
					'</div>'
				);
			});
		}

		function renderCategoryBreakdown(rows) {
			var container = $('#category-breakdown-feed');
			container.empty();
			if (!rows || !rows.length) {
				container.html('<div class="dashboard-empty">No category data available.</div>');
				return;
			}

			$.each(rows, function (_, row) {
				container.append(
					'<div class="dashboard-feed-item">' +
						'<strong>' + $('<div>').text(row.label).html() + '</strong>' +
						'<div class="dashboard-feed-meta"><span>Products</span><span>' + row.total + '</span></div>' +
					'</div>'
				);
			});
		}

		function renderAudit(rows) {
			var container = $('#audit-feed');
			container.empty();
			if (!rows || !rows.length) {
				container.html('<div class="dashboard-empty">No audit events recorded yet.</div>');
				return;
			}

			$.each(rows, function (_, row) {
				var actor = (row.actor_type || 'actor') + ': ' + (row.actor_identifier || 'unknown');
				var details = row.details ? $('<div>').text(row.details).html() : 'No additional details';
				container.append(
					'<div class="dashboard-feed-item">' +
						'<strong>' + $('<div>').text(actor).html() + '</strong>' +
						'<div>' + $('<div>').text(row.event_type).html() + ' <span class="status-pill ' + statusClass(String(row.status).toLowerCase()) + '">' + $('<div>').text(row.status).html() + '</span></div>' +
						'<div style="margin-top:8px;color:#627264;">' + details + '</div>' +
						'<div class="dashboard-feed-meta"><span>' + $('<div>').text(row.ip_address || '').html() + '</span><span>' + $('<div>').text(row.created_at).html() + '</span></div>' +
					'</div>'
				);
			});
		}

		function updateSnapshot(snapshot) {
			$('[data-kpi]').each(function () {
				var key = $(this).data('kpi');
				$(this).text((snapshot.kpis && snapshot.kpis[key]) ? snapshot.kpis[key] : 0);
			});
			$('[data-kpi-money]').each(function () {
				var key = $(this).data('kpi-money');
				var value = snapshot.kpis && snapshot.kpis[key] ? snapshot.kpis[key] : 0;
				$(this).text(formatMoney(value));
			});

			$('#dashboard-last-updated').text(new Date().toLocaleString());
			plotLineChart('#orders-trend-chart', snapshot.ordersTrend || [], '#4fb477');
			plotLineChart('#logins-trend-chart', snapshot.loginsTrend || [], '#f0ad4e');
			renderOrders(snapshot.recentOrders || []);
			renderRoleLogins(snapshot.roleLogins || []);
			renderCategoryBreakdown(snapshot.categoryBreakdown || []);
			renderAudit(snapshot.recentAudit || []);
		}

		function refreshDashboard() {
			$.getJSON(endpoint, function (snapshot) {
				updateSnapshot(snapshot || {});
			});
		}

		updateSnapshot(<?php echo json_encode($snapshot); ?>);
		setInterval(refreshDashboard, 30000);
	})();
	</script>
</body>
</html>
