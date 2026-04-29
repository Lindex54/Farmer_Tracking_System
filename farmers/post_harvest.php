<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
require_once __DIR__ . '/../includes/post-harvest-helpers.php';
requireAdminOrFarmer(appUrl('/farmers/login.php'));

$activePage = 'post-harvest';
$pageError = '';
$batches = array();
$stageRows = array();
$storageRows = array();
$qualityRows = array();
$flashMessage = pullFlashMessage('post_harvest');
$currentFarmerId = (function_exists('isFarmer') && isFarmer() && !empty($_SESSION['farmer_id'])) ? (int)$_SESSION['farmer_id'] : 0;

function ph_clean_text($value)
{
    return trim(strip_tags((string)$value));
}

function ph_is_valid_date($date, $allowEmpty = false)
{
    if ($allowEmpty && trim((string)$date) === '') {
        return true;
    }
    $dt = DateTime::createFromFormat('Y-m-d', (string)$date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function ph_redirect($status, $message)
{
    redirectWithFlash(appUrl('/farmers/post_harvest.php'), $status, $message, 'post_harvest');
}

function ph_batch_allowed($con, $batchId)
{
    if (!$con || $batchId <= 0) {
        return false;
    }

    if (function_exists('isFarmer') && isFarmer()) {
        $farmerId = !empty($_SESSION['farmer_id']) ? (int)$_SESSION['farmer_id'] : 0;
        if ($farmerId <= 0) {
            return false;
        }
        $stmt = mysqli_prepare($con, "SELECT batch_id FROM batches WHERE batch_id = ? AND farmer_id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $batchId, $farmerId);
    } else {
        $stmt = mysqli_prepare($con, "SELECT batch_id FROM batches WHERE batch_id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'i', $batchId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ? true : false;
}

function ph_nullable_number($value, $label, $min = null, $max = null)
{
    $value = ph_clean_text($value);
    if ($value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        ph_redirect('error', $label . ' must be a valid number.');
    }
    $number = (float)$value;
    if ($min !== null && $number < $min) {
        ph_redirect('error', $label . ' is below the allowed range.');
    }
    if ($max !== null && $number > $max) {
        ph_redirect('error', $label . ' is above the allowed range.');
    }
    return number_format($number, 2, '.', '');
}

if (!$con) {
    $pageError = 'Database connection is not available.';
} elseif (!ensurePostHarvestTables($con)) {
    $pageError = 'Unable to prepare post-harvest tables.';
}

if ($pageError === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = ph_clean_text(isset($_POST['form_type']) ? $_POST['form_type'] : '');
    $batchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;

    if (!ph_batch_allowed($con, $batchId)) {
        ph_redirect('error', 'Selected batch is not available for this account.');
    }

    if ($formType === 'stage') {
        $stageMethods = postHarvestStageMethods();
        $stageType = ph_clean_text(isset($_POST['stage_type']) ? $_POST['stage_type'] : '');
        $startDate = ph_clean_text(isset($_POST['start_date']) ? $_POST['start_date'] : '');
        $endDate = ph_clean_text(isset($_POST['end_date']) ? $_POST['end_date'] : '');
        $method = ph_clean_text(isset($_POST['method']) ? $_POST['method'] : '');
        $resultScore = ph_nullable_number(isset($_POST['result_score']) ? $_POST['result_score'] : '', 'Result score', 0, 100);

        if (!isset($stageMethods[$stageType])) {
            ph_redirect('error', 'Please choose a valid stage type.');
        }
        if (!in_array($method, $stageMethods[$stageType], true)) {
            ph_redirect('error', 'Please choose a valid method for the selected stage.');
        }
        if (!ph_is_valid_date($startDate) || !ph_is_valid_date($endDate, true)) {
            ph_redirect('error', 'Please enter valid stage dates.');
        }
        if ($endDate !== '' && $endDate < $startDate) {
            ph_redirect('error', 'Stage end date cannot be before the start date.');
        }

        $stmt = mysqli_prepare($con, "INSERT INTO post_harvest_stages (batch_id, stage_type, start_date, end_date, method, result_score) VALUES (?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''))");
        if (!$stmt) {
            ph_redirect('error', 'Unable to prepare stage record.');
        }
        mysqli_stmt_bind_param($stmt, 'isssss', $batchId, $stageType, $startDate, $endDate, $method, $resultScore);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        ph_redirect($ok ? 'success' : 'error', $ok ? 'Post-harvest stage saved.' : 'Failed to save post-harvest stage.');
    }

    if ($formType === 'storage') {
        $storageType = ph_clean_text(isset($_POST['storage_type']) ? $_POST['storage_type'] : '');
        $startDate = ph_clean_text(isset($_POST['start_date']) ? $_POST['start_date'] : '');
        $endDate = ph_clean_text(isset($_POST['end_date']) ? $_POST['end_date'] : '');
        $moistureLevel = ph_nullable_number(isset($_POST['moisture_level']) ? $_POST['moisture_level'] : '', 'Moisture level', 0, 100);
        $temperature = ph_nullable_number(isset($_POST['temperature']) ? $_POST['temperature'] : '', 'Temperature');
        $pestLevel = ph_clean_text(isset($_POST['pest_infestation_level']) ? $_POST['pest_infestation_level'] : '');

        if (!in_array($storageType, postHarvestStorageTypes(), true)) {
            ph_redirect('error', 'Please choose a valid storage type.');
        }
        if (!in_array($pestLevel, postHarvestPestLevels(), true)) {
            ph_redirect('error', 'Please choose a valid pest infestation level.');
        }
        if (!ph_is_valid_date($startDate) || !ph_is_valid_date($endDate, true)) {
            ph_redirect('error', 'Please enter valid storage dates.');
        }
        if ($endDate !== '' && $endDate < $startDate) {
            ph_redirect('error', 'Storage end date cannot be before the start date.');
        }

        $stmt = mysqli_prepare($con, "INSERT INTO storage_records (batch_id, storage_type, start_date, end_date, moisture_level, temperature, pest_infestation_level) VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)");
        if (!$stmt) {
            ph_redirect('error', 'Unable to prepare storage record.');
        }
        mysqli_stmt_bind_param($stmt, 'issssss', $batchId, $storageType, $startDate, $endDate, $moistureLevel, $temperature, $pestLevel);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        ph_redirect($ok ? 'success' : 'error', $ok ? 'Storage record saved.' : 'Failed to save storage record.');
    }

    if ($formType === 'quality') {
        $testDate = ph_clean_text(isset($_POST['test_date']) ? $_POST['test_date'] : '');
        $moldPresence = isset($_POST['mold_presence']) && (string)$_POST['mold_presence'] === '1' ? 1 : 0;
        $aflatoxinReading = ph_nullable_number(isset($_POST['aflatoxin_reading']) ? $_POST['aflatoxin_reading'] : '', 'Aflatoxin reading', 0);
        $notes = trim((string)(isset($_POST['notes']) ? $_POST['notes'] : ''));

        if (!ph_is_valid_date($testDate)) {
            ph_redirect('error', 'Please enter a valid quality test date.');
        }
        if (strlen($notes) > 2000) {
            ph_redirect('error', 'Quality notes are too long.');
        }

        $stmt = mysqli_prepare($con, "INSERT INTO quality_logs (batch_id, test_date, mold_presence, aflatoxin_reading, notes) VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))");
        if (!$stmt) {
            ph_redirect('error', 'Unable to prepare quality log.');
        }
        mysqli_stmt_bind_param($stmt, 'isiss', $batchId, $testDate, $moldPresence, $aflatoxinReading, $notes);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        ph_redirect($ok ? 'success' : 'error', $ok ? 'Quality log saved.' : 'Failed to save quality log.');
    }

    ph_redirect('error', 'Unknown post-harvest form submitted.');
}

if ($pageError === '') {
    $batchSql = "SELECT b.batch_id, f.name AS farmer_name, b.harvest_date, b.drying_method
                 FROM batches b
                 INNER JOIN farmers f ON f.id = b.farmer_id";
    $recordWhere = '';
    if (function_exists('isFarmer') && isFarmer()) {
        if ($currentFarmerId <= 0) {
            $pageError = 'Your farmer account could not be identified. Please sign in again.';
        } else {
            $batchSql .= " WHERE b.farmer_id = ?";
            $recordWhere = " WHERE b.farmer_id = ?";
        }
    }
    $batchSql .= " ORDER BY b.batch_id DESC";

    if ($pageError === '') {
        $stmt = mysqli_prepare($con, $batchSql);
        if ($stmt) {
            if ($recordWhere !== '') {
                mysqli_stmt_bind_param($stmt, 'i', $currentFarmerId);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $batches[] = $row;
            }
            mysqli_stmt_close($stmt);
        }

        $queries = array(
            'stageRows' => "SELECT phs.*, b.harvest_date, f.name AS farmer_name FROM post_harvest_stages phs INNER JOIN batches b ON b.batch_id = phs.batch_id INNER JOIN farmers f ON f.id = b.farmer_id" . $recordWhere . " ORDER BY phs.id DESC",
            'storageRows' => "SELECT sr.*, b.harvest_date, f.name AS farmer_name FROM storage_records sr INNER JOIN batches b ON b.batch_id = sr.batch_id INNER JOIN farmers f ON f.id = b.farmer_id" . $recordWhere . " ORDER BY sr.id DESC",
            'qualityRows' => "SELECT ql.*, b.harvest_date, f.name AS farmer_name FROM quality_logs ql INNER JOIN batches b ON b.batch_id = ql.batch_id INNER JOIN farmers f ON f.id = b.farmer_id" . $recordWhere . " ORDER BY ql.id DESC"
        );

        foreach ($queries as $target => $sql) {
            $stmt = mysqli_prepare($con, $sql);
            if ($stmt) {
                if ($recordWhere !== '') {
                    mysqli_stmt_bind_param($stmt, 'i', $currentFarmerId);
                }
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                while ($result && ($row = mysqli_fetch_assoc($result))) {
                    ${$target}[] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$stageMethods = postHarvestStageMethods();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Post-Harvest Records</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.postharvest-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
		.postharvest-grid .module { margin-bottom: 0; }
		.postharvest-grid .module-body { padding: 18px; }
		.postharvest-grid .control-group { margin-bottom: 12px; }
		.postharvest-grid input, .postharvest-grid select, .postharvest-grid textarea { box-sizing: border-box; min-height: 34px; }
		.record-section { margin-top: 18px; }
		@media (max-width: 979px) { .postharvest-grid { grid-template-columns: 1fr; } }
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
<?php if ($flashMessage && !empty($flashMessage['message'])) { ?>
						<div class="alert <?php echo ($flashMessage['status'] === 'success') ? 'alert-success' : 'alert-error'; ?>">
							<button type="button" class="close" data-dismiss="alert">x</button>
							<?php echo htmlentities($flashMessage['message']); ?>
						</div>
<?php } ?>
<?php if ($pageError !== '') { ?>
						<div class="alert alert-error"><?php echo htmlentities($pageError); ?></div>
<?php } ?>
<?php if ($pageError === '' && empty($batches)) { ?>
						<div class="alert alert-info">No batches are available yet. Add a batch before recording post-harvest details.</div>
<?php } ?>
<?php if ($pageError === '' && !empty($batches)) { ?>
						<div class="postharvest-grid">
							<div class="module">
								<div class="module-head"><h3>Post-Harvest Stage</h3></div>
								<div class="module-body">
									<form method="post" class="form-vertical">
										<input type="hidden" name="form_type" value="stage">
										<label>Batch</label>
										<select name="batch_id" class="span12" required>
<?php foreach ($batches as $batch) { ?>
											<option value="<?php echo (int)$batch['batch_id']; ?>">Batch <?php echo (int)$batch['batch_id']; ?> - <?php echo htmlentities($batch['farmer_name']); ?></option>
<?php } ?>
										</select>
										<label>Stage Type</label>
										<select name="stage_type" id="stage_type" class="span12" required>
<?php foreach (array_keys($stageMethods) as $stageType) { ?>
											<option value="<?php echo htmlentities($stageType); ?>"><?php echo htmlentities($stageType); ?></option>
<?php } ?>
										</select>
										<label>Method</label>
										<select name="method" id="stage_method" class="span12" required></select>
										<label>Start Date</label>
										<input type="date" name="start_date" class="span12" required>
										<label>End Date</label>
										<input type="date" name="end_date" class="span12">
										<label>Result Score</label>
										<input type="number" name="result_score" class="span12" min="0" max="100" step="0.01">
										<button type="submit" class="btn btn-primary">Save Stage</button>
									</form>
								</div>
							</div>
							<div class="module">
								<div class="module-head"><h3>Storage</h3></div>
								<div class="module-body">
									<form method="post" class="form-vertical">
										<input type="hidden" name="form_type" value="storage">
										<label>Batch</label>
										<select name="batch_id" class="span12" required>
<?php foreach ($batches as $batch) { ?>
											<option value="<?php echo (int)$batch['batch_id']; ?>">Batch <?php echo (int)$batch['batch_id']; ?> - <?php echo htmlentities($batch['farmer_name']); ?></option>
<?php } ?>
										</select>
										<label>Storage Type</label>
										<select name="storage_type" class="span12" required>
<?php foreach (postHarvestStorageTypes() as $storageType) { ?>
											<option value="<?php echo htmlentities($storageType); ?>"><?php echo htmlentities($storageType); ?></option>
<?php } ?>
										</select>
										<label>Start Date</label>
										<input type="date" name="start_date" class="span12" required>
										<label>End Date</label>
										<input type="date" name="end_date" class="span12">
										<label>Moisture Level (%)</label>
										<input type="number" name="moisture_level" class="span12" min="0" max="100" step="0.01">
										<label>Temperature</label>
										<input type="number" name="temperature" class="span12" step="0.01">
										<label>Pest Level</label>
										<select name="pest_infestation_level" class="span12" required>
<?php foreach (postHarvestPestLevels() as $pestLevel) { ?>
											<option value="<?php echo htmlentities($pestLevel); ?>"><?php echo htmlentities($pestLevel); ?></option>
<?php } ?>
										</select>
										<button type="submit" class="btn btn-primary">Save Storage</button>
									</form>
								</div>
							</div>
							<div class="module">
								<div class="module-head"><h3>Quality Log</h3></div>
								<div class="module-body">
									<form method="post" class="form-vertical">
										<input type="hidden" name="form_type" value="quality">
										<label>Batch</label>
										<select name="batch_id" class="span12" required>
<?php foreach ($batches as $batch) { ?>
											<option value="<?php echo (int)$batch['batch_id']; ?>">Batch <?php echo (int)$batch['batch_id']; ?> - <?php echo htmlentities($batch['farmer_name']); ?></option>
<?php } ?>
										</select>
										<label>Test Date</label>
										<input type="date" name="test_date" class="span12" required>
										<label>Mold Presence</label>
										<select name="mold_presence" class="span12" required>
											<option value="0">No</option>
											<option value="1">Yes</option>
										</select>
										<label>Aflatoxin Reading</label>
										<input type="number" name="aflatoxin_reading" class="span12" min="0" step="0.01">
										<label>Notes</label>
										<textarea name="notes" class="span12" rows="3"></textarea>
										<button type="submit" class="btn btn-primary">Save Quality</button>
									</form>
								</div>
							</div>
						</div>

						<div class="module record-section">
							<div class="module-head"><h3>Post-Harvest Records</h3></div>
							<div class="module-body table">
								<table class="datatable-1 table table-bordered table-striped display" width="100%">
									<thead><tr><th>Batch</th><th>Farmer</th><th>Stage</th><th>Method</th><th>Start</th><th>End</th><th>Score</th></tr></thead>
									<tbody>
<?php foreach ($stageRows as $row) { ?>
										<tr><td><?php echo (int)$row['batch_id']; ?></td><td><?php echo htmlentities($row['farmer_name']); ?></td><td><?php echo htmlentities($row['stage_type']); ?></td><td><?php echo htmlentities($row['method']); ?></td><td><?php echo htmlentities($row['start_date']); ?></td><td><?php echo $row['end_date'] ? htmlentities($row['end_date']) : '-'; ?></td><td><?php echo $row['result_score'] !== null ? htmlentities(number_format((float)$row['result_score'], 2)) : '-'; ?></td></tr>
<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="module record-section">
							<div class="module-head"><h3>Storage Records</h3></div>
							<div class="module-body table">
								<table class="datatable-2 table table-bordered table-striped display" width="100%">
									<thead><tr><th>Batch</th><th>Farmer</th><th>Type</th><th>Start</th><th>End</th><th>Moisture</th><th>Temp</th><th>Pest</th></tr></thead>
									<tbody>
<?php foreach ($storageRows as $row) { ?>
										<tr><td><?php echo (int)$row['batch_id']; ?></td><td><?php echo htmlentities($row['farmer_name']); ?></td><td><?php echo htmlentities($row['storage_type']); ?></td><td><?php echo htmlentities($row['start_date']); ?></td><td><?php echo $row['end_date'] ? htmlentities($row['end_date']) : '-'; ?></td><td><?php echo $row['moisture_level'] !== null ? htmlentities(number_format((float)$row['moisture_level'], 2)) : '-'; ?></td><td><?php echo $row['temperature'] !== null ? htmlentities(number_format((float)$row['temperature'], 2)) : '-'; ?></td><td><?php echo htmlentities($row['pest_infestation_level']); ?></td></tr>
<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="module record-section">
							<div class="module-head"><h3>Quality Logs</h3></div>
							<div class="module-body table">
								<table class="datatable-3 table table-bordered table-striped display" width="100%">
									<thead><tr><th>Batch</th><th>Farmer</th><th>Test Date</th><th>Mold</th><th>Aflatoxin</th><th>Notes</th></tr></thead>
									<tbody>
<?php foreach ($qualityRows as $row) { ?>
										<tr><td><?php echo (int)$row['batch_id']; ?></td><td><?php echo htmlentities($row['farmer_name']); ?></td><td><?php echo htmlentities($row['test_date']); ?></td><td><?php echo ((int)$row['mold_presence'] === 1) ? 'Yes' : 'No'; ?></td><td><?php echo $row['aflatoxin_reading'] !== null ? htmlentities(number_format((float)$row['aflatoxin_reading'], 2)) : '-'; ?></td><td><?php echo $row['notes'] !== null && $row['notes'] !== '' ? htmlentities($row['notes']) : '-'; ?></td></tr>
<?php } ?>
									</tbody>
								</table>
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
	<script src="../admin/scripts/datatables/jquery.dataTables.js"></script>
	<script>
		$(document).ready(function() {
			$('.datatable-1, .datatable-2, .datatable-3').dataTable();
		});

		(function() {
			var forms = document.querySelectorAll('form.form-vertical');
			for (var i = 0; i < forms.length; i++) {
				forms[i].addEventListener('submit', function(event) {
					var startField = this.querySelector('input[name="start_date"]');
					var endField = this.querySelector('input[name="end_date"]');
					if (startField && endField && startField.value && endField.value && endField.value < startField.value) {
						alert('End date cannot be before the start date.');
						event.preventDefault();
					}
				});
			}
		})();

		(function() {
			var methodsByStage = <?php echo json_encode($stageMethods); ?>;
			var stageField = document.getElementById('stage_type');
			var methodField = document.getElementById('stage_method');

			function renderMethods() {
				var stage = stageField ? stageField.value : '';
				var methods = methodsByStage[stage] || [];
				methodField.innerHTML = '';
				for (var i = 0; i < methods.length; i++) {
					var option = document.createElement('option');
					option.value = methods[i];
					option.textContent = methods[i];
					methodField.appendChild(option);
				}
			}

			if (stageField && methodField) {
				stageField.addEventListener('change', renderMethods);
				renderMethods();
			}
		})();
	</script>
</body>
</html>
