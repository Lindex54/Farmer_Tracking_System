<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
require_once __DIR__ . '/../includes/farmer-product-helpers.php';
require_once __DIR__ . '/include/audit.php';
requireAdmin(appUrl('/admin/index.php'));

$activePage = 'insert-product';
$pageError = '';
$message = '';
$messageType = 'success';

if (!$con || !ensureFarmerProductTables($con)) {
    $pageError = 'Unable to prepare marketplace product tables.';
}

function admin_redirect_products($status, $message)
{
    $_SESSION['market_product_msg'] = array('status' => $status, 'message' => $message);
    header('Location: ' . appUrl('/admin/insert-product.php'));
    exit();
}

if ($pageError === '' && isset($_GET['action'], $_GET['id'])) {
    $action = trim((string)$_GET['action']);
    $id = (int)$_GET['id'];

    $source = dbFetchOne($con, "SELECT * FROM farmer_products WHERE id = ? LIMIT 1", 'i', array($id));
    if (!$source) {
        admin_redirect_products('error', 'Farmer product could not be found.');
    }

    if ($action === 'publish') {
        $farmer = dbFetchOne($con, "SELECT id FROM farmers WHERE id = ? LIMIT 1", 'i', array((int)$source['farmer_id']));
        if (!$farmer) {
            admin_redirect_products('error', 'Assign this product to a valid farmer before publishing.');
        }

        $availability = ((float)$source['quantity_available'] > 0) ? 'In Stock' : 'Out of Stock';
        $ok = dbExecute(
            $con,
            "INSERT INTO marketplace_products (farmer_product_id, farmer_id, product_name, description, unit_label, quantity_available, price, image_path, availability, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
             ON DUPLICATE KEY UPDATE
                farmer_id = VALUES(farmer_id),
                product_name = VALUES(product_name),
                description = VALUES(description),
                unit_label = VALUES(unit_label),
                quantity_available = VALUES(quantity_available),
                price = VALUES(price),
                image_path = VALUES(image_path),
                availability = VALUES(availability),
                status = 'published',
                updated_at = CURRENT_TIMESTAMP",
            'iisssddss',
            array($source['id'], $source['farmer_id'], $source['product_name'], $source['description'], $source['unit_label'], $source['quantity_available'], $source['price'], $source['image_path'], $availability)
        );

        if ($ok) {
            dbExecute($con, "UPDATE farmer_products SET status = 'published', reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 'i', array($id));
            writeAuditLog($con, 'admin', !empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $_SESSION['alogin'], 'market_product_published', 'success', 'Published farmer product ID ' . $id . '.');
            admin_redirect_products('success', 'Product published to the customer marketplace.');
        }

        admin_redirect_products('error', 'Unable to publish product.');
    }

    if ($action === 'unpublish') {
        dbExecute($con, "UPDATE farmer_products SET status = 'pending', reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 'i', array($id));
        dbExecute($con, "UPDATE marketplace_products SET status = 'unpublished', updated_at = CURRENT_TIMESTAMP WHERE farmer_product_id = ?", 'i', array($id));
        writeAuditLog($con, 'admin', !empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $_SESSION['alogin'], 'market_product_unpublished', 'success', 'Unpublished farmer product ID ' . $id . '.');
        admin_redirect_products('success', 'Product removed from the homepage and returned to pending review.');
    }

    if ($action === 'reject') {
        dbExecute($con, "UPDATE farmer_products SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 'i', array($id));
        dbExecute($con, "UPDATE marketplace_products SET status = 'unpublished', updated_at = CURRENT_TIMESTAMP WHERE farmer_product_id = ?", 'i', array($id));
        writeAuditLog($con, 'admin', !empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $_SESSION['alogin'], 'market_product_rejected', 'success', 'Rejected farmer product ID ' . $id . '.');
        admin_redirect_products('success', 'Product rejected and removed from marketplace publishing.');
    }
}

if (!empty($_SESSION['market_product_msg'])) {
    $messageType = $_SESSION['market_product_msg']['status'];
    $message = $_SESSION['market_product_msg']['message'];
    unset($_SESSION['market_product_msg']);
}

$submissions = array();
if ($pageError === '') {
    $result = mysqli_query($con, "SELECT fp.*, f.name AS farmer_name, f.phone AS farmer_phone, f.location AS farmer_location
        FROM farmer_products fp
        LEFT JOIN farmers f ON f.id = fp.farmer_id
        ORDER BY FIELD(fp.status, 'pending', 'published', 'rejected'), fp.submitted_at DESC");
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $submissions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Publish Farmer Products</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<style>
		.product-review-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
		.review-card { background: #fff; border: 1px solid #dce7df; border-radius: 8px; overflow: hidden; box-shadow: 0 8px 20px rgba(20, 47, 31, 0.08); }
		.review-card img { width: 100%; height: 210px; object-fit: cover; display: block; background: #eef4ef; }
		.review-card-body { padding: 16px; }
		.review-title { margin: 0 0 8px; color: #173b2a; font-size: 18px; line-height: 1.3; }
		.review-meta { color: #5b6d63; margin-bottom: 8px; }
		.review-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
		.status-pill { display: inline-block; padding: 4px 9px; border-radius: 999px; background: #fff4d9; color: #8a5a00; font-weight: 700; text-transform: capitalize; }
		.status-published { background: #ddf3e5; color: #276b3e; }
		.status-rejected { background: #f8dddd; color: #8a2828; }
		@media (max-width: 979px) { .product-review-grid { grid-template-columns: 1fr; } }
	</style>
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
							<div class="module-head"><h3>Insert Product From Farmer Submissions</h3></div>
							<div class="module-body">
<?php if ($pageError !== '') { ?>
								<div class="alert alert-error"><?php echo htmlentities($pageError); ?></div>
<?php } ?>
<?php if ($message !== '') { ?>
								<div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlentities($message); ?></div>
<?php } ?>
								<div class="product-review-grid">
<?php foreach ($submissions as $product) { ?>
									<div class="review-card">
										<img src="../<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt="">
										<div class="review-card-body">
											<h4 class="review-title"><?php echo htmlentities($product['product_name']); ?></h4>
											<div class="review-meta">Farmer: <?php echo $product['farmer_name'] ? htmlentities($product['farmer_name']) : '<strong>Submitted farmer not found</strong>'; ?><?php echo $product['farmer_location'] ? ' | ' . htmlentities($product['farmer_location']) : ''; ?></div>
											<div class="review-meta">Qty: <?php echo htmlentities(number_format((float)$product['quantity_available'], 2) . ' ' . $product['unit_label']); ?> | Price: <?php echo htmlentities(formatMarketMoney($product['price'])); ?></div>
											<p><?php echo htmlentities($product['description']); ?></p>
											<span class="status-pill status-<?php echo htmlentities($product['status']); ?>"><?php echo htmlentities($product['status']); ?></span>
<?php if (empty($product['farmer_name'])) { ?>
											<div class="alert alert-error" style="margin-top:12px;">This old submission has no valid farmer attached. Ask the farmer to resubmit it from the farmer portal.</div>
<?php } ?>
											<div class="review-actions">
<?php if (!empty($product['farmer_name']) && $product['status'] === 'pending') { ?>
												<a class="btn btn-primary" href="<?php echo appUrl('/admin/insert-product.php?action=publish&id=' . (int)$product['id']); ?>">Publish to Homepage</a>
												<a class="btn" href="<?php echo appUrl('/admin/insert-product.php?action=reject&id=' . (int)$product['id']); ?>" onclick="return confirm('Reject this product?');">Reject</a>
<?php } elseif ($product['status'] === 'published') { ?>
												<a class="btn" href="<?php echo appUrl('/admin/insert-product.php?action=unpublish&id=' . (int)$product['id']); ?>" onclick="return confirm('Remove this product from the homepage?');">Unpublish</a>
<?php } elseif (!empty($product['farmer_name']) && $product['status'] === 'rejected') { ?>
												<a class="btn btn-primary" href="<?php echo appUrl('/admin/insert-product.php?action=publish&id=' . (int)$product['id']); ?>">Publish Anyway</a>
<?php } ?>
											</div>
										</div>
									</div>
<?php } ?>
<?php if (empty($submissions)) { ?>
									<div class="alert alert-info">No farmer products have been submitted yet.</div>
<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php include('include/footer.php'); ?>
	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
