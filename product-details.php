<?php
session_start();
error_reporting(0);
include('includes/config.php');
require_once __DIR__ . '/includes/farmer-product-helpers.php';
ensureFarmerProductTables($con);

if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $id = (int)$_GET['id'];
    $product = dbFetchOne($con, "SELECT id, price FROM marketplace_products WHERE id = ? AND status = 'published' LIMIT 1", 'i', array($id));
    if ($product) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = array('quantity' => 1, 'price' => $product['price']);
        }
        header('Location: my-cart.php');
        exit();
    }
}

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$product = dbFetchOne($con, "SELECT mp.*, f.name AS farmer_name, f.location AS farmer_location, f.phone AS farmer_phone
    FROM marketplace_products mp
    INNER JOIN farmers f ON f.id = mp.farmer_id
    WHERE mp.id = ? AND mp.status = 'published'
    LIMIT 1", 'i', array($pid));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<title><?php echo $product ? htmlentities($product['product_name']) : 'Product Details'; ?></title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<style>
		.detail-shell { background: #fff; border: 1px solid #dfe9e2; border-radius: 8px; padding: 24px; margin: 28px 0; box-shadow: 0 10px 26px rgba(25,68,43,.08); }
		.detail-grid { display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 28px; align-items: start; }
		.detail-image { width: 100%; max-height: 470px; object-fit: cover; border-radius: 8px; background: #eef4ef; }
		.detail-title { color: #183d2a; font-size: 32px; line-height: 1.2; margin: 0 0 12px; }
		.detail-meta { color: #66766d; margin-bottom: 8px; }
		.detail-price { color: #2f7a4e; font-size: 28px; font-weight: 700; margin: 18px 0; }
		.detail-description { color: #34473c; font-size: 15px; line-height: 1.7; white-space: pre-wrap; }
		@media (max-width: 767px) { .detail-grid { grid-template-columns: 1fr; } }
	</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<div class="body-content">
	<div class="container">
<?php if (!$product) { ?>
		<div class="detail-shell">Product not found or not currently published.</div>
<?php } else { ?>
		<div class="detail-shell">
			<div class="detail-grid">
				<div>
					<img class="detail-image" src="<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt="<?php echo htmlentities($product['product_name']); ?>">
				</div>
				<div>
					<h1 class="detail-title"><?php echo htmlentities($product['product_name']); ?></h1>
					<div class="detail-meta">Sold by <?php echo htmlentities($product['farmer_name']); ?><?php echo $product['farmer_location'] ? ' - ' . htmlentities($product['farmer_location']) : ''; ?></div>
					<div class="detail-meta">Available: <?php echo htmlentities(number_format((float)$product['quantity_available'], 2) . ' ' . $product['unit_label']); ?></div>
					<div class="detail-meta">Status: <?php echo htmlentities($product['availability']); ?></div>
					<div class="detail-price"><?php echo htmlentities(formatMarketMoney($product['price'])); ?></div>
					<p class="detail-description"><?php echo htmlentities($product['description']); ?></p>
					<a href="product-details.php?action=add&id=<?php echo (int)$product['id']; ?>&pid=<?php echo (int)$product['id']; ?>" class="btn btn-primary btn-lg"><i class="fa fa-shopping-cart"></i> Add to Cart</a>
					<a href="index.php" class="btn btn-default btn-lg">Continue Shopping</a>
				</div>
			</div>
		</div>
<?php } ?>
	</div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
