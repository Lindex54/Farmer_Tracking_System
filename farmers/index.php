<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
requireAdmin(appUrl('/admin/index.php'));

$activePage = 'farmers';
$farmers = array();
$pageError = '';

if ($con) {
    $stmt = mysqli_prepare($con, "SELECT id, farmer_number, username, name, location, phone, farm_size, group_membership, created_at FROM farmers ORDER BY created_at DESC");
    if ($stmt) {
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
    } else {
        $pageError = 'Unable to prepare farmers list query.';
    }
} else {
    $pageError = 'Database connection is not available.';
}

$flashMessage = pullFlashMessage('farmers');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin | Manage Farmers</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
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
								<h3 style="display:inline-block;">Manage Farmers</h3>
								<a href="<?php echo appUrl('/farmers/create.php'); ?>" class="btn btn-primary pull-right">Add Farmer</a>
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
<?php if (!empty($farmers)) { ?>
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
									<thead>
										<tr>
											<th>#</th>
											<th>Farmer Number</th>
											<th>Username</th>
											<th>Name</th>
											<th>Location</th>
											<th>Phone</th>
											<th>Farm Size (Acres)</th>
											<th>Group Membership</th>
											<th>Created At</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
<?php
$cnt = 1;
foreach ($farmers as $farmer) {
?>
										<tr>
											<td><?php echo htmlentities($cnt); ?></td>
											<td><?php echo ($farmer['farmer_number'] !== null && $farmer['farmer_number'] !== '') ? htmlentities($farmer['farmer_number']) : '-'; ?></td>
											<td><?php echo ($farmer['username'] !== null && $farmer['username'] !== '') ? htmlentities($farmer['username']) : '-'; ?></td>
											<td><?php echo htmlentities($farmer['name']); ?></td>
											<td><?php echo htmlentities($farmer['location']); ?></td>
											<td><?php echo htmlentities($farmer['phone']); ?></td>
											<td><?php echo ($farmer['farm_size'] !== null && $farmer['farm_size'] !== '') ? htmlentities(number_format((float)$farmer['farm_size'], 2)) : '-'; ?></td>
											<td><?php echo ($farmer['group_membership'] !== null && $farmer['group_membership'] !== '') ? htmlentities($farmer['group_membership']) : '-'; ?></td>
											<td><?php echo htmlentities($farmer['created_at']); ?></td>
											<td><a href="<?php echo appUrl('/farmers/edit.php?id=' . urlencode((string)$farmer['id'])); ?>"><i class="icon-edit"></i> Edit</a></td>
										</tr>
<?php
    $cnt = $cnt + 1;
}
?>
									</tbody>
								</table>
<?php } else { ?>
								<div class="alert alert-info">No farmers found yet. Click "Add Farmer" to create the first record.</div>
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
