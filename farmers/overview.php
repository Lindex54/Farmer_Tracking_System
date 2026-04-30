<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
require_once __DIR__ . '/../includes/post-harvest-helpers.php';
require_once __DIR__ . '/../includes/farmer-product-helpers.php';
requireAdminOrFarmer(appUrl('/farmers/login.php'));

$activePage = 'overview';
$pageError = '';
$currentFarmerId = (function_exists('isFarmer') && isFarmer() && !empty($_SESSION['farmer_id'])) ? (int)$_SESSION['farmer_id'] : 0;
$isFarmerView = function_exists('isFarmer') && isFarmer();
$where = '';
$types = '';
$params = array();

if (!$con) {
    $pageError = 'Database connection is not available.';
} elseif (!ensurePostHarvestTables($con)) {
    $pageError = 'Unable to prepare overview tables.';
} elseif (!ensureFarmerProductTables($con)) {
    $pageError = 'Unable to prepare marketplace tables.';
} elseif ($isFarmerView && $currentFarmerId <= 0) {
    $pageError = 'Your farmer account could not be identified. Please sign in again.';
} elseif ($isFarmerView) {
    $where = ' WHERE b.farmer_id = ?';
    $types = 'i';
    $params = array($currentFarmerId);
}

function overview_fetch($con, $sql, $types = '', $params = array())
{
    return dbFetchOne($con, $sql, $types, $params);
}

function overview_number($value, $decimals = 0)
{
    if ($value === null || $value === '') {
        $value = 0;
    }
    return number_format((float)$value, $decimals);
}

$farmerProfile = null;
$batchStats = array();
$stageStats = array();
$storageStats = array();
$qualityStats = array();
$registeredFarmers = array('total_farmers' => 0);
$recentBatches = array();
$methodRows = array();
$marketStats = array('submitted_products' => 0, 'published_products' => 0, 'sold_orders' => 0, 'sales_revenue' => 0);

if ($pageError === '') {
    if ($isFarmerView) {
        $farmerProfile = overview_fetch(
            $con,
            "SELECT name, farmer_number, location, phone, farm_size, group_membership, created_at FROM farmers WHERE id = ? LIMIT 1",
            'i',
            array($currentFarmerId)
        );
    }

    $batchStats = overview_fetch(
        $con,
        "SELECT
            COUNT(*) AS total_batches,
            COALESCE(SUM(quantity_kg), 0) AS total_quantity,
            COALESCE(SUM(remaining_qty_kg), 0) AS remaining_quantity,
            COALESCE(AVG(initial_moisture), 0) AS avg_moisture,
            COALESCE(AVG(sorting_quality_score), 0) AS avg_sorting_score,
            SUM(CASE WHEN status = 'drying' THEN 1 ELSE 0 END) AS drying_batches,
            SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) AS complete_batches
         FROM batches b" . $where,
        $types,
        $params
    );

    $stageStats = overview_fetch(
        $con,
        "SELECT
            COUNT(phs.id) AS total_stages,
            SUM(CASE WHEN phs.stage_type = 'Drying' THEN 1 ELSE 0 END) AS drying_stages,
            COALESCE(AVG(phs.result_score), 0) AS avg_stage_score
         FROM post_harvest_stages phs
         INNER JOIN batches b ON b.batch_id = phs.batch_id" . $where,
        $types,
        $params
    );

    $storageStats = overview_fetch(
        $con,
        "SELECT
            COUNT(sr.id) AS total_storage_records,
            SUM(CASE WHEN sr.end_date IS NULL THEN 1 ELSE 0 END) AS active_storage,
            COALESCE(AVG(sr.moisture_level), 0) AS avg_storage_moisture,
            SUM(CASE WHEN sr.pest_infestation_level IN ('Medium', 'High') THEN 1 ELSE 0 END) AS pest_alerts
         FROM storage_records sr
         INNER JOIN batches b ON b.batch_id = sr.batch_id" . $where,
        $types,
        $params
    );

    $qualityStats = overview_fetch(
        $con,
        "SELECT
            COUNT(ql.id) AS total_quality_logs,
            SUM(CASE WHEN ql.mold_presence = 1 THEN 1 ELSE 0 END) AS mold_alerts,
            COALESCE(MAX(ql.aflatoxin_reading), 0) AS max_aflatoxin,
            COALESCE(AVG(ql.aflatoxin_reading), 0) AS avg_aflatoxin
         FROM quality_logs ql
         INNER JOIN batches b ON b.batch_id = ql.batch_id" . $where,
        $types,
        $params
    );

    $registeredFarmers = overview_fetch($con, "SELECT COUNT(*) AS total_farmers FROM farmers");
    $marketProductWhere = $isFarmerView ? ' WHERE farmer_id = ?' : '';
    $submittedProducts = overview_fetch($con, "SELECT COUNT(*) AS total FROM farmer_products" . $marketProductWhere, $types, $params);
    $publishedProducts = overview_fetch($con, "SELECT COUNT(*) AS total FROM marketplace_products" . $marketProductWhere, $types, $params);
    $soldOrders = overview_fetch($con, "SELECT COUNT(*) AS total FROM marketplace_orders" . $marketProductWhere, $types, $params);
    $salesRevenue = overview_fetch($con, "SELECT COALESCE(SUM((quantity * unit_price) + shipping_charge), 0) AS total FROM marketplace_orders" . $marketProductWhere, $types, $params);
    $marketStats = array(
        'submitted_products' => isset($submittedProducts['total']) ? $submittedProducts['total'] : 0,
        'published_products' => isset($publishedProducts['total']) ? $publishedProducts['total'] : 0,
        'sold_orders' => isset($soldOrders['total']) ? $soldOrders['total'] : 0,
        'sales_revenue' => isset($salesRevenue['total']) ? $salesRevenue['total'] : 0
    );

    $recentSql = "SELECT b.batch_id, b.harvest_date, b.quantity_kg, b.remaining_qty_kg, b.drying_method, b.status, f.name AS farmer_name
                  FROM batches b
                  INNER JOIN farmers f ON f.id = b.farmer_id" . $where . "
                  ORDER BY b.batch_id DESC
                  LIMIT 5";
    $stmt = mysqli_prepare($con, $recentSql);
    if ($stmt) {
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, $params[0]);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $recentBatches[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $methodSql = "SELECT COALESCE(NULLIF(b.drying_method, ''), 'Not Set') AS drying_method, COUNT(*) AS total_batches
                  FROM batches b" . $where . "
                  GROUP BY COALESCE(NULLIF(b.drying_method, ''), 'Not Set')
                  ORDER BY total_batches DESC";
    $stmt = mysqli_prepare($con, $methodSql);
    if ($stmt) {
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, $params[0]);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $methodRows[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

$totalBatches = isset($batchStats['total_batches']) ? (int)$batchStats['total_batches'] : 0;
$totalQuantity = isset($batchStats['total_quantity']) ? (float)$batchStats['total_quantity'] : 0;
$remainingQuantity = isset($batchStats['remaining_quantity']) ? (float)$batchStats['remaining_quantity'] : 0;
$processedQuantity = max(0, $totalQuantity - $remainingQuantity);
$remainingPercent = $totalQuantity > 0 ? min(100, ($remainingQuantity / $totalQuantity) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Farmer Overview</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css?v=farmer-header-2" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.overview-band { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
		.overview-stat {
			position: relative;
			overflow: hidden;
			background: #fff;
			border: 1px solid #dce7df;
			border-left: 5px solid #2f7b5d;
			border-radius: 6px;
			padding: 16px 16px 14px;
			min-height: 88px;
			box-shadow: 0 8px 22px rgba(22, 59, 41, 0.08);
		}
		.overview-stat:before {
			position: absolute;
			right: 14px;
			top: 14px;
			font-family: FontAwesome;
			font-size: 24px;
			line-height: 1;
			opacity: 0.18;
		}
		.overview-stat strong {
			display: block;
			color: #123b2a;
			font-size: 24px;
			line-height: 1.2;
			margin-top: 8px;
			overflow-wrap: anywhere;
		}
		.overview-stat span { color: #435d52; font-size: 12px; text-transform: uppercase; font-weight: 700; }
		.stat-batches { background: #f4fbf7; border-left-color: #2f7b5d; }
		.stat-batches:before { content: "\f1b3"; color: #2f7b5d; }
		.stat-quantity { background: #f7fbff; border-left-color: #2f6f9f; }
		.stat-quantity:before { content: "\f0ce"; color: #2f6f9f; }
		.stat-remaining { background: #fffaf0; border-left-color: #b7791f; }
		.stat-remaining:before { content: "\f017"; color: #b7791f; }
		.stat-farmers { background: #f5fbfa; border-left-color: #22837a; }
		.stat-farmers:before { content: "\f0c0"; color: #22837a; }
		.stat-drying { background: #f3fbf4; border-left-color: #4fb477; }
		.stat-drying:before { content: "\f185"; color: #4fb477; }
		.stat-complete { background: #f3f8ff; border-left-color: #4776b4; }
		.stat-complete:before { content: "\f00c"; color: #4776b4; }
		.stat-quality { background: #fbf8f1; border-left-color: #9a7a32; }
		.stat-quality:before { content: "\f0f6"; color: #9a7a32; }
		.stat-alert { background: #fff6f4; border-left-color: #b94a48; }
		.stat-alert:before { content: "\f071"; color: #b94a48; }
		.overview-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; }
		.progress-wrap { height: 12px; background: #eaf2ec; border-radius: 999px; overflow: hidden; margin: 8px 0 6px; }
		.progress-fill { height: 100%; background: #4fb477; }
		.profile-list { margin: 0; }
		.profile-list dt { color: #60736a; font-weight: 700; }
		.profile-list dd { margin-left: 0; margin-bottom: 9px; }
		.overview-grid .module .module-head {
			height: auto !important;
			min-height: 46px !important;
			padding: 12px 18px !important;
		}
		.overview-grid .module .module-head h3 {
			height: auto !important;
			line-height: 1.3 !important;
			white-space: normal !important;
		}
		.method-row { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #edf2ef; padding: 8px 0; }
		.method-row:last-child { border-bottom: 0; }
		@media (max-width: 979px) { .overview-band, .overview-grid { grid-template-columns: 1fr; } }
	</style>
</head>
<body>
<?php include('../admin/include/header.php'); ?>
	<div class="wrapper">
		<div class="container">
			<div class="row">
				<?php include('include/sidebar.php'); ?>
				<div class="span9">
					<div class="content">
<?php if ($pageError !== '') { ?>
						<div class="alert alert-error"><?php echo htmlentities($pageError); ?></div>
<?php } else { ?>
						<div class="overview-band">
							<div class="overview-stat stat-batches"><span>Total Batches</span><strong><?php echo overview_number($totalBatches); ?></strong></div>
							<div class="overview-stat stat-quantity"><span>Total Quantity</span><strong><?php echo overview_number($totalQuantity, 2); ?> kg</strong></div>
							<div class="overview-stat stat-remaining"><span>Remaining Quantity</span><strong><?php echo overview_number($remainingQuantity, 2); ?> kg</strong></div>
							<div class="overview-stat stat-farmers"><span>Available Farmers</span><strong><?php echo overview_number(isset($registeredFarmers['total_farmers']) ? $registeredFarmers['total_farmers'] : 0); ?></strong></div>
						</div>

						<div class="overview-band">
							<div class="overview-stat stat-drying"><span>Drying Batches</span><strong><?php echo overview_number(isset($batchStats['drying_batches']) ? $batchStats['drying_batches'] : 0); ?></strong></div>
							<div class="overview-stat stat-complete"><span>Complete Batches</span><strong><?php echo overview_number(isset($batchStats['complete_batches']) ? $batchStats['complete_batches'] : 0); ?></strong></div>
							<div class="overview-stat stat-quality"><span>Quality Tests</span><strong><?php echo overview_number(isset($qualityStats['total_quality_logs']) ? $qualityStats['total_quality_logs'] : 0); ?></strong></div>
							<div class="overview-stat stat-alert"><span>Mold Alerts</span><strong><?php echo overview_number(isset($qualityStats['mold_alerts']) ? $qualityStats['mold_alerts'] : 0); ?></strong></div>
						</div>
						<div class="overview-band">
							<div class="overview-stat stat-batches"><span>Products Submitted</span><strong><?php echo overview_number(isset($marketStats['submitted_products']) ? $marketStats['submitted_products'] : 0); ?></strong></div>
							<div class="overview-stat stat-complete"><span>Products Live</span><strong><?php echo overview_number(isset($marketStats['published_products']) ? $marketStats['published_products'] : 0); ?></strong></div>
							<div class="overview-stat stat-quality"><span>Sold Orders</span><strong><?php echo overview_number(isset($marketStats['sold_orders']) ? $marketStats['sold_orders'] : 0); ?></strong></div>
							<div class="overview-stat stat-quantity"><span>Sales Revenue</span><strong><?php echo htmlentities(formatMarketMoney(isset($marketStats['sales_revenue']) ? $marketStats['sales_revenue'] : 0)); ?></strong></div>
						</div>

						<div class="overview-grid">
							<div>
								<div class="module">
									<div class="module-head"><h3>Batch Flow</h3></div>
									<div class="module-body">
										<p><strong><?php echo overview_number($processedQuantity, 2); ?> kg</strong> processed or no longer remaining out of <strong><?php echo overview_number($totalQuantity, 2); ?> kg</strong>.</p>
										<div class="progress-wrap"><div class="progress-fill" style="width: <?php echo htmlentities(number_format($remainingPercent, 2, '.', '')); ?>%;"></div></div>
										<p><?php echo overview_number($remainingPercent, 1); ?>% of recorded quantity remains.</p>
										<table class="table table-bordered table-striped">
											<thead><tr><th>Batch</th><th>Harvest Date</th><th>Method</th><th>Qty</th><th>Remaining</th><th>Status</th></tr></thead>
											<tbody>
<?php foreach ($recentBatches as $batch) { ?>
												<tr>
													<td><?php echo (int)$batch['batch_id']; ?></td>
													<td><?php echo htmlentities($batch['harvest_date']); ?></td>
													<td><?php echo $batch['drying_method'] !== '' ? htmlentities($batch['drying_method']) : '-'; ?></td>
													<td><?php echo overview_number($batch['quantity_kg'], 2); ?></td>
													<td><?php echo overview_number($batch['remaining_qty_kg'], 2); ?></td>
													<td><?php echo htmlentities(ucfirst((string)$batch['status'])); ?></td>
												</tr>
<?php } ?>
<?php if (empty($recentBatches)) { ?>
												<tr><td colspan="6">No batches recorded yet.</td></tr>
<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
								<div class="module">
									<div class="module-head"><h3>Drying Methods</h3></div>
									<div class="module-body">
<?php foreach ($methodRows as $row) { ?>
										<div class="method-row"><span><?php echo htmlentities($row['drying_method']); ?></span><strong><?php echo overview_number($row['total_batches']); ?></strong></div>
<?php } ?>
<?php if (empty($methodRows)) { ?>
										<p>No drying method data yet.</p>
<?php } ?>
									</div>
								</div>
							</div>
							<div>
								<div class="module">
									<div class="module-head"><h3>Farmer Profile</h3></div>
									<div class="module-body">
										<dl class="profile-list">
											<dt>Name</dt><dd><?php echo htmlentities($farmerProfile && $farmerProfile['name'] ? $farmerProfile['name'] : getCurrentDisplayName()); ?></dd>
											<dt>Farmer Number</dt><dd><?php echo htmlentities($farmerProfile && $farmerProfile['farmer_number'] ? $farmerProfile['farmer_number'] : '-'); ?></dd>
											<dt>Location</dt><dd><?php echo htmlentities($farmerProfile && $farmerProfile['location'] ? $farmerProfile['location'] : '-'); ?></dd>
											<dt>Farm Size</dt><dd><?php echo $farmerProfile && $farmerProfile['farm_size'] !== null ? overview_number($farmerProfile['farm_size'], 2) : '-'; ?></dd>
											<dt>Group</dt><dd><?php echo htmlentities($farmerProfile && $farmerProfile['group_membership'] ? $farmerProfile['group_membership'] : '-'); ?></dd>
										</dl>
									</div>
								</div>
								<div class="module">
									<div class="module-head"><h3>Post-Harvest Health</h3></div>
									<div class="module-body">
										<dl class="profile-list">
											<dt>Stages Recorded</dt><dd><?php echo overview_number(isset($stageStats['total_stages']) ? $stageStats['total_stages'] : 0); ?></dd>
											<dt>Average Stage Score</dt><dd><?php echo overview_number(isset($stageStats['avg_stage_score']) ? $stageStats['avg_stage_score'] : 0, 2); ?></dd>
											<dt>Active Storage</dt><dd><?php echo overview_number(isset($storageStats['active_storage']) ? $storageStats['active_storage'] : 0); ?></dd>
											<dt>Pest Alerts</dt><dd><?php echo overview_number(isset($storageStats['pest_alerts']) ? $storageStats['pest_alerts'] : 0); ?></dd>
											<dt>Max Aflatoxin</dt><dd><?php echo overview_number(isset($qualityStats['max_aflatoxin']) ? $qualityStats['max_aflatoxin'] : 0, 2); ?></dd>
										</dl>
									</div>
								</div>
							</div>
						</div>
<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php include('../admin/include/footer.php'); ?>
	<script src="../admin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="../admin/scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="../admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
