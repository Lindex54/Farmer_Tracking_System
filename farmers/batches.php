<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');

$activePage = 'farmers';
$pageError = '';
$batches = array();

if ($con) {
    $stmt = mysqli_prepare(
        $con,
        "SELECT b.batch_id, b.farmer_id, f.name AS farmer_name, b.harvest_date, b.quantity_kg, b.initial_moisture, b.remaining_qty_kg, b.drying_method, b.sorting_quality_score, b.created_at
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

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$message = isset($_GET['message']) ? trim($_GET['message']) : '';
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
	<link type="text/css" href="../admin/css/theme.css" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.new-batch-row { background-color: #f5faf4 !important; }
	</style>
</head>
<body>
<?php include('../admin/include/header.php'); ?>
	<div class="wrapper">
		<div class="container">
			<div class="row">
				<?php include('../admin/include/sidebar.php'); ?>
				<div class="span9">
					<div class="content">
						<div class="module">
							<div class="module-head">
								<h3 style="display:inline-block;">Batch Records</h3>
								<a href="<?php echo appUrl('/farmers/add_batch.php'); ?>" class="btn btn-primary pull-right">Add New Batch</a>
							</div>
							<div class="module-body table">
<?php if ($message !== '') { ?>
								<div class="alert <?php echo ($status === 'success') ? 'alert-success' : 'alert-error'; ?>">
									<button type="button" class="close" data-dismiss="alert">x</button>
									<?php echo htmlentities($message); ?>
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
											<th>Created At</th>
										</tr>
									</thead>
									<tbody>
<?php foreach ($batches as $batch) { ?>
										<tr class="<?php echo ($newBatchId > 0 && (int)$batch['batch_id'] === $newBatchId) ? 'new-batch-row' : ''; ?>">
											<td><?php echo htmlentities((string)$batch['batch_id']); ?></td>
											<td><?php echo htmlentities($batch['farmer_name']); ?></td>
											<td><?php echo htmlentities($batch['harvest_date']); ?></td>
											<td><?php echo htmlentities(number_format((float)$batch['quantity_kg'], 2)); ?></td>
											<td><?php echo ($batch['initial_moisture'] !== null && $batch['initial_moisture'] !== '') ? htmlentities(number_format((float)$batch['initial_moisture'], 2)) : '-'; ?></td>
											<td><?php echo htmlentities(number_format((float)$batch['remaining_qty_kg'], 2)); ?></td>
											<td><?php echo ($batch['drying_method'] !== null && $batch['drying_method'] !== '') ? htmlentities($batch['drying_method']) : '-'; ?></td>
											<td><?php echo ($batch['sorting_quality_score'] !== null && $batch['sorting_quality_score'] !== '') ? htmlentities(number_format((float)$batch['sorting_quality_score'], 2)) : '-'; ?></td>
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
	</script>
</body>
</html>
