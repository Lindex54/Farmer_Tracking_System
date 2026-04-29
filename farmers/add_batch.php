<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
requireAdminOrFarmer(appUrl('/farmers/login.php'));

$activePage = 'add-batches';
$pageError = '';
$farmers = array();
$currentFarmerId = (function_exists('isFarmer') && isFarmer() && !empty($_SESSION['farmer_id'])) ? (int)$_SESSION['farmer_id'] : 0;

if ($con) {
    if (function_exists('isFarmer') && isFarmer()) {
        if ($currentFarmerId <= 0) {
            $stmt = false;
            $pageError = 'Your farmer account could not be identified. Please sign in again.';
        } else {
            $stmt = mysqli_prepare($con, "SELECT id, name FROM farmers WHERE id = ? LIMIT 1");
        }
    } else {
        $stmt = mysqli_prepare($con, "SELECT id, name FROM farmers ORDER BY name ASC");
    }

    if ($stmt) {
        if (function_exists('isFarmer') && isFarmer()) {
            mysqli_stmt_bind_param($stmt, 'i', $currentFarmerId);
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $farmers[] = $row;
                }
            }
        } else {
            $pageError = 'Unable to fetch farmers at the moment.';
        }
        mysqli_stmt_close($stmt);
    } elseif ($pageError === '') {
        $pageError = 'Unable to prepare farmers query.';
    }
} else {
    $pageError = 'Database connection is not available.';
}

$flashMessage = pullFlashMessage('batch_form');
$batchId = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin | Add Batch</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.batch-form-shell { max-width: 760px; margin: 0 auto; }
		.batch-card.module { border-radius: 8px; overflow: hidden; }
		.batch-card .module-head { padding: 14px 18px; }
		.batch-card .module-body { padding: 24px 22px 12px; }
		.batch-card .control-group { margin-bottom: 18px; }
		.batch-card .control-label { font-weight: 600; }
		.batch-card input,
		.batch-card select { border-radius: 6px; padding: 8px 10px; min-height: 38px; box-sizing: border-box; }
		.batch-card .form-actions-wrap { margin-top: 4px; }
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
						<div class="batch-form-shell">
							<div class="module batch-card">
								<div class="module-head">
									<h3>Add Harvest Batch</h3>
								</div>
								<div class="module-body">
<?php if ($flashMessage && !empty($flashMessage['message'])) { ?>
									<div class="alert <?php echo ($flashMessage['status'] === 'success') ? 'alert-success' : 'alert-error'; ?>">
										<button type="button" class="close" data-dismiss="alert">x</button>
										<?php echo htmlentities($flashMessage['message']); ?>
									</div>
<?php } ?>
<?php if ($pageError !== '') { ?>
									<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">x</button>
										<?php echo htmlentities($pageError); ?>
									</div>
<?php } ?>
									<form id="batchForm" class="form-horizontal row-fluid" action="<?php echo appUrl('/admin/add_batch.php'); ?>" method="post" novalidate>
										<input type="hidden" name="batch_id" value="<?php echo $batchId > 0 ? $batchId : 0; ?>">
<?php if (function_exists('isFarmer') && isFarmer()) { ?>
										<input type="hidden" name="farmer_id" value="<?php echo (int)$currentFarmerId; ?>">
<?php if (!empty($farmers)) { ?>
										<div class="control-group">
											<label class="control-label">Farmer</label>
											<div class="controls">
												<input type="text" class="span10" value="<?php echo htmlentities($farmers[0]['name']); ?>" disabled>
											</div>
										</div>
<?php } ?>
<?php } else { ?>
										<div class="control-group">
											<label class="control-label" for="farmer_id">Farmer</label>
											<div class="controls">
												<select id="farmer_id" name="farmer_id" class="span10" required>
													<option value="">Select Farmer</option>
<?php foreach ($farmers as $farmer) { ?>
													<option value="<?php echo htmlentities((string)$farmer['id']); ?>"><?php echo htmlentities($farmer['name']); ?></option>
<?php } ?>
												</select>
											</div>
										</div>
<?php } ?>
										<div class="control-group">
											<label class="control-label" for="harvest_date">Harvest Date</label>
											<div class="controls">
												<input type="date" id="harvest_date" name="harvest_date" class="span10" required>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="quantity_kg">Quantity (kg)</label>
											<div class="controls">
												<input type="number" id="quantity_kg" name="quantity_kg" class="span10" placeholder="e.g. 1200.50" min="0.01" step="0.01" required>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="initial_moisture">Initial Moisture (%)</label>
											<div class="controls">
												<input type="number" id="initial_moisture" name="initial_moisture" class="span10" placeholder="e.g. 18.5" min="0" max="100" step="0.01">
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="remaining_qty_kg">Remaining Quantity (kg)</label>
											<div class="controls">
												<input type="number" id="remaining_qty_kg" name="remaining_qty_kg" class="span10" placeholder="Leave empty to use Quantity (kg)" min="0" step="0.01">
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="drying_method">Drying Method</label>
											<div class="controls">
												<select id="drying_method" name="drying_method" class="span10" required>
													<option value="">Select Method</option>
													<option value="Sun Drying">Sun Drying</option>
													<option value="Mechanical Dryer">Mechanical Dryer</option>
													<option value="Raised Bed Drying">Raised Bed Drying</option>
													<option value="Hybrid Method">Hybrid Method</option>
												</select>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="sorting_quality_score">Sorting Quality Score</label>
											<div class="controls">
												<input type="number" id="sorting_quality_score" name="sorting_quality_score" class="span10" placeholder="e.g. 85" min="0" max="100" step="0.01">
											</div>
										</div>
										<div class="control-group form-actions-wrap">
											<div class="controls">
												<button type="submit" class="btn btn-primary">Save Batch</button>
												<a href="<?php echo appUrl('/farmers/batches.php'); ?>" class="btn">View Batch Records</a>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php include('../admin/include/footer.php'); ?>
	<script src="../admin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="../admin/scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="../admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script>
	(function () {
		var form = document.getElementById('batchForm');
		if (!form) {
			return;
		}

		var durationMap = {
			'Sun Drying': '5 days',
			'Mechanical Dryer': '12 hours',
			'Raised Bed Drying': '2 days',
			'Hybrid Method': '24 hours'
		};

		form.addEventListener('submit', function (event) {
			var dryingMethodField = document.getElementById('drying_method');
			var selectedMethod = dryingMethodField ? dryingMethodField.value : '';
			var predictedTime = durationMap[selectedMethod] || '';

			if (!predictedTime) {
				alert('Please select a drying method.');
				event.preventDefault();
				return;
			}

			var confirmMessage = 'Your predicted drying time is ' + predictedTime + '. You will be redirected to the batches page.';
			if (!window.confirm(confirmMessage)) {
				event.preventDefault();
			}
		});
	})();
	</script>
</body>
</html>
