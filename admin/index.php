<?php
session_start();
error_reporting(0);
include("include/config.php");
require_once __DIR__ . '/include/audit.php';
if(isset($_POST['submit']))
{
	$username=trim((string)$_POST['username']);
	$password=(string)$_POST['password'];
    $num = null;

    $stmt = mysqli_prepare($con, "SELECT id, username FROM admin WHERE username = ? AND password = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $username, $password);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $num = $result ? mysqli_fetch_assoc($result) : null;
        }
        mysqli_stmt_close($stmt);
    }
if($num)
{
$extra="dashboard.php";//
$_SESSION['alogin']=$_POST['username'];
$_SESSION['id']=$num['id'];
$_SESSION['admin_name']=$num['username'];
$_SESSION['role']='admin';
unset($_SESSION['farmer_id'], $_SESSION['farmer_username'], $_SESSION['farmer_name']);
registerTrackedSession($con, 'admin', $num['username'], 'Administrator');
writeAuditLog($con, 'admin', $username, 'login', 'success', 'Administrator signed in successfully.');
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
$_SESSION['errmsg']="Invalid username or password";
writeAuditLog($con, 'admin', $username, 'login', 'failed', 'Failed administrator login attempt.');
$extra="index.php";
$host  = $_SERVER['HTTP_HOST'];
$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Admin login</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
	<!-- Favicon -->
	<link rel="shortcut icon" href="assets/images/favicon.ico">
</head>
<body>

	<div class="navbar navbar-fixed-top" >
		<div class="navbar-inner" style="background-color:#4fb477;">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-inverse-collapse">
					<i class="icon-reorder shaded"></i>
				</a>

			  	<a class="brand" href="index.html" style="text-shadow:none;">
			  		<?php echo APP_NAME; ?> | Admin
			  	</a>

				<div class="nav-collapse collapse navbar-inverse-collapse">
				
					<ul class="nav pull-right">

						<li><a href="../" style="text-shadow:none; color:#000000;">
						Back to Portal
						
						</a></li>

						

						
					</ul>
				</div><!-- /.nav-collapse -->
			</div>
		</div><!-- /navbar-inner -->
	</div><!-- /navbar -->



	<div class="wrapper" >
		<div class="container" style="margin-bottom:2rem;">
			<div class="row">
				<div class="module module-login span4 offset4">
					<form class="form-vertical" method="post">
						<div class="module-head">
							<h3>Sign In</h3>
						</div>
						<span style="color:red;" ><?php echo htmlentities($_SESSION['errmsg']); ?><?php echo htmlentities($_SESSION['errmsg']="");?></span>
						<div class="module-body">
							<div class="control-group">
								<div class="controls row-fluid">
									<input class="span12" type="text" id="inputEmail" name="username" placeholder="Username">
								</div>
							</div>
							<div class="control-group">
								<div class="controls row-fluid">
						<input class="span12" type="password" id="inputPassword" name="password" placeholder="Password">
								</div>
							</div>
						</div>
						<div class="module-foot">
							<div class="control-group">
								<div class="controls clearfix">
									<button type="submit" class="btn btn-primary pull-right" name="submit">Login</button>
									
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div><!--/.wrapper-->

	<div class="footer" style="">
		<div class="container" >
			 

			<b class="copyright">&copy; 2025 <?php echo APP_NAME; ?> </b> All rights reserved.
		</div>
	</div>
	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
