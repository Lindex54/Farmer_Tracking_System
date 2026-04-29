<?php
session_start();
include('include/config.php');
include('include/admin-auth.php');
requireAdmin(appUrl('/admin/index.php'));
ensureUserAccountControlColumns($con);
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $action = isset($_POST['user_action']) ? trim((string) $_POST['user_action']) : '';
    $adminActor = !empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $_SESSION['alogin'];
    $selectedUser = fetchUserById($con, $userId);

    if (!$selectedUser) {
        $_SESSION['user_error'] = 'The selected user account could not be found.';
        header('Location: manage-users.php');
        exit();
    }

    $selectedState = getUserAccessState($con, $selectedUser);
    $isDeleted = $selectedState['status'] === 'deleted';
    $isSuspended = $selectedState['status'] === 'suspended';
    $isActive = $selectedState['status'] === 'active';

    if ($action === 'suspend') {
        $durationKey = isset($_POST['suspension_period']) ? trim((string) $_POST['suspension_period']) : '';
        $allowResuspend = isset($_POST['allow_resuspend']) ? trim((string) $_POST['allow_resuspend']) : '';
        $durationMap = array(
            '1_day' => '+1 day',
            '3_days' => '+3 days',
            '1_week' => '+1 week',
            '2_weeks' => '+2 weeks',
            '1_month' => '+1 month',
        );

        if (!isset($durationMap[$durationKey])) {
            $_SESSION['user_error'] = 'Choose a suspension duration before saving.';
            header('Location: manage-users.php');
            exit();
        }

        if ($isDeleted) {
            $_SESSION['user_error'] = 'This account has already been deleted.';
            header('Location: manage-users.php');
            exit();
        }

        if ($isSuspended && $allowResuspend !== '1') {
            $_SESSION['user_error'] = 'This user is already suspended. Confirm again if you want to resuspend the account.';
            header('Location: manage-users.php');
            exit();
        }

        $until = date('Y-m-d H:i:s', strtotime($durationMap[$durationKey]));
        if (suspendUserAccount($con, $userId, $until)) {
            closeTrackedSessionsForUser($con, $selectedUser);
            $eventType = $isSuspended ? 'user_resuspended' : 'user_suspended';
            $message = $isSuspended ? 'User suspension updated until ' : 'User suspended until ';
            writeAuditLog($con, 'admin', $adminActor, $eventType, 'success', 'Administrator set suspension for customer "' . $selectedUser['email'] . '" until ' . $until . '.');
            $_SESSION['user_success'] = $message . date('M d, Y h:i A', strtotime($until)) . '.';
        } else {
            $_SESSION['user_error'] = 'Unable to suspend that user account right now.';
        }
    } elseif ($action === 'reactivate') {
        if ($isDeleted) {
            $_SESSION['user_error'] = 'Deleted accounts cannot be restored from this screen.';
            header('Location: manage-users.php');
            exit();
        }

        if ($isActive) {
            $_SESSION['user_error'] = 'This user account is already active.';
            header('Location: manage-users.php');
            exit();
        }

        if (reactivateUserAccount($con, $userId)) {
            writeAuditLog($con, 'admin', $adminActor, 'user_reactivated', 'success', 'Administrator reactivated customer "' . $selectedUser['email'] . '".');
            $_SESSION['user_success'] = 'User account reactivated successfully.';
        } else {
            $_SESSION['user_error'] = 'Unable to reactivate that user account.';
        }
    } elseif ($action === 'delete') {
        if (deleteUserAccount($con, $userId)) {
            closeTrackedSessionsForUser($con, $selectedUser);
            writeAuditLog($con, 'admin', $adminActor, 'user_deleted', 'success', 'Administrator deleted customer "' . $selectedUser['email'] . '" (soft delete).');
            $_SESSION['user_success'] = 'User account deleted. The customer will no longer be able to log in.';
        } else {
            $_SESSION['user_error'] = 'Unable to delete that user account.';
        }
    }

    header('Location: manage-users.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin| Manage Users</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css?v=nav-shell-1" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
	<style>
		.table-scroll-wrap {
			overflow-x: auto;
			overflow-y: hidden;
			max-width: 100%;
			padding-bottom: 8px;
		}

		.table-scroll-wrap table {
			min-width: 1180px;
		}

		.user-action-cell {
			min-width: 118px;
			width: 118px;
		}

		.user-action-cell select {
			width: 100%;
			box-sizing: border-box;
			font-size: 12px;
			margin-bottom: 4px;
			max-width: 86px;
			padding: 2px 4px;
		}

		.user-action-buttons {
			display: flex;
			flex-wrap: wrap;
			gap: 4px;
		}

		.user-action-buttons .btn {
			padding: 2px 6px;
			font-size: 11px;
			line-height: 1.2;
		}

		.user-status-cell {
			min-width: 88px;
			width: 88px;
			font-size: 12px;
		}

		.user-address-cell {
			min-width: 220px;
			max-width: 260px;
			white-space: normal;
			word-break: break-word;
		}

		.user-email-cell {
			min-width: 150px;
			word-break: break-word;
		}

		div.dataTables_wrapper {
			width: 100%;
		}

		div.dataTables_scrollBody {
			border-bottom: 1px solid #dfe6ee;
		}
	</style>
</head>
<body>
<?php include('include/header.php');?>

	<div class="wrapper">
		<div class="container">
			<div class="row">
<?php include('include/sidebar.php');?>
			<div class="span9">
					<div class="content">

	<div class="module">
							<div class="module-head">
								<h3>Manage Users</h3>
							</div>
							<div class="module-body table">
<?php if (!empty($_SESSION['user_success'])) { ?>
									<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
									<strong>Success!</strong> <?php echo htmlentities($_SESSION['user_success']); ?>
									</div>
<?php $_SESSION['user_success'] = ''; } ?>
<?php if (!empty($_SESSION['user_error'])) { ?>
									<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">x</button>
									<strong>Notice:</strong> <?php echo htmlentities($_SESSION['user_error']); ?>
									</div>
<?php $_SESSION['user_error'] = ''; } ?>

									<br />

								<div class="table-scroll-wrap">
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%" >
									<thead>
										<tr>
											<th>#</th>
											<th>Name</th>
											<th>Email</th>
											<th>Contact no</th>
											<th>Status</th>
											<th>Action</th>
											<th>Shippping Address/City/State/Pincode</th>
											<th>Billing Address/City/State/Pincode</th>
											<th>Reg. Date</th>
										</tr>
									</thead>
									<tbody>

<?php $query=mysqli_query($con,"select * from users order by id desc");
$cnt=1;
while($row=mysqli_fetch_array($query))
{
	$state = getUserAccessState($con, $row);
	$statusLabel = 'Active';
	$statusTitle = 'Active';
	if ($state['status'] === 'suspended' && !empty($state['user']['suspended_until'])) {
		$statusLabel = 'Susp.';
		$statusTitle = 'Suspended until ' . date('M d, Y h:i A', strtotime($state['user']['suspended_until']));
	} elseif ($state['status'] === 'deleted') {
		$statusLabel = 'Deleted';
		$statusTitle = 'Deleted';
	}
?>
										<tr>
											<td><?php echo htmlentities($cnt);?></td>
											<td><?php echo htmlentities($row['name']);?></td>
											<td class="user-email-cell"><?php echo htmlentities($row['email']);?></td>
											<td><?php echo htmlentities($row['contactno']);?></td>
											<td class="user-status-cell" title="<?php echo htmlentities($statusTitle);?>"><?php echo htmlentities($statusLabel);?></td>
											<td class="user-action-cell">
												<form method="post" style="margin:0;">
													<input type="hidden" name="user_id" value="<?php echo (int) $row['id']; ?>">
													<input type="hidden" name="allow_resuspend" value="0">
													<select name="suspension_period" <?php echo $state['status'] === 'deleted' ? 'disabled="disabled"' : ''; ?>>
														<option value="">Suspend for...</option>
														<option value="1_day">1 day</option>
														<option value="3_days">3 days</option>
														<option value="1_week">1 week</option>
														<option value="2_weeks">2 weeks</option>
														<option value="1_month">1 month</option>
													</select>
													<div class="user-action-buttons">
<?php if ($state['status'] !== 'deleted') { ?>
														<button type="submit" name="user_action" value="suspend" class="btn btn-warning btn-mini" onclick="return confirmSuspendAction(this.form);">Suspend</button>
														<button type="submit" name="user_action" value="reactivate" class="btn btn-success btn-mini">Restore</button>
<?php } ?>
														<button type="submit" name="user_action" value="delete" class="btn btn-danger btn-mini" onclick="return confirmDeleteAction();" <?php echo $state['status'] === 'deleted' ? 'disabled="disabled"' : ''; ?>>Delete</button>
													</div>
												</form>
											</td>
											<td class="user-address-cell"><?php echo htmlentities($row['shippingAddress'].",".$row['shippingCity'].",".$row['shippingState']."-".$row['shippingPincode']);?></td>
											<td class="user-address-cell"><?php echo htmlentities($row['billingAddress'].",".$row['billingCity'].",".$row['billingState']."-".$row['billingPincode']);?></td>
											<td><?php echo htmlentities($row['regDate']);?></td>
										</tr>
<?php $cnt=$cnt+1; } ?>

								</table>
								</div>
							</div>
						</div>

					</div><!--/.content-->
				</div><!--/.span9-->
			</div>
		</div><!--/.container-->
	</div><!--/.wrapper-->

<?php include('include/footer.php');?>

	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
	<script src="scripts/datatables/jquery.dataTables.js"></script>
	<script>
		function confirmSuspendAction(form) {
			var durationField = form.querySelector('select[name="suspension_period"]');
			var allowResuspendField = form.querySelector('input[name="allow_resuspend"]');
			if (!durationField || !durationField.value) {
				alert('Please choose a suspension duration first.');
				return false;
			}

			var durationLabel = durationField.options[durationField.selectedIndex].text;
			var statusCell = form.closest('tr').querySelector('.user-status-cell');
			var isSuspended = statusCell && statusCell.textContent.replace(/\s+/g, ' ').toLowerCase().indexOf('susp') !== -1;

			if (allowResuspendField) {
				allowResuspendField.value = '0';
			}

			if (isSuspended) {
				var resuspend = confirm('This user is already suspended. Do you want to resuspend the account for ' + durationLabel + '?');
				if (resuspend && allowResuspendField) {
					allowResuspendField.value = '1';
				}
				return resuspend;
			}

			return confirm('Are you sure you want to suspend this user for ' + durationLabel + '?');
		}

		function confirmDeleteAction() {
			return confirm('Are you sure you want to delete this user? They will not be able to log in again.');
		}

		$(document).ready(function() {
			$('.datatable-1').dataTable();
			$('.dataTables_paginate').addClass("btn-group datatable-pagination");
			$('.dataTables_paginate > a').wrapInner('<span />');
			$('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
			$('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');
		} );
	</script>
</body>
</html>
