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
?>
<div class="navbar navbar-fixed-top">
		<div class="navbar-inner" style="background-color:#4fb477;">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-inverse-collapse">
					<i class="icon-reorder shaded"></i>
				</a>

			  	<a class="brand" href="<?php echo appUrl('/admin/todays-orders.php'); ?>" style="text-shadow:none;">
			  		MaizeHub | Admin
			  	</a>

				<div class="nav-collapse collapse navbar-inverse-collapse">
					<ul class="nav pull-right">
						<li><a href="#" style="text-shadow:none; color:#000000;">
							Admin
						</a></li>
						<li class="nav-user dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<img src="<?php echo appUrl('/admin/images/user.png'); ?>" class="nav-avatar" />
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo appUrl('/admin/change-password.php'); ?>">Change Password</a></li>
								<li class="divider"></li>
								<li><a href="<?php echo appUrl('/admin/logout.php'); ?>">Logout</a></li>
							</ul>
						</li>
					</ul>
				</div><!-- /.nav-collapse -->
			</div>
		</div><!-- /navbar-inner -->
	</div><!-- /navbar -->
