<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

try {
	$pdo = get_pdo();
	// Today revenue
	$stmt = $pdo->query("SELECT COALESCE(SUM(total),0) as today_total FROM orders WHERE DATE(created_at) = CURDATE()");
	$today = (float)($stmt->fetch()['today_total'] ?? 0);
	// Pending count
	$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
	$pending = (int)($stmt->fetch()['cnt'] ?? 0);
} catch (PDOException $e) {
	$today = 0.0;
	$pending = 0;
	$error_message = "Unable to load dashboard data.";
}

include __DIR__ . '/../inc/header.php';
?>
<h2 class="mb-3">Admin Dashboard</h2>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
	<div class="col-md-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-2">Today's Revenue</h5>
				<div class="display-6 price">$<?php echo e(price_format($today)); ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-1">Pending Orders</h5>
				<div class="display-6"><?php echo e((string)$pending); ?></div>
			</div>
		</div>
	</div>
</div>

<div class="row g-3">
	<div class="col-md-12">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-3">Quick Actions</h5>
				<div class="row g-2">
					<div class="col-md-3">
						<a href="<?php echo e(BASE_URL); ?>/admin/menu.php" class="btn btn-primary w-100">
							<strong>📋 Manage Menu</strong><br>
							<small>Add, edit, or delete menu items</small>
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php" class="btn btn-success w-100">
							<strong>➕ Add New Item</strong><br>
							<small>Create a new menu item</small>
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo e(BASE_URL); ?>/admin/orders.php" class="btn btn-warning w-100">
							<strong>📦 Manage Orders</strong><br>
							<small>View and update order status</small>
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo e(BASE_URL); ?>/admin/reports.php" class="btn btn-info w-100">
							<strong>📊 View Reports</strong><br>
							<small>Revenue charts and feedback</small>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row g-3 mt-3">
	<div class="col-md-12">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-3">Admin Features</h5>
				<div class="list-group">
					<a href="<?php echo e(BASE_URL); ?>/admin/menu.php" class="list-group-item list-group-item-action">
						<div class="d-flex w-100 justify-content-between">
							<h6 class="mb-1">Menu Management</h6>
						</div>
						<p class="mb-1">Add, edit, delete menu items. Upload images for each item.</p>
						<small>Click to manage menu items</small>
					</a>
					<a href="<?php echo e(BASE_URL); ?>/admin/orders.php" class="list-group-item list-group-item-action">
						<div class="d-flex w-100 justify-content-between">
							<h6 class="mb-1">Order Management</h6>
						</div>
						<p class="mb-1">View all orders and update their status (pending → preparing → delivered).</p>
						<small>Click to manage orders</small>
					</a>
					<a href="<?php echo e(BASE_URL); ?>/admin/reports.php" class="list-group-item list-group-item-action">
						<div class="d-flex w-100 justify-content-between">
							<h6 class="mb-1">Reports & Analytics</h6>
						</div>
						<p class="mb-1">View revenue charts, daily/monthly reports, and customer feedback.</p>
						<small>Click to view reports</small>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>

