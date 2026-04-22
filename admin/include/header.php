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
<style>
	.admin-module-tools {
		float: right;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.admin-module-tools .btn {
		margin: 0;
		background: #1f2937;
		border: 1px solid #111827;
		color: #ffffff !important;
		text-shadow: none;
		font-weight: 600;
		padding: 6px 12px;
		border-radius: 4px;
		box-shadow: none;
	}

	.admin-module-tools .btn:hover,
	.admin-module-tools .btn:focus {
		background: #111827;
		color: #ffffff !important;
	}

	.admin-scroll-wrap {
		overflow-x: auto;
		overflow-y: hidden;
		padding-bottom: 8px;
		-webkit-overflow-scrolling: touch;
	}

	.admin-scroll-wrap table,
	.admin-scroll-wrap form,
	.admin-scroll-wrap .form-horizontal {
		min-width: 960px;
	}

	.admin-scroll-hint {
		display: none !important;
	}

	.admin-fullscreen-backdrop {
		display: none;
		position: fixed;
		inset: 0;
		background: rgba(15, 23, 42, 0.65);
		z-index: 9998;
	}

	.admin-fullscreen-backdrop.is-open {
		display: block;
	}

	.module.module-fullscreen {
		position: fixed;
		top: 20px;
		left: 20px;
		right: 20px;
		bottom: 20px;
		margin: 0;
		z-index: 9999;
		background: #fff;
		box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28);
		display: flex;
		flex-direction: column;
	}

	.module.module-fullscreen .module-head {
		flex: 0 0 auto;
	}

	.module.module-fullscreen .module-body {
		flex: 1 1 auto;
		overflow: auto;
	}

	body.admin-fullscreen-open {
		overflow: hidden;
	}
</style>
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
