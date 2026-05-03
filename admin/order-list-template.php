<?php
$orders = array();
$sql = "SELECT mo.id, mo.quantity, mo.unit_price, mo.shipping_charge, mo.order_status, mo.order_date,
        users.name AS username, users.email AS useremail, users.contactno AS usercontact,
        users.shippingAddress AS shippingaddress, users.shippingCity AS shippingcity,
        users.shippingState AS shippingstate, users.shippingPincode AS shippingpincode,
        mp.product_name AS productname, farmers.name AS farmer_name
    FROM marketplace_orders mo
    INNER JOIN users ON users.id = mo.user_id
    INNER JOIN marketplace_products mp ON mp.id = mo.product_id
    INNER JOIN farmers ON farmers.id = mo.farmer_id
    " . $where . "
    ORDER BY mo.order_date DESC";
$query = mysqli_query($con, $sql);
while ($query && ($row = mysqli_fetch_assoc($query))) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin | <?php echo htmlentities($pageTitle); ?></title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css?v=side-rail-2" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
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
							<div class="module-head"><h3><?php echo htmlentities($pageTitle); ?></h3></div>
							<div class="module-body table">
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display table-responsive">
									<thead>
										<tr>
											<th>#</th>
											<th>Customer</th>
											<th>Email / Contact</th>
											<th>Shipping Address</th>
											<th>Product</th>
											<th>Farmer</th>
											<th>Qty</th>
											<th>Amount</th>
											<th>Status</th>
											<th>Order Date</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
<?php $cnt = 1; foreach ($orders as $row) { ?>
										<tr>
											<td><?php echo htmlentities($cnt); ?></td>
											<td><?php echo htmlentities($row['username']); ?></td>
											<td><?php echo htmlentities($row['useremail']); ?> / <?php echo htmlentities($row['usercontact']); ?></td>
											<td><?php echo htmlentities($row['shippingaddress'] . ', ' . $row['shippingcity'] . ', ' . $row['shippingstate'] . '-' . $row['shippingpincode']); ?></td>
											<td><?php echo htmlentities($row['productname']); ?></td>
											<td><?php echo htmlentities($row['farmer_name']); ?></td>
											<td><?php echo htmlentities($row['quantity']); ?></td>
											<td><?php echo htmlentities(formatMarketMoney(((float)$row['unit_price'] * (int)$row['quantity']) + (float)$row['shipping_charge'])); ?></td>
											<td><?php echo htmlentities($row['order_status'] ? $row['order_status'] : 'Pending'); ?></td>
											<td><?php echo htmlentities($row['order_date']); ?></td>
											<td><a href="updateorder.php?oid=<?php echo htmlentities($row['id']); ?>" title="Update order" target="_blank"><i class="icon-edit"></i></a></td>
										</tr>
<?php $cnt++; } ?>
<?php if (empty($orders)) { ?>
										<tr><td colspan="11">No orders found.</td></tr>
<?php } ?>
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
			$('.datatable-1').dataTable();
		});
	</script>
</body>
</html>
