<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_login();

$pdo = get_pdo();

$cart = get_cart();
if (!$cart) {
	redirect(BASE_URL . '/cart.php');
}

// Load items for totals
$ids = array_map('intval', array_keys($cart));
if (empty($ids)) {
	redirect(BASE_URL . '/cart.php');
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE id IN ($placeholders)");
$stmt->execute($ids);
$items = $stmt->fetchAll();
$total = 0.0;
$indexed = [];
foreach ($items as $row) {
	$qty = (int)$cart[(int)$row['id']];
	$line = (float)$row['price'] * $qty;
	$total += $line;
	$indexed[(int)$row['id']] = ['name' => $row['name'], 'price' => (float)$row['price'], 'qty' => $qty];
}

$success = null;
if (is_post() && isset($_POST['pay']) && $_POST['pay'] === '1') {
	verify_csrf();
	// Mock payment: simulate success
	$success = true;
	if ($success) {
		$pdo->beginTransaction();
		try {
			$stmt = $pdo->prepare('INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, ?, NOW())');
			$stmt->execute([current_user_id(), $total, 'pending']);
			$orderId = (int)$pdo->lastInsertId();
			$oi = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
			foreach ($indexed as $id => $data) {
				$oi->execute([$orderId, $id, $data['qty'], $data['price']]);
			}
			// status history
			$hist = $pdo->prepare('INSERT INTO order_status_history (order_id, status, changed_by_user_id, changed_at) VALUES (?, ?, ?, NOW())');
			$hist->execute([$orderId, 'pending', current_user_id()]);
			$pdo->commit();
			save_cart([]);
			redirect(BASE_URL . '/orders.php?placed=1');
		} catch (Throwable $e) {
			$pdo->rollBack();
			$success = false;
		}
	}
}

include __DIR__ . '/inc/header.php';
?>
<h2 class="mb-3">Checkout</h2>
<div class="card shadow-sm">
	<div class="card-body">
		<h5 class="card-title">Order Summary</h5>
		<ul class="list-group list-group-flush mb-3">
			<?php foreach ($indexed as $id => $data): ?>
				<li class="list-group-item d-flex justify-content-between">
					<span><?php echo e($data['name']); ?> x <?php echo e($data['qty']); ?></span>
					<span>$<?php echo e(price_format($data['price'] * $data['qty'])); ?></span>
				</li>
			<?php endforeach; ?>
			<li class="list-group-item d-flex justify-content-between">
				<strong>Total</strong>
				<strong>$<?php echo e(price_format($total)); ?></strong>
			</li>
		</ul>
		<form method="post">
			<?php echo csrf_field(); ?>
			<input type="hidden" name="pay" value="1">
			<button class="btn btn-success">Pay Now (Mock)</button>
			<a class="btn btn-link" href="<?php echo e(BASE_URL); ?>/cart.php">Back to cart</a>
		</form>
	</div>
	<div class="card-footer small text-muted">
		Mock payment will always succeed and create your order as pending.
	</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>

