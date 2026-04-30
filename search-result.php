<?php
session_start();
error_reporting(0);
include('includes/config.php');
require_once __DIR__ . '/includes/farmer-product-helpers.php';
ensureFarmerProductTables($con);

$term = isset($_POST['product']) ? trim((string)$_POST['product']) : '';
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $id = (int)$_GET['id'];
    $product = dbFetchOne($con, "SELECT id, price FROM marketplace_products WHERE id = ? AND status = 'published' LIMIT 1", 'i', array($id));
    if ($product) {
        $_SESSION['cart'][$id] = array('quantity' => isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['quantity'] + 1 : 1, 'price' => $product['price']);
        header('Location: my-cart.php');
        exit();
    }
}

$products = array();
if ($term !== '') {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($con, "SELECT mp.*, f.name AS farmer_name
        FROM marketplace_products mp
        INNER JOIN farmers f ON f.id = mp.farmer_id
        WHERE mp.status = 'published' AND mp.product_name LIKE ?
        ORDER BY mp.published_at DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $products[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<title>Search Products</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<style>.search-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 6px; }.search-card { margin-bottom: 22px; }</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>
<div class="body-content outer-top-xs">
	<div class="container">
		<h3>Search Results</h3>
		<div class="row">
<?php foreach ($products as $product) { ?>
			<div class="col-sm-6 col-md-4 search-card">
				<a href="product-details.php?pid=<?php echo (int)$product['id']; ?>"><img src="<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt=""></a>
				<h4><a href="product-details.php?pid=<?php echo (int)$product['id']; ?>"><?php echo htmlentities($product['product_name']); ?></a></h4>
				<p>By <?php echo htmlentities($product['farmer_name']); ?></p>
				<strong><?php echo htmlentities(formatMarketMoney($product['price'])); ?></strong><br>
				<a href="search-result.php?action=add&id=<?php echo (int)$product['id']; ?>" class="btn btn-primary">Add to Cart</a>
			</div>
<?php } ?>
<?php if (empty($products)) { ?>
			<div class="col-md-12"><div class="alert alert-info">No published farmer product found for "<?php echo htmlentities($term); ?>".</div></div>
<?php } ?>
		</div>
	</div>
</div>
<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
