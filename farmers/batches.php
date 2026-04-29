<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
requireAdminOrFarmer(appUrl('/farmers/login.php'));

$activePage = 'batches';
$pageError = '';
$batches = array();

if ($con) {
    mysqli_query($con, "UPDATE batches SET status='complete' WHERE status='drying' AND end_time IS NOT NULL AND end_time <= NOW()");

    $stmt = mysqli_prepare(
        $con,
        "SELECT b.batch_id, b.farmer_id, f.name AS farmer_name, b.harvest_date, b.quantity_kg, b.initial_moisture, b.remaining_qty_kg, b.drying_method, b.sorting_quality_score, b.start_time, b.end_time, b.status, b.created_at
         FROM batches b
         INNER JOIN farmers f ON f.id = b.farmer_id
         ORDER BY b.batch_id DESC"
    );

    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $batches[] = $row;
                }
            }
        } else {
            $pageError = 'Unable to fetch batch records at the moment.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $pageError = 'Unable to prepare batches query.';
    }
} else {
    $pageError = 'Database connection is not available.';
}

$flashMessage = pullFlashMessage('batches');
$newBatchId = isset($_GET['new_batch_id']) ? (int)$_GET['new_batch_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Batch Records</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=side-rail-2" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.new-batch-row { background-color: #f5faf4 !important; }
		.batch-complete-row { background-color: #eef7ea !important; }
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
						<div class="module">
							<div class="module-head">
								<h3 style="display:inline-block;">Batch Records</h3>
								<a href="<?php echo appUrl('/farmers/add_batch.php'); ?>" class="btn btn-primary pull-right">Add New Batch</a>
							</div>
							<div class="module-body table">
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
<?php if (!empty($batches)) { ?>
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
									<thead>
										<tr>
											<th>Batch ID</th>
											<th>Farmer</th>
											<th>Harvest Date</th>
											<th>Quantity (kg)</th>
											<th>Initial Moisture (%)</th>
											<th>Remaining Qty (kg)</th>
											<th>Drying Method</th>
											<th>Sorting Score</th>
											<th>Time Left to Dry</th>
											<th>Status</th>
											<th>Created At</th>
										</tr>
									</thead>
									<tbody>
<?php foreach ($batches as $batch) { ?>
<?php
    $rowClasses = array();
    if ($newBatchId > 0 && (int)$batch['batch_id'] === $newBatchId) {
        $rowClasses[] = 'new-batch-row';
    }
    if ($batch['status'] === 'complete') {
        $rowClasses[] = 'batch-complete-row';
    }
?>
										<tr class="<?php echo implode(' ', $rowClasses); ?>" data-batch-id="<?php echo (int)$batch['batch_id']; ?>" data-end-time="<?php echo htmlentities((string)$batch['end_time']); ?>" data-status="<?php echo htmlentities((string)$batch['status']); ?>">
											<td><?php echo htmlentities((string)$batch['batch_id']); ?></td>
											<td><?php echo htmlentities($batch['farmer_name']); ?></td>
											<td><?php echo htmlentities($batch['harvest_date']); ?></td>
											<td><?php echo htmlentities(number_format((float)$batch['quantity_kg'], 2)); ?></td>
											<td><?php echo ($batch['initial_moisture'] !== null && $batch['initial_moisture'] !== '') ? htmlentities(number_format((float)$batch['initial_moisture'], 2)) : '-'; ?></td>
											<td><?php echo htmlentities(number_format((float)$batch['remaining_qty_kg'], 2)); ?></td>
											<td><?php echo ($batch['drying_method'] !== null && $batch['drying_method'] !== '') ? htmlentities($batch['drying_method']) : '-'; ?></td>
											<td><?php echo ($batch['sorting_quality_score'] !== null && $batch['sorting_quality_score'] !== '') ? htmlentities(number_format((float)$batch['sorting_quality_score'], 2)) : '-'; ?></td>
											<td class="time-left-cell">-</td>
											<td class="status-cell"><?php echo htmlentities(ucfirst((string)$batch['status'])); ?></td>
											<td><?php echo htmlentities($batch['created_at']); ?></td>
										</tr>
<?php } ?>
									</tbody>
								</table>
<?php } else { ?>
								<div class="alert alert-info">No batch records found yet. Click "Add New Batch" to submit one.</div>
<?php } ?>
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
	<script src="../admin/scripts/datatables/jquery.dataTables.js"></script>
	<script>
		$(document).ready(function() {
			$('.datatable-1').dataTable();
			$('.dataTables_paginate').addClass("btn-group datatable-pagination");
			$('.dataTables_paginate > a').wrapInner('<span />');
			$('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
			$('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');
		});

		(function() {
			function parseMysqlDateTime(value) {
				if (!value) {
					return null;
				}
				var parts = value.split(/[- :]/);
				if (parts.length < 6) {
					return null;
				}
				return new Date(
					parseInt(parts[0], 10),
					parseInt(parts[1], 10) - 1,
					parseInt(parts[2], 10),
					parseInt(parts[3], 10),
					parseInt(parts[4], 10),
					parseInt(parts[5], 10)
				);
			}

			function formatTimeLeft(diffMs) {
				var totalMinutes = Math.floor(diffMs / (1000 * 60));
				var days = Math.floor(totalMinutes / (60 * 24));
				var hours = Math.floor((totalMinutes % (60 * 24)) / 60);
				var minutes = totalMinutes % 60;
				return days + ' days ' + hours + ' hours ' + minutes + ' minutes';
			}

			function markBatchComplete(batchId, row) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo appUrl('/admin/complete_batch.php'); ?>', true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4 && xhr.status === 200) {
						row.setAttribute('data-status', 'complete');
						row.classList.add('batch-complete-row');
						var statusCell = row.querySelector('.status-cell');
						if (statusCell) {
							statusCell.textContent = 'Complete';
						}
					}
				};
				xhr.send('batch_id=' + encodeURIComponent(batchId));
			}

			function updateCountdowns() {
				var rows = document.querySelectorAll('tr[data-batch-id]');
				var now = new Date();

				for (var i = 0; i < rows.length; i++) {
					var row = rows[i];
					var status = row.getAttribute('data-status');
					var endTime = parseMysqlDateTime(row.getAttribute('data-end-time'));
					var timeCell = row.querySelector('.time-left-cell');
					var statusCell = row.querySelector('.status-cell');

					if (!timeCell || !statusCell) {
						continue;
					}

					if (status === 'complete') {
						timeCell.textContent = 'Drying Complete';
						statusCell.textContent = 'Complete';
						row.classList.add('batch-complete-row');
						continue;
					}

					if (!endTime) {
						timeCell.textContent = '-';
						continue;
					}

					var diffMs = endTime.getTime() - now.getTime();
					if (diffMs <= 0) {
						timeCell.textContent = 'Drying Complete';
						statusCell.textContent = 'Complete';
						row.classList.add('batch-complete-row');
						row.setAttribute('data-status', 'complete');
						markBatchComplete(row.getAttribute('data-batch-id'), row);
					} else {
						timeCell.textContent = formatTimeLeft(diffMs);
						statusCell.textContent = 'Drying';
					}
				}
			}

			updateCountdowns();
			setInterval(updateCountdowns, 60000);
		})();
	</script>
</body>
</html>
