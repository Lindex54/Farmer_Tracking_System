<?php
session_start();
error_reporting(0);
include('../admin/include/config.php');
include('../admin/include/admin-auth.php');

if (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'farmer') {
    header('Location: ' . appUrl('/farmers/overview.php'));
    exit();
}

$username = '';
$errorMessage = '';

if (isset($_SESSION['pending_farmer_username'])) {
    $username = trim((string)$_SESSION['pending_farmer_username']);
    unset($_SESSION['pending_farmer_username']);
} elseif (isset($_POST['username'])) {
    $username = trim((string)$_POST['username']);
}

if ($username === '') {
    redirectWithFlash(appUrl('/farmers/login.php'), 'error', 'Username is required.', 'farmer_login');
}

if (!$con) {
    $errorMessage = 'Database connection is not available.';
} else {
    $checkStmt = mysqli_prepare($con, "SELECT id, password FROM farmers WHERE username = ? LIMIT 1");
    if (!$checkStmt) {
        $errorMessage = 'Unable to process your request right now.';
    } else {
        mysqli_stmt_bind_param($checkStmt, 's', $username);
        if (!mysqli_stmt_execute($checkStmt)) {
            $errorMessage = 'Unable to process your request right now.';
        } else {
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $farmer = $checkResult ? mysqli_fetch_assoc($checkResult) : null;

            if (!$farmer) {
                mysqli_stmt_close($checkStmt);
                redirectWithFlash(appUrl('/farmers/login.php'), 'error', 'Username not found', 'farmer_login');
            }

            $hasPassword = isset($farmer['password']) && trim((string)$farmer['password']) !== '';
            if ($hasPassword) {
                mysqli_stmt_close($checkStmt);
                redirectWithFlash(appUrl('/farmers/login.php'), 'success', 'Password already exists. Please sign in.', 'farmer_login');
            }
        }
        mysqli_stmt_close($checkStmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage === '') {
    $newPassword = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

    if ($newPassword === '' || $confirmPassword === '') {
        $errorMessage = 'Both password fields are required.';
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            $errorMessage = 'Unable to create password. Please try again.';
        } else {
            $updateStmt = mysqli_prepare($con, "UPDATE farmers SET password = ? WHERE username = ? AND (password IS NULL OR password = '')");

            if (!$updateStmt) {
                $errorMessage = 'Unable to process your request right now.';
            } else {
                mysqli_stmt_bind_param($updateStmt, 'ss', $hashedPassword, $username);

                if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
                    mysqli_stmt_close($updateStmt);
                    redirectWithFlash(appUrl('/farmers/login.php'), 'success', 'Password created successfully. Please sign in.', 'farmer_login');
                }

                $errorMessage = 'Unable to set password. It may have already been set.';
                mysqli_stmt_close($updateStmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> | Create Password</title>
	<link type="text/css" href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="../admin/css/theme.css?v=side-rail-2" rel="stylesheet">
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
					<li><a href="<?php echo appUrl('/farmers/login.php'); ?>" style="text-shadow:none; color:#000000;">Back to Login</a></li>
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
							<h3>Create Password</h3>
						</div>
						<div class="module-body">
<?php if ($errorMessage !== '') { ?>
							<div class="alert alert-error">
								<button type="button" class="close" data-dismiss="alert">x</button>
								<?php echo htmlentities($errorMessage); ?>
							</div>
<?php } ?>
							<div class="control-group">
								<label for="username">Username</label>
								<div class="controls row-fluid">
									<input class="span12" type="text" id="username" value="<?php echo htmlentities($username); ?>" readonly>
									<input type="hidden" name="username" value="<?php echo htmlentities($username); ?>">
								</div>
							</div>
							<div class="control-group">
								<label for="new_password">New Password</label>
								<div class="controls row-fluid">
									<input class="span12" type="password" id="new_password" name="new_password" minlength="6" maxlength="255" required>
								</div>
							</div>
							<div class="control-group">
								<label for="confirm_password">Confirm Password</label>
								<div class="controls row-fluid">
									<input class="span12" type="password" id="confirm_password" name="confirm_password" minlength="6" maxlength="255" required>
								</div>
							</div>
						</div>
						<div class="module-foot">
							<div class="control-group">
								<div class="controls clearfix">
									<button type="submit" class="btn btn-primary pull-right">Save Password</button>
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
