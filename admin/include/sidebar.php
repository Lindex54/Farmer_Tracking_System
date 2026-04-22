<?php
if (!function_exists('getAppBasePath')) {
    function getAppBasePath()
    {
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
        $basePath = preg_replace('#/(admin|farmers)(/.*)?$#', '', $scriptName);
        if (!is_string($basePath)) {
            return '';
        }
        $basePath = rtrim($basePath, '/');
        return $basePath === '/' ? '' : $basePath;
    }
}

if (!function_exists('appUrl')) {
    function appUrl($path)
    {
        $path = (string)$path;
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        return getAppBasePath() . $path;
    }
}

$currentScript = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$activePage = isset($activePage) ? $activePage : '';
$isDashboardPage = (strpos($currentScript, 'dashboard.php') !== false || $activePage === 'dashboard');

$isOrdersPage = (
    strpos($currentScript, 'todays-orders.php') !== false ||
    strpos($currentScript, 'pending-orders.php') !== false ||
    strpos($currentScript, 'delivered-orders.php') !== false
);

$isUsersPage = strpos($currentScript, 'manage-users.php') !== false;
$isCategoryPage = strpos($currentScript, 'category.php') !== false;
$isSubCategoryPage = strpos($currentScript, 'subcategory.php') !== false;
$isInsertProductPage = strpos($currentScript, 'insert-product.php') !== false;
$isManageProductsPage = strpos($currentScript, 'manage-products.php') !== false;
$isFarmersPage = (strpos($currentScript, '/farmers/') !== false || $activePage === 'farmers');
$isUserLogsPage = (strpos($currentScript, 'user-logs.php') !== false || strpos($currentScript, 'audit-logs.php') !== false || $activePage === 'audit-logs');
?>
<div class="span3">
	<div class="sidebar">
		<ul class="widget widget-menu unstyled">
			<li class="<?php echo $isDashboardPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/admin/dashboard.php'); ?>">
					<i class="menu-icon icon-dashboard"></i>
					Dashboard
				</a>
			</li>
			<li class="<?php echo $isOrdersPage ? 'active' : ''; ?>">
				<a class="<?php echo $isOrdersPage ? '' : 'collapsed'; ?>" data-toggle="collapse" href="#togglePages">
					<i class="menu-icon icon-cog"></i>
					<i class="icon-chevron-down pull-right"></i><i class="icon-chevron-up pull-right"></i>
					Order Management
				</a>
				<ul id="togglePages" class="collapse unstyled <?php echo $isOrdersPage ? 'in' : ''; ?>">
					<li>
						<a href="<?php echo appUrl('/admin/todays-orders.php'); ?>">
							<i class="icon-tasks"></i>
							Today's Orders
<?php
$f1 = "00:00:00";
$from = date('Y-m-d') . " " . $f1;
$t1 = "23:59:59";
$to = date('Y-m-d') . " " . $t1;
$result = mysqli_query($con, "SELECT * FROM Orders where orderDate Between '$from' and '$to'");
$num_rows1 = mysqli_num_rows($result);
{
?>
							<b class="label orange pull-right"><?php echo htmlentities($num_rows1); ?></b>
<?php } ?>
						</a>
					</li>
					<li>
						<a href="<?php echo appUrl('/admin/pending-orders.php'); ?>">
							<i class="icon-tasks"></i>
							Pending Orders
<?php
$status = 'Delivered';
$ret = mysqli_query($con, "SELECT * FROM Orders where orderStatus!='$status' || orderStatus is null ");
$num = mysqli_num_rows($ret);
{ ?><b class="label orange pull-right"><?php echo htmlentities($num); ?></b>
<?php } ?>
						</a>
					</li>
					<li>
						<a href="<?php echo appUrl('/admin/delivered-orders.php'); ?>">
							<i class="icon-inbox"></i>
							Delivered Orders
<?php
$status = 'Delivered';
$rt = mysqli_query($con, "SELECT * FROM Orders where orderStatus='$status'");
$num1 = mysqli_num_rows($rt);
{ ?><b class="label green pull-right"><?php echo htmlentities($num1); ?></b>
<?php } ?>
						</a>
					</li>
				</ul>
			</li>

			<li class="<?php echo $isUsersPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/admin/manage-users.php'); ?>">
					<i class="menu-icon icon-group"></i>
					Manage users
				</a>
			</li>
		</ul>

		<ul class="widget widget-menu unstyled">
			<li class="<?php echo $isCategoryPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/admin/category.php'); ?>"><i class="menu-icon icon-tasks"></i> Create Category </a></li>
			<li class="<?php echo $isSubCategoryPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/admin/subcategory.php'); ?>"><i class="menu-icon icon-tasks"></i>Sub Category </a></li>
			<li class="<?php echo $isInsertProductPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/admin/insert-product.php'); ?>"><i class="menu-icon icon-paste"></i>Insert Product </a></li>
			<li class="<?php echo $isManageProductsPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/admin/manage-products.php'); ?>"><i class="menu-icon icon-table"></i>Manage Products </a></li>
			<li class="<?php echo $isFarmersPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/farmers/index.php'); ?>"><i class="menu-icon icon-leaf"></i>Manage Farmers </a></li>
		</ul>

		<ul class="widget widget-menu unstyled">
			<li class="<?php echo $isUserLogsPage ? 'active' : ''; ?>"><a href="<?php echo appUrl('/admin/audit-logs.php'); ?>"><i class="menu-icon icon-time"></i>Audit Logs </a></li>
			<li>
				<a href="<?php echo appUrl('/admin/logout.php'); ?>">
					<i class="menu-icon icon-signout"></i>
					Logout
				</a>
			</li>
		</ul>
	</div>
</div>
