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

$products = array();
$result = mysqli_query($con, "SELECT mp.*, f.name AS farmer_name, f.location AS farmer_location
    FROM marketplace_products mp
    INNER JOIN farmers f ON f.id = mp.farmer_id
    WHERE mp.status = 'published'
    ORDER BY mp.published_at DESC");
while ($result && ($row = mysqli_fetch_assoc($result))) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<title><?php echo APP_NAME; ?> | Farmer Marketplace</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<style>
		.market-hero { background: linear-gradient(135deg, #1f6f4a, #8fbf4d); color: #fff; padding: 42px 0; margin-bottom: 28px; }
		.market-hero h1 { color: #fff; font-size: 38px; line-height: 1.15; margin: 0 0 10px; }
		.market-hero p { max-width: 700px; font-size: 16px; margin: 0; color: rgba(255,255,255,.92); }
		.market-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
		.market-card { background: #fff; border: 1px solid #dfe9e2; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 26px rgba(25, 68, 43, 0.08); min-height: 100%; display: flex; flex-direction: column; }
		.market-card img { width: 100%; height: 210px; object-fit: cover; background: #eef4ef; }
		.market-card-body { padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
		.market-card h3 { font-size: 18px; line-height: 1.3; margin: 0; color: #193d2b; }
		.market-meta { color: #66766d; font-size: 13px; }
		.market-price { color: #2f7a4e; font-size: 20px; font-weight: 700; }
		.market-actions { margin-top: auto; display: flex; gap: 8px; flex-wrap: wrap; }
		.market-empty { background: #fff; border: 1px solid #dfe9e2; padding: 28px; border-radius: 8px; color: #52645a; }
		@media (max-width: 979px) { .market-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
		@media (max-width: 620px) { .market-grid { grid-template-columns: 1fr; } .market-hero h1 { font-size: 30px; } }
	</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<section class="market-hero">
	<div class="container">
		<h1>Buy fresh produce directly from trusted farmers</h1>
		<p>Browse available harvests, compare prices, add products to your cart, and order from farmers registered on FarmHub.</p>
	</div>
</section>

<div class="body-content">
	<div class="container">
		<div class="market-grid">
<?php foreach ($products as $product) { ?>
			<div class="market-card">
				<a href="product-details.php?pid=<?php echo (int)$product['id']; ?>">
					<img src="<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt="<?php echo htmlentities($product['product_name']); ?>">
				</a>
				<div class="market-card-body">
					<h3><a href="product-details.php?pid=<?php echo (int)$product['id']; ?>"><?php echo htmlentities($product['product_name']); ?></a></h3>
					<div class="market-meta">By <?php echo htmlentities($product['farmer_name']); ?><?php echo $product['farmer_location'] ? ' - ' . htmlentities($product['farmer_location']) : ''; ?></div>
					<div class="market-meta"><?php echo htmlentities(number_format((float)$product['quantity_available'], 2) . ' ' . $product['unit_label']); ?> available</div>
					<div class="market-price"><?php echo htmlentities(formatMarketMoney($product['price'])); ?></div>
					<div class="market-actions">
						<a href="product-details.php?pid=<?php echo (int)$product['id']; ?>" class="btn btn-default">View</a>
						<a href="index.php?action=add&id=<?php echo (int)$product['id']; ?>" class="btn btn-primary"><i class="fa fa-shopping-cart"></i> Add to Cart</a>
					</div>
				</div>
			</div>
<?php } ?>
		</div>
<?php if (empty($products)) { ?>
		<div class="market-empty">No farmer products are live yet. Once farmers submit products and admin publishes them, they will appear here.</div>
<?php } ?>
	</div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
