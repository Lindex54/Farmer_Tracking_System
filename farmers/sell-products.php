<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
require_once __DIR__ . '/../includes/farmer-product-helpers.php';
requireAdminOrFarmer(appUrl('/farmers/login.php'));

if (!function_exists('isFarmer') || !isFarmer()) {
    redirectWithFlash(appUrl('/farmers/login.php'), 'error', 'Please sign in as a farmer before submitting products for sale.', 'farmer_login');
}

$activePage = 'sell-products';
$pageError = '';
$message = '';
$messageType = 'success';
$currentFarmerId = (function_exists('isFarmer') && isFarmer() && !empty($_SESSION['farmer_id'])) ? (int)$_SESSION['farmer_id'] : 0;

if (!$con || !ensureFarmerProductTables($con)) {
    $pageError = 'Unable to prepare product selling tables.';
} elseif ($currentFarmerId <= 0) {
    $pageError = 'Your farmer account could not be identified. Please sign in again.';
}

function farmer_product_clean($value)
{
    return trim(strip_tags((string)$value));
}

function farmer_product_upload($fieldName, &$error)
{
    if (empty($_FILES[$fieldName]['name'])) {
        $error = 'Please choose a product image.';
        return '';
    }

    if (!empty($_FILES[$fieldName]['error'])) {
        $error = 'The image upload failed. Please try again.';
        return '';
    }

    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $name = (string)$_FILES[$fieldName]['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        $error = 'Product image must be JPG, PNG, GIF, or WEBP.';
        return '';
    }

    $baseDir = dirname(__DIR__) . '/uploads/farmer-products';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $fileName = 'product-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $target = $baseDir . '/' . $fileName;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        $error = 'Unable to save product image.';
        return '';
    }

    return 'uploads/farmer-products/' . $fileName;
}

if ($pageError === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = farmer_product_clean(isset($_POST['product_name']) ? $_POST['product_name'] : '');
    $description = trim((string)(isset($_POST['description']) ? $_POST['description'] : ''));
    $unitLabel = farmer_product_clean(isset($_POST['unit_label']) ? $_POST['unit_label'] : 'kg');
    $quantity = isset($_POST['quantity_available']) ? (float)$_POST['quantity_available'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $uploadError = '';
    $imagePath = farmer_product_upload('product_image', $uploadError);

    if ($productName === '') {
        $message = 'Product name is required.';
        $messageType = 'error';
    } elseif ($quantity <= 0 || $price <= 0) {
        $message = 'Quantity and price must be greater than zero.';
        $messageType = 'error';
    } elseif ($uploadError !== '') {
        $message = $uploadError;
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($con, "INSERT INTO farmer_products (farmer_id, product_name, description, unit_label, quantity_available, price, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssdds', $currentFarmerId, $productName, $description, $unitLabel, $quantity, $price, $imagePath);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = $ok ? 'Product submitted. Admin can now publish it to the marketplace.' : 'Failed to submit product.';
            $messageType = $ok ? 'success' : 'error';
        } else {
            $message = 'Unable to prepare product submission.';
            $messageType = 'error';
        }
    }
}

$products = array();
$sales = array();
if ($pageError === '') {
    $stmt = mysqli_prepare($con, "SELECT * FROM farmer_products WHERE farmer_id = ? ORDER BY submitted_at DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $currentFarmerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $products[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($con, "SELECT mo.*, mp.product_name, u.name AS customer_name, u.email AS customer_email, u.contactno AS customer_phone
        FROM marketplace_orders mo
        INNER JOIN marketplace_products mp ON mp.id = mo.product_id
        LEFT JOIN users u ON u.id = mo.user_id
        WHERE mo.farmer_id = ?
        ORDER BY mo.order_date DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $currentFarmerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $sales[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sell Products</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css?v=sell-products-1" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
	<style>
		.sell-grid { display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 16px; }
		.product-thumb { width: 74px; height: 58px; object-fit: cover; border-radius: 6px; border: 1px solid #d8e5dc; }
		.status-pill { display: inline-block; padding: 3px 8px; border-radius: 999px; background: #eaf2ec; color: #2f6848; font-size: 12px; font-weight: 700; text-transform: capitalize; }
		.status-pending { background: #fff4d9; color: #8a5a00; }
		.status-rejected { background: #f8dddd; color: #8a2828; }
		.status-published, .status-approved { background: #ddf3e5; color: #276b3e; }
		@media (max-width: 979px) { .sell-grid { grid-template-columns: 1fr; } }
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
<?php } ?>
<?php if ($message !== '') { ?>
						<div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlentities($message); ?></div>
<?php } ?>
						<div class="sell-grid">
							<div class="module">
								<div class="module-head"><h3>Sell Product</h3></div>
								<div class="module-body">
									<form method="post" enctype="multipart/form-data" class="form-vertical">
										<label>Product Name</label>
										<input type="text" name="product_name" class="span12" required>
										<label>Description</label>
										<textarea name="description" rows="4" class="span12"></textarea>
										<label>Unit</label>
										<select name="unit_label" class="span12">
											<option value="kg">kg</option>
											<option value="bag">bag</option>
											<option value="sack">sack</option>
											<option value="piece">piece</option>
										</select>
										<label>Available Quantity</label>
										<input type="number" name="quantity_available" min="0.01" step="0.01" class="span12" required>
										<label>Price Per Unit (UGX)</label>
										<input type="number" name="price" min="1" step="1" class="span12" required>
										<label>Product Image</label>
										<input type="file" name="product_image" class="span12" accept="image/*" required>
										<button type="submit" class="btn btn-primary">Submit for Admin Publishing</button>
									</form>
								</div>
							</div>
							<div class="module">
								<div class="module-head"><h3>My Product Submissions</h3></div>
								<div class="module-body table">
									<table class="table table-bordered table-striped">
										<thead><tr><th>Image</th><th>Product</th><th>Qty</th><th>Price</th><th>Status</th></tr></thead>
										<tbody>
<?php foreach ($products as $product) { ?>
											<tr>
												<td><img class="product-thumb" src="../<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt=""></td>
												<td><?php echo htmlentities($product['product_name']); ?></td>
												<td><?php echo htmlentities(number_format((float)$product['quantity_available'], 2) . ' ' . $product['unit_label']); ?></td>
												<td><?php echo htmlentities(formatMarketMoney($product['price'])); ?></td>
												<td><span class="status-pill status-<?php echo htmlentities($product['status']); ?>"><?php echo htmlentities($product['status']); ?></span></td>
											</tr>
<?php } ?>
<?php if (empty($products)) { ?>
											<tr><td colspan="5">No products submitted yet.</td></tr>
<?php } ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="module">
							<div class="module-head"><h3>Sold Products and Customers</h3></div>
							<div class="module-body table">
								<table class="table table-bordered table-striped">
									<thead><tr><th>Product</th><th>Customer</th><th>Contact</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
									<tbody>
<?php foreach ($sales as $sale) { ?>
										<tr>
											<td><?php echo htmlentities($sale['product_name']); ?></td>
											<td><?php echo htmlentities($sale['customer_name'] ? $sale['customer_name'] : 'Customer'); ?></td>
											<td><?php echo htmlentities(($sale['customer_email'] ? $sale['customer_email'] : '') . ($sale['customer_phone'] ? ' / ' . $sale['customer_phone'] : '')); ?></td>
											<td><?php echo (int)$sale['quantity']; ?></td>
											<td><?php echo htmlentities(formatMarketMoney(((float)$sale['unit_price'] * (int)$sale['quantity']) + (float)$sale['shipping_charge'])); ?></td>
											<td><?php echo htmlentities($sale['order_status'] ? $sale['order_status'] : 'Pending'); ?></td>
											<td><?php echo htmlentities($sale['order_date']); ?></td>
										</tr>
<?php } ?>
<?php if (empty($sales)) { ?>
										<tr><td colspan="7">No customer orders for your products yet.</td></tr>
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
<?php include('../admin/include/footer.php'); ?>
	<script src="../admin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="../admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
