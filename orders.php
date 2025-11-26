<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_login();

try {
	$pdo = get_pdo();

	$placed = isset($_GET['placed']);
	$userId = current_user_id();

	$stmt = $pdo->prepare('SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
	$stmt->execute([$userId]);
	$orders = $stmt->fetchAll();

	// Load histories
	$histories = [];
	if ($orders) {
		$ids = array_map(fn($o) => (int)$o['id'], $orders);
		if (!empty($ids)) {
			$placeholders = implode(',', array_fill(0, count($ids), '?'));
			$q = $pdo->prepare("SELECT order_id, status, changed_at FROM order_status_history WHERE order_id IN ($placeholders) ORDER BY changed_at ASC");
			$q->execute($ids);
			foreach ($q->fetchAll() as $row) {
				$histories[(int)$row['order_id']][] = $row;
			}
		}
	}
} catch (PDOException $e) {
	$orders = [];
	$histories = [];
	$error_message = "Unable to load orders. Please try again.";
}
include __DIR__ . '/inc/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h2>My Orders</h2>
	<?php if ($placed): ?>
		<div class="alert alert-success mb-0">Order placed successfully!</div>
	<?php endif; ?>
</div>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php elseif (!$orders): ?>
	<div class="alert alert-info">No orders yet. <a href="<?php echo e(BASE_URL); ?>/index.php">Order now</a></div>
<?php else: ?>
	<?php foreach ($orders as $order): ?>
		<div class="card shadow-sm mb-3">
			<div class="card-body">
				<div class="d-flex justify-content-between">
					<div>
						<h5 class="card-title mb-1">Order #<?php echo e((int)$order['id']); ?></h5>
						<div class="text-muted small">Placed on <?php echo e($order['created_at']); ?></div>
					</div>
					<div>
						<strong>Status: <?php echo e($order['status']); ?></strong><br>
						<strong>Total: $<?php echo e(price_format((float)$order['total'])); ?></strong>
					</div>
				</div>
				<hr>
				<div>
					<h6>Timeline</h6>
					<ol class="timeline">
						<?php foreach ($histories[(int)$order['id']] ?? [] as $h): ?>
							<li>
								<span class="badge bg-secondary text-uppercase"><?php echo e($h['status']); ?></span>
								<span class="text-muted small ms-2"><?php echo e($h['changed_at']); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>

