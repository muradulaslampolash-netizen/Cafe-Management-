<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

// Handle status update
if (is_post() && ($_POST['action'] ?? '') === 'update_status') {
	verify_csrf();
	$orderId = (int)($_POST['order_id'] ?? 0);
	$status = $_POST['status'] ?? '';
	$allowed = ['pending','preparing','delivered'];
	if ($orderId > 0 && in_array($status, $allowed, true)) {
		try {
			$pdo = get_pdo();
			$pdo->beginTransaction();
			$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
			$stmt->execute([$status, $orderId]);
			$hist = $pdo->prepare('INSERT INTO order_status_history (order_id, status, changed_by_user_id, changed_at) VALUES (?, ?, ?, NOW())');
			$hist->execute([$orderId, $status, current_user_id()]);
			$pdo->commit();
		} catch (Throwable $e) {
			if (isset($pdo)) {
				$pdo->rollBack();
			}
		}
	}
	redirect(BASE_URL . '/admin/orders.php');
}

// List orders with user
try {
	$pdo = get_pdo();
	$stmt = $pdo->query("SELECT o.id, o.user_id, o.total, o.status, o.created_at, u.name as user_name
		FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC");
	$orders = $stmt->fetchAll();
} catch (PDOException $e) {
	$orders = [];
	$error_message = "Unable to load orders.";
}

include __DIR__ . '/../inc/header.php';
?>
<h2 class="mb-3">Orders</h2>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php endif; ?>
<div class="table-responsive">
	<table class="table align-middle">
		<thead>
			<tr>
				<th>ID</th>
				<th>Customer</th>
				<th>Total</th>
				<th>Status</th>
				<th>Placed</th>
				<th>Update</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($orders)): ?>
				<tr><td colspan="6" class="text-center text-muted">No orders found.</td></tr>
			<?php else: ?>
			<?php foreach ($orders as $o): ?>
				<tr>
					<td>#<?php echo e((int)$o['id']); ?></td>
					<td><?php echo e($o['user_name']); ?></td>
					<td>$<?php echo e(price_format((float)$o['total'])); ?></td>
					<td><span class="badge bg-secondary text-uppercase"><?php echo e($o['status']); ?></span></td>
					<td><?php echo e($o['created_at']); ?></td>
					<td>
						<form method="post" class="d-flex align-items-center gap-2">
							<?php echo csrf_field(); ?>
							<input type="hidden" name="action" value="update_status">
							<input type="hidden" name="order_id" value="<?php echo e((int)$o['id']); ?>">
							<select name="status" class="form-select form-select-sm" style="width:auto">
								<?php foreach (['pending','preparing','delivered'] as $st): ?>
									<option value="<?php echo e($st); ?>" <?php echo $st===$o['status']?'selected':''; ?>><?php echo e(ucfirst($st)); ?></option>
								<?php endforeach; ?>
							</select>
							<button class="btn btn-sm btn-primary">Save</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>

