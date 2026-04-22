<?php
session_start();
error_reporting(0);
include('includes/config.php');
require_once __DIR__ . '/admin/include/audit.php';
$authDefaultView = isset($_POST['submit']) ? 'register' : 'login';
// Code user Registration
if(isset($_POST['submit']))
{
$name=trim((string)$_POST['fullname']);
$email=trim((string)$_POST['emailid']);
$contactno=trim((string)$_POST['contactno']);
$password=md5($_POST['password']);
$existingUser = fetchUserByEmail($con, $email);
if($existingUser)
{
	echo "<script>alert('An account with this email already exists.');</script>";
}
else
{
$stmt = mysqli_prepare($con, "INSERT INTO users(name,email,contactno,password) VALUES (?, ?, ?, ?)");
$query = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $contactno, $password);
$query = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}
if($query)
{
	echo "<script>alert('You are successfully register');</script>";
}
else{
echo "<script>alert('Not register something went worng');</script>";
}
}
}
// Code for User login
if(isset($_POST['login']))
{
   $email=$_POST['email'];
   $password=md5($_POST['password']);
$user=fetchUserByEmail($con, $email);
if($user && isset($user['password']) && $user['password']===$password)
{
$state=getUserAccessState($con, $user);
if(!$state['allowed'])
{
$extra="login.php";
writeAuditLog($con, 'user', $email, 'login', 'failed', $state['message']);
$uip=$_SERVER['REMOTE_ADDR'];
$status=0;
$logStmt = mysqli_prepare($con,"INSERT INTO userlog(userEmail,userip,status) VALUES (?, ?, ?)");
if ($logStmt) {
mysqli_stmt_bind_param($logStmt, 'ssi', $email, $uip, $status);
mysqli_stmt_execute($logStmt);
mysqli_stmt_close($logStmt);
}
$host  = $_SERVER['HTTP_HOST'];
$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
$_SESSION['errmsg']=$state['message'];
exit();
}
$extra="my-cart.php";
$_SESSION['login']=$_POST['email'];
$_SESSION['id']=$user['id'];
$_SESSION['username']=$user['name'];
$_SESSION['role']='user';
unset($_SESSION['alogin'], $_SESSION['admin_name'], $_SESSION['farmer_id'], $_SESSION['farmer_username'], $_SESSION['farmer_name']);
registerTrackedSession($con, 'user', $_SESSION['username'], 'Customer');
writeAuditLog($con, 'user', $_POST['email'], 'login', 'success', 'Customer signed in successfully.');
$uip=$_SERVER['REMOTE_ADDR'];
$status=1;
$logStmt = mysqli_prepare($con,"INSERT INTO userlog(userEmail,userip,status) VALUES (?, ?, ?)");
if ($logStmt) {
mysqli_stmt_bind_param($logStmt, 'ssi', $_SESSION['login'], $uip, $status);
mysqli_stmt_execute($logStmt);
mysqli_stmt_close($logStmt);
}
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
$extra="login.php";
$email=$_POST['email'];
writeAuditLog($con, 'user', $email, 'login', 'failed', 'Failed customer login attempt.');
$uip=$_SERVER['REMOTE_ADDR'];
$status=0;
$logStmt = mysqli_prepare($con,"INSERT INTO userlog(userEmail,userip,status) VALUES (?, ?, ?)");
if ($logStmt) {
mysqli_stmt_bind_param($logStmt, 'ssi', $email, $uip, $status);
mysqli_stmt_execute($logStmt);
mysqli_stmt_close($logStmt);
}
$host  = $_SERVER['HTTP_HOST'];
$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
$_SESSION['errmsg']="Invalid email id or Password";
exit();
}
}


?>


<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Meta -->
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta name="description" content="">
		<meta name="author" content="">
	    <meta name="keywords" content="MediaCenter, Template, eCommerce">
	    <meta name="robots" content="all">

	    <title><?php echo APP_NAME; ?> | Sign in | Sign up</title>

	    <!-- Bootstrap Core CSS -->
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    
	    <!-- Customizable CSS -->
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
		<!--<link rel="stylesheet" href="assets/css/owl.theme.css">-->
		<link href="assets/css/lightbox.css" rel="stylesheet">
		<link rel="stylesheet" href="assets/css/animate.min.css">
		<link rel="stylesheet" href="assets/css/rateit.css">
		<link rel="stylesheet" href="assets/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="assets/css/auth-ui.css">

		<!-- Demo Purpose Only. Should be removed in production -->
		<link rel="stylesheet" href="assets/css/config.css">

		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<!-- Demo Purpose Only. Should be removed in production : END -->

		
		<!-- Icons/Glyphs -->
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">

        <!-- Fonts --> 
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		
		<!-- Favicon -->
		<link rel="shortcut icon" href="assets/images/favicon.ico">
<script type="text/javascript">
function valid()
{
 if(document.register.password.value!= document.register.confirmpassword.value)
{
alert("Password and Confirm Password Field do not match  !!");
document.register.confirmpassword.focus();
return false;
}
return true;
}
</script>
    	<script>
function userAvailability() {
$("#loaderIcon").show();
jQuery.ajax({
url: "check_availability.php",
data:'email='+$("#register-email").val(),
type: "POST",
success:function(data){
$("#user-availability-status1").html(data);
$("#loaderIcon").hide();
},
error:function (){}
});
}
</script>



	</head>
    <body class="cnt-home">
	
		
	
		<!-- ============================================== HEADER ============================================== -->
<header class="header-style-1">

	<!-- ============================================== TOP MENU ============================================== -->
<?php include('includes/top-header.php');?>
<!-- ============================================== TOP MENU : END ============================================== -->
<?php include('includes/main-header.php');?>
	<!-- ============================================== NAVBAR ============================================== -->
<?php include('includes/menu-bar.php');?>
<!-- ============================================== NAVBAR : END ============================================== -->

</header>

<!-- ============================================== HEADER : END ============================================== -->
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="home.html">Home</a></li>
				<li class='active'>Authentication</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="auth-page-wrap">
			<div class="auth-shell" data-auth-ui data-auth-default-view="<?php echo htmlentities($authDefaultView); ?>">
				<div class="auth-card">
					<div class="auth-grid">
						<div class="auth-brand-panel">
							<div class="auth-brand-content">
								<div>
									<span class="auth-brand-kicker"><i class="fa fa-leaf"></i> <?php echo APP_NAME; ?> Portal</span>
									<h1 class="auth-brand-title">Welcome back to a cleaner way to trade and manage produce.</h1>
									<p class="auth-brand-copy">Sign in as a customer to shop, track orders, and manage your account. Farmers continue to use their dedicated portal with the existing workflow unchanged.</p>
								</div>
								<ul class="auth-brand-points">
									<li><i class="fa fa-check"></i> Fast checkout for returning customers</li>
									<li><i class="fa fa-line-chart"></i> Better visibility into orders and activity</li>
									<li><i class="fa fa-shield"></i> Separate farmer portal for farm operations</li>
								</ul>
							</div>
						</div>
						<div class="auth-panel">
							<div class="auth-topbar">
								<div class="auth-mode-toggle" role="tablist" aria-label="Authentication mode">
									<button type="button" class="is-active" data-auth-mode="user">Login as User</button>
									<button type="button" data-auth-mode="farmer" data-auth-redirect="farmers/login.php">Login as Farmer</button>
								</div>
								<div class="auth-view-switch" role="tablist" aria-label="Authentication form">
									<button type="button" class="is-active" data-auth-view="login">Sign In</button>
									<button type="button" data-auth-view="register">Create Account</button>
								</div>
							</div>

							<div class="auth-panels">
								<div class="auth-form-panel is-active" data-auth-panel="login">
									<h2 class="auth-heading">User login</h2>
									<p class="auth-subheading">Use your existing account details to continue to cart, orders, and profile management.</p>
<?php if (!empty($_SESSION['errmsg'])) { ?>
									<div class="auth-status error"><?php echo htmlentities($_SESSION['errmsg']); ?></div>
<?php } ?>
<?php $_SESSION['errmsg']=""; ?>
									<form class="auth-form" method="post">
										<div class="form-group">
											<label for="user-email">Email Address</label>
											<input type="email" name="email" class="auth-input" id="user-email" placeholder="Enter your email address" required>
										</div>
										<div class="form-group">
											<label for="user-password">Password</label>
											<input type="password" name="password" class="auth-input" id="user-password" placeholder="Enter your password" required>
										</div>
										<div class="auth-inline">
											<a href="forgot-password.php">Forgot Password?</a>
											<a href="#" class="auth-link" data-auth-view="register">Create Account</a>
										</div>
										<button type="submit" class="auth-button" name="login">Login</button>
									</form>
									<p class="auth-footnote">Farmer accounts use the separate farmer portal for sign-in.</p>
								</div>

								<div class="auth-form-panel" data-auth-panel="register">
									<h2 class="auth-heading">Create your account</h2>
									<p class="auth-subheading">Register once to start shopping, save details, and follow your orders without changing any of the existing backend flow.</p>
									<form class="auth-form" role="form" method="post" name="register" onSubmit="return valid();">
										<div class="form-group">
											<label for="fullname">Full Name</label>
											<input type="text" class="auth-input" id="fullname" name="fullname" placeholder="Your full name" required="required">
										</div>
										<div class="form-group">
											<label for="register-email">Email Address</label>
											<input type="email" class="auth-input" id="register-email" onBlur="userAvailability()" name="emailid" placeholder="Your email address" required>
											<span id="user-availability-status1" class="auth-availability"></span>
										</div>
										<div class="form-group">
											<label for="contactno">Contact No.</label>
											<input type="text" class="auth-input" id="contactno" name="contactno" maxlength="10" placeholder="Your contact number" required>
										</div>
										<div class="form-group">
											<label for="password">Password</label>
											<input type="password" class="auth-input" id="password" name="password" placeholder="Create a password" required>
										</div>
										<div class="form-group">
											<label for="confirmpassword">Confirm Password</label>
											<input type="password" class="auth-input" id="confirmpassword" name="confirmpassword" placeholder="Confirm your password" required>
										</div>
										<button type="submit" name="submit" class="auth-button" id="submit">Sign Up</button>
									</form>
									<div class="auth-benefits">
										<div class="auth-benefit">
											<strong>Faster checkout</strong>
											<span>Move through purchases with fewer repeated steps.</span>
										</div>
										<div class="auth-benefit">
											<strong>Order tracking</strong>
											<span>See status updates and order details in one place.</span>
										</div>
										<div class="auth-benefit">
											<strong>Saved history</strong>
											<span>Keep a clear record of previous purchases.</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php include('includes/brands-slider.php');?>
</div>
</div>
<?php include('includes/footer.php');?>
	<script src="assets/js/jquery-1.11.1.min.js"></script>
	
	<script src="assets/js/bootstrap.min.js"></script>
	
	<script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/js/owl.carousel.min.js"></script>
	
	<script src="assets/js/echo.min.js"></script>
	<script src="assets/js/jquery.easing-1.3.min.js"></script>
	<script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/jquery.rateit.min.js"></script>
    <script type="text/javascript" src="assets/js/lightbox.min.js"></script>
	<script src="assets/js/bootstrap-select.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
	<script src="assets/js/scripts.js"></script>
	<script src="assets/js/auth-ui.js"></script>

	<!-- For demo purposes – can be removed on production -->
	
	<script src="switchstylesheet/switchstylesheet.js"></script>
	
	<script>
		$(document).ready(function(){ 
			$(".changecolor").switchstylesheet( { seperator:"color"} );
			$('.show-theme-options').click(function(){
				$(this).parent().toggleClass('open');
				return false;
			});
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
	<!-- For demo purposes – can be removed on production : End -->

	

</body>
</html>
