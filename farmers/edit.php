<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
requireAdmin(appUrl('/admin/index.php'));

$activePage = 'farmers';
$pageError = '';

$farmerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($farmerId <= 0) {
    redirectWithFlash(appUrl('/farmers/index.php'), 'error', 'Invalid farmer selected.', 'farmers');
}

$farmer = null;
if ($con) {
    $stmt = mysqli_prepare($con, "SELECT id, name, location, phone, farm_size, group_membership FROM farmers WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $farmerId);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $farmer = mysqli_fetch_assoc($result);
            }
            if (!$farmer) {
                $pageError = 'Farmer record not found.';
            }
        } else {
            $pageError = 'Unable to fetch farmer details.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $pageError = 'Unable to prepare farmer lookup query.';
    }
} else {
    $pageError = 'Database connection is not available.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin | Edit Farmer</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=side-rail-2" rel="stylesheet">
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
								<h3>Edit Farmer</h3>
							</div>
							<div class="module-body">
<?php if ($pageError !== '') { ?>
								<div class="alert alert-error">
									<button type="button" class="close" data-dismiss="alert">x</button>
									<?php echo htmlentities($pageError); ?>
								</div>
								<a href="<?php echo appUrl('/farmers/index.php'); ?>" class="btn">Back to Farmers List</a>
<?php } elseif ($farmer) { ?>
								<form class="form-horizontal row-fluid" action="<?php echo appUrl('/admin/update_farmer.php'); ?>" method="post" novalidate>
									<input type="hidden" name="id" value="<?php echo htmlentities((string)$farmer['id']); ?>">
									<div class="control-group">
										<label class="control-label" for="name">Farmer Name</label>
										<div class="controls">
											<input type="text" id="name" name="name" class="span8" value="<?php echo htmlentities($farmer['name']); ?>" required maxlength="100">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="phone">Phone Number</label>
										<div class="controls">
											<input type="text" id="phone" name="phone" class="span8" value="<?php echo htmlentities($farmer['phone']); ?>" required maxlength="20" pattern="[0-9+\-\s()]{7,20}">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="location">Location</label>
										<div class="controls">
											<input type="text" id="location" name="location" class="span8" value="<?php echo htmlentities($farmer['location']); ?>" required maxlength="150">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="farm_size">Farm Size (Acres)</label>
										<div class="controls">
											<input type="number" id="farm_size" name="farm_size" class="span8" value="<?php echo ($farmer['farm_size'] !== null && $farmer['farm_size'] !== '') ? htmlentities((string)$farmer['farm_size']) : ''; ?>" min="0" max="99999999.99" step="0.01">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="group_membership">Group Membership</label>
										<div class="controls">
											<input type="text" id="group_membership" name="group_membership" class="span8" value="<?php echo htmlentities((string)$farmer['group_membership']); ?>" maxlength="100">
										</div>
									</div>
									<div class="control-group">
										<div class="controls">
											<button type="submit" class="btn btn-primary">Update Farmer</button>
											<a href="<?php echo appUrl('/farmers/index.php'); ?>" class="btn">Back to Farmers List</a>
										</div>
									</div>
								</form>
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
</body>
</html>
