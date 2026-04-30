<?php
$currentScript = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
$activePage = isset($activePage) ? (string)$activePage : '';
$isOverviewPage = (strpos($currentScript, '/farmers/overview.php') !== false || $activePage === 'overview');
$isAddBatchPage = (strpos($currentScript, '/farmers/add_batch.php') !== false || $activePage === 'add-batches');
$isBatchesPage = (strpos($currentScript, '/farmers/batches.php') !== false || $activePage === 'batches');
$isPostHarvestPage = (strpos($currentScript, '/farmers/post_harvest.php') !== false || $activePage === 'post-harvest');
$isSellProductsPage = (strpos($currentScript, '/farmers/sell-products.php') !== false || $activePage === 'sell-products');
?>
<div class="span3">
	<div class="sidebar">
		<ul class="widget widget-menu unstyled">
			<li class="<?php echo $isOverviewPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/farmers/overview.php'); ?>">
					<i class="menu-icon icon-dashboard"></i>
					Overview
				</a>
			</li>
			<li class="<?php echo $isAddBatchPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/farmers/add_batch.php'); ?>">
					<i class="menu-icon icon-plus"></i>
					Add Batches
				</a>
			</li>
			<li class="<?php echo $isBatchesPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/farmers/batches.php'); ?>">
					<i class="menu-icon icon-table"></i>
					Batches
				</a>
			</li>
			<li class="<?php echo $isPostHarvestPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/farmers/post_harvest.php'); ?>">
					<i class="menu-icon icon-tasks"></i>
					Post-Harvest
				</a>
			</li>
			<li class="<?php echo $isSellProductsPage ? 'active' : ''; ?>">
				<a href="<?php echo appUrl('/farmers/sell-products.php'); ?>">
					<i class="menu-icon icon-shopping-cart"></i>
					Sell Products
				</a>
			</li>
		</ul>
		<ul class="widget widget-menu unstyled">
			<li>
				<a href="<?php echo appUrl('/farmers/logout.php'); ?>">
					<i class="menu-icon icon-signout"></i>
					Logout
				</a>
			</li>
		</ul>
	</div>
</div>
