<?php
$currentScript = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
$activePage = isset($activePage) ? (string)$activePage : '';
$isAddBatchPage = (strpos($currentScript, '/farmers/add_batch.php') !== false || $activePage === 'add-batches');
$isBatchesPage = (strpos($currentScript, '/farmers/batches.php') !== false || $activePage === 'batches');
?>
<div class="span3">
	<div class="sidebar">
		<ul class="widget widget-menu unstyled">
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
