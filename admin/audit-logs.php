<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/include/audit.php';

requireAdmin(appUrl('/admin/index.php'));
ensureAuditLogTable($con);
$activePage = 'audit-logs';
$logs = mysqli_query($con, "SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Audit Logs</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css?v=side-rail-2" rel="stylesheet">
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
					<div class="content">
						<div class="module">
							<div class="module-head">
								<h3>Audit Logs</h3>
							</div>
							<div class="module-body table">
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
									<thead>
										<tr>
											<th>#</th>
											<th>Actor Type</th>
											<th>Actor</th>
											<th>Event</th>
											<th>Status</th>
											<th>Details</th>
											<th>Created</th>
										</tr>
									</thead>
									<tbody>
<?php
$cnt = 1;
if ($logs) {
    while ($row = mysqli_fetch_assoc($logs)) {
?>
										<tr>
											<td><?php echo htmlentities($cnt); ?></td>
											<td><?php echo htmlentities($row['actor_type']); ?></td>
											<td><?php echo htmlentities($row['actor_identifier']); ?></td>
											<td><?php echo htmlentities($row['event_type']); ?></td>
											<td><?php echo htmlentities($row['status']); ?></td>
											<td><?php echo htmlentities($row['details']); ?></td>
											<td><?php echo htmlentities($row['created_at']); ?></td>
										</tr>
<?php
        $cnt++;
    }
}
?>
									</tbody>
								</table>
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
	<script src="scripts/datatables/jquery.dataTables.js"></script>
	<script>
		$(document).ready(function() {
			$('.datatable-1').dataTable({
				"order": [[6, "desc"]]
			});
		});
	</script>
</body>
</html>
