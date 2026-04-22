<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
requireAdmin(appUrl('/admin/index.php'));

$activePage = 'farmers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin | Add Farmer</title>
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
								<h3>Add New Farmer</h3>
							</div>
							<div class="module-body">
								<form class="form-horizontal row-fluid" action="<?php echo appUrl('/admin/add_farmer.php'); ?>" method="post" novalidate>
									<div class="control-group">
										<label class="control-label" for="name">Farmer Name</label>
										<div class="controls">
											<input type="text" id="name" name="name" class="span8" placeholder="Enter full name" required maxlength="100">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="phone">Phone Number</label>
										<div class="controls">
											<input type="text" id="phone" name="phone" class="span8" placeholder="e.g. 0772123456" required maxlength="20" pattern="[0-9+\-\s()]{7,20}">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="location">Location</label>
										<div class="controls">
											<input type="text" id="location" name="location" class="span8" placeholder="District, Sub-county, Village" required maxlength="150">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="farm_size">Farm Size (Acres)</label>
										<div class="controls">
											<input type="number" id="farm_size" name="farm_size" class="span8" placeholder="e.g. 2.50" min="0" max="99999999.99" step="0.01">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="group_membership">Group Membership</label>
										<div class="controls">
											<input type="text" id="group_membership" name="group_membership" class="span8" placeholder="e.g. Bukedea Farmers SACCO" maxlength="100">
										</div>
									</div>
									<div class="control-group">
										<div class="controls">
											<button type="submit" class="btn btn-primary">Save Farmer</button>
											<a href="<?php echo appUrl('/farmers/index.php'); ?>" class="btn">Back to Farmers List</a>
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
<?php include('../admin/include/footer.php'); ?>
	<script src="../admin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="../admin/scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="../admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
