<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');
require_once __DIR__ . '/../admin/include/audit.php';

if (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'farmer') {
    header('Location: ' . appUrl('/farmers/overview.php'));
    exit();
}

$flashMessage = pullFlashMessage('farmer_login');

$username = '';
$errorMessage = '';
$showPasswordField = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';

    if ($username === '') {
        $errorMessage = 'Username is required.';
    } elseif (!$con) {
        $errorMessage = 'Database connection is not available.';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id, username, name, password FROM farmers WHERE username = ? LIMIT 1");

        if (!$stmt) {
            $errorMessage = 'Unable to process your request right now.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);

            if (!mysqli_stmt_execute($stmt)) {
                $errorMessage = 'Unable to process your request right now.';
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $farmer = $result ? mysqli_fetch_assoc($result) : null;

                if (!$farmer) {
                    $errorMessage = 'Username not found';
                } else {
                    $hasPassword = isset($farmer['password']) && trim((string)$farmer['password']) !== '';

                    if (!$hasPassword) {
                        $_SESSION['pending_farmer_username'] = $username;
                        header('Location: ' . appUrl('/farmers/create_password.php'));
                        mysqli_stmt_close($stmt);
                        exit();
                    }

                    if ($action === 'verify_password') {
                        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

                        if ($password === '') {
                            $errorMessage = 'Password is required.';
                            $showPasswordField = true;
                        } elseif (password_verify($password, (string)$farmer['password'])) {
                            $_SESSION['role'] = 'farmer';
                            $_SESSION['farmer_id'] = (int)$farmer['id'];
                            $_SESSION['farmer_username'] = (string)$farmer['username'];
                            $_SESSION['farmer_name'] = isset($farmer['name']) ? (string)$farmer['name'] : (string)$farmer['username'];
                            unset($_SESSION['alogin'], $_SESSION['admin_name']);
                            registerTrackedSession($con, 'farmer', $_SESSION['farmer_name'], 'Farmer');
                            writeAuditLog($con, 'farmer', $_SESSION['farmer_name'], 'login', 'success', 'Farmer signed in successfully.');

                            header('Location: ' . appUrl('/farmers/overview.php'));
                            mysqli_stmt_close($stmt);
                            exit();
                        } else {
                            $errorMessage = 'Incorrect password';
                            $showPasswordField = true;
                            writeAuditLog($con, 'farmer', $username, 'login', 'failed', 'Incorrect farmer password supplied.');
                        }
                    } else {
                        $showPasswordField = true;
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }
    }

    if ($errorMessage === 'Username not found') {
        writeAuditLog($con, 'farmer', $username, 'login', 'failed', 'Farmer username not found.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Farmer Login</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="../admin/images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
	<link type="text/css" href="include/farmers-ui.css" rel="stylesheet">
	<link rel="shortcut icon" href="../assets/images/favicon.ico">
</head>
<body>
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner" style="background-color:#4fb477;">
			<div class="container">
				<a class="brand" href="<?php echo appUrl('/farmers/login.php'); ?>" style="text-shadow:none;">
					<?php echo APP_NAME; ?> | Farmer Portal
				</a>
				<ul class="nav pull-right">
					<li><a href="<?php echo appUrl('/'); ?>" style="text-shadow:none; color:#000000;">Back to Portal</a></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="wrapper">
		<div class="container" style="margin-bottom:2rem;">
			<div class="row">
				<div class="module module-login span4 offset4">
					<form class="form-vertical" method="post" novalidate>
						<div class="module-head">
							<h3>Farmer Sign In</h3>
						</div>
						<div class="module-body">
<?php if ($flashMessage && !empty($flashMessage['message'])) { ?>
							<div class="alert <?php echo ($flashMessage['status'] === 'success') ? 'alert-success' : 'alert-error'; ?>">
								<button type="button" class="close" data-dismiss="alert">x</button>
								<?php echo htmlentities($flashMessage['message']); ?>
							</div>
<?php } ?>
<?php if ($errorMessage !== '') { ?>
							<div class="alert alert-error">
								<button type="button" class="close" data-dismiss="alert">x</button>
								<?php echo htmlentities($errorMessage); ?>
							</div>
<?php } ?>
							<div class="control-group">
								<label for="username">Username</label>
								<div class="controls row-fluid">
									<input class="span12" type="text" id="username" name="username" maxlength="100" value="<?php echo htmlentities($username); ?>" required>
								</div>
							</div>
<?php if ($showPasswordField) { ?>
							<div class="control-group">
								<label for="password">Password</label>
								<div class="controls row-fluid">
									<input class="span12" type="password" id="password" name="password" maxlength="255" required>
								</div>
							</div>
							<input type="hidden" name="action" value="verify_password">
<?php } else { ?>
							<input type="hidden" name="action" value="check_username">
<?php } ?>
						</div>
						<div class="module-foot">
							<div class="control-group">
								<div class="controls clearfix">
									<button type="submit" class="btn btn-primary pull-right">
										<?php echo $showPasswordField ? 'Login' : 'Continue'; ?>
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="footer">
		<div class="container">
			<b class="copyright">&copy; 2026 <?php echo APP_NAME; ?></b> All rights reserved.
		</div>
	</div>
	<script src="../admin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="../admin/scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="../admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
