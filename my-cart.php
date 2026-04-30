<?php
session_start();
error_reporting(0);
include('includes/config.php');
require_once __DIR__ . '/includes/farmer-product-helpers.php';
require_once __DIR__ . '/admin/include/audit.php';
ensureFarmerProductTables($con);

if (isset($_POST['submit']) && !empty($_SESSION['cart'])) {
    foreach ($_POST['quantity'] as $key => $val) {
        $productId = (int)$key;
        $qty = (int)$val;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId]['quantity'] = $qty;
        }
    }
}

if (isset($_POST['remove_code']) && !empty($_SESSION['cart'])) {
    foreach ($_POST['remove_code'] as $key) {
        unset($_SESSION['cart'][(int)$key]);
    }
}

if (isset($_POST['ordersubmit'])) {
    if (strlen($_SESSION['login']) == 0) {
        header('Location: login.php');
        exit();
    }

    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productId => $item) {
            $productId = (int)$productId;
            $quantity = max(1, (int)$item['quantity']);
            $product = dbFetchOne($con, "SELECT id, farmer_id, price FROM marketplace_products WHERE id = ? AND status = 'published' LIMIT 1", 'i', array($productId));
            if ($product) {
                dbExecute(
                    $con,
                    "INSERT INTO marketplace_orders (user_id, product_id, farmer_id, quantity, unit_price, shipping_charge) VALUES (?, ?, ?, ?, ?, 0)",
                    'iiiid',
                    array((int)$_SESSION['id'], $productId, (int)$product['farmer_id'], $quantity, (float)$product['price'])
                );
                writeAuditLog($con, 'user', !empty($_SESSION['username']) ? $_SESSION['username'] : $_SESSION['login'], 'market_order_created', 'success', 'Customer placed marketplace order for product ID ' . $productId . '.');
            }
        }
        unset($_SESSION['cart']);
    }

    header('Location: order-history.php');
    exit();
}

$cartProducts = array();
$totalPrice = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $ids = array_filter($ids);
    if (!empty($ids)) {
        $sql = "SELECT mp.*, f.name AS farmer_name FROM marketplace_products mp INNER JOIN farmers f ON f.id = mp.farmer_id WHERE mp.id IN(" . implode(',', $ids) . ") AND mp.status = 'published' ORDER BY mp.id ASC";
        $result = mysqli_query($con, $sql);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $quantity = isset($_SESSION['cart'][$row['id']]['quantity']) ? (int)$_SESSION['cart'][$row['id']]['quantity'] : 1;
            $row['cart_quantity'] = max(1, $quantity);
            $row['cart_total'] = $row['cart_quantity'] * (float)$row['price'];
            $totalPrice += $row['cart_total'];
            $cartProducts[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<title>My Cart</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<style>.cart-img { width: 90px; height: 70px; object-fit: cover; border-radius: 6px; }</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>
<div class="body-content outer-top-xs">
	<div class="container">
		<form method="post">
<?php if (!empty($cartProducts)) { ?>
			<div class="table-responsive">
				<table class="table table-bordered">
					<thead><tr><th>Remove</th><th>Image</th><th>Product</th><th>Farmer</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
					<tbody>
<?php foreach ($cartProducts as $product) { ?>
						<tr>
							<td><input type="checkbox" name="remove_code[]" value="<?php echo (int)$product['id']; ?>"></td>
							<td><img class="cart-img" src="<?php echo htmlentities(marketplaceImageUrl($product['image_path'])); ?>" alt=""></td>
							<td><a href="product-details.php?pid=<?php echo (int)$product['id']; ?>"><?php echo htmlentities($product['product_name']); ?></a></td>
							<td><?php echo htmlentities($product['farmer_name']); ?></td>
							<td><input type="number" min="1" name="quantity[<?php echo (int)$product['id']; ?>]" value="<?php echo (int)$product['cart_quantity']; ?>" style="width:80px;"></td>
							<td><?php echo htmlentities(formatMarketMoney($product['price'])); ?></td>
							<td><?php echo htmlentities(formatMarketMoney($product['cart_total'])); ?></td>
						</tr>
<?php } ?>
					</tbody>
				</table>
			</div>
			<div class="cart-shopping-total pull-right" style="max-width:360px;">
				<table class="table table-bordered">
					<tr><th>Grand Total</th><td><?php echo htmlentities(formatMarketMoney($totalPrice)); ?></td></tr>
					<tr><td colspan="2">
						<button type="submit" name="submit" class="btn btn-primary">Update Cart</button>
						<button type="submit" name="ordersubmit" class="btn btn-primary">Proceed to Checkout</button>
					</td></tr>
				</table>
			</div>
<?php } else { ?>
			<div class="alert alert-info">Your shopping cart is empty. <a href="index.php">Continue shopping</a>.</div>
<?php } ?>
		</form>
	</div>
</div>
<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
