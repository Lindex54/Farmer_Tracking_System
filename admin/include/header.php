<?php
require_once __DIR__ . '/admin-auth.php';

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

$portalName = defined('APP_NAME') ? APP_NAME : 'FarmHub';
$currentRoleLabel = function_exists('getCurrentRoleLabel') ? getCurrentRoleLabel() : 'Admin';
$currentDisplayName = function_exists('getCurrentDisplayName') ? getCurrentDisplayName() : 'Admin';
?>
<div class="navbar navbar-fixed-top">
		<div class="navbar-inner" style="background-color:#4fb477;">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-inverse-collapse">
					<i class="icon-reorder shaded"></i>
				</a>

			  	<a class="brand" href="<?php echo (function_exists('isFarmer') && isFarmer()) ? appUrl('/farmers/batches.php') : appUrl('/admin/dashboard.php'); ?>" style="text-shadow:none;">
			  		<?php echo htmlentities($portalName); ?> | <?php echo htmlentities($currentRoleLabel); ?> Portal
			  	</a>

				<div class="nav-collapse collapse navbar-inverse-collapse">
					<ul class="nav pull-right">
						<li><a href="#" style="text-shadow:none; color:#000000;">
							<?php echo htmlentities($currentDisplayName); ?>
						</a></li>
						<li class="nav-user dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<img src="<?php echo appUrl('/admin/images/user.png'); ?>" class="nav-avatar" />
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo appUrl('/admin/change-password.php'); ?>">Change Password</a></li>
								<li class="divider"></li>
								<li><a href="<?php echo (function_exists('isFarmer') && isFarmer()) ? appUrl('/farmers/logout.php') : appUrl('/admin/logout.php'); ?>">Logout</a></li>
							</ul>
						</li>
					</ul>
				</div><!-- /.nav-collapse -->
			</div>
		</div><!-- /navbar-inner -->
	</div><!-- /navbar -->
