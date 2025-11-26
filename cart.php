<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_login();

$pdo = get_pdo();

// Handle updates
if (is_post()) {
	verify_csrf();
	$action = $_POST['action'] ?? '';
	if ($action === 'update') {
		$quantities = $_POST['qty'] ?? [];
		$cart = get_cart();
		foreach ($quantities as $id => $q) {
			$menuId = (int)$id;
			$qty = max(0, (int)$q);
			if ($qty <= 0) {
				unset($cart[$menuId]);
			} else {
				$cart[$menuId] = $qty;
			}
		}
		save_cart($cart);
		redirect(BASE_URL . '/cart.php');
	} elseif ($action === 'remove') {
		$menuId = (int)($_POST['menu_id'] ?? 0);
		if ($menuId > 0) {
			remove_from_cart($menuId);
		}
		redirect(BASE_URL . '/cart.php');
	}
}

$cart = get_cart();
$items = [];
$subtotal = 0.0;
if ($cart) {
	$ids = array_map('intval', array_keys($cart));
	if (!empty($ids)) {
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$stmt = $pdo->prepare("SELECT id, name, price, image FROM menu_items WHERE id IN ($placeholders)");
		$stmt->execute($ids);
		$rows = $stmt->fetchAll();
		foreach ($rows as $row) {
			$qty = (int)($cart[(int)$row['id']] ?? 0);
			if ($qty <= 0) continue;
			$lineTotal = (float)$row['price'] * $qty;
			$items[] = [
				'id' => (int)$row['id'],
				'name' => $row['name'],
				'price' => (float)$row['price'],
				'image' => $row['image'],
				'qty' => $qty,
				'total' => $lineTotal,
			];
			$subtotal += $lineTotal;
		}
	}
}
include __DIR__ . '/inc/header.php';
?>
<h2 class="mb-3">Your Cart</h2>
<?php if (!$items): ?>
	<div class="alert alert-info">Your cart is empty. <a href="<?php echo e(BASE_URL); ?>/index.php">Browse menu</a></div>
<?php else: ?>
	<form method="post">
		<?php echo csrf_field(); ?>
		<input type="hidden" name="action" value="update">
		<div class="table-responsive">
			<table class="table align-middle">
				<thead>
					<tr>
						<th>Item</th>
						<th style="width: 120px;">Price</th>
						<th style="width: 120px;">Qty</th>
						<th style="width: 140px;">Total</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $it): ?>
						<tr>
							<td>
								<div class="d-flex align-items-center">
									<?php if (!empty($it['image'])): ?>
										<img src="<?php echo e(UPLOAD_URL . '/' . $it['image']); ?>" class="me-2 rounded" alt="" style="width: 56px;height: 56px;object-fit: cover;">
									<?php endif; ?>
									<strong><?php echo e($it['name']); ?></strong>
								</div>
							</td>
							<td>$<?php echo e(price_format($it['price'])); ?></td>
							<td>
								<input type="number" min="0" class="form-control form-control-sm" name="qty[<?php echo e($it['id']); ?>]" value="<?php echo e($it['qty']); ?>">
							</td>
							<td>$<?php echo e(price_format($it['total'])); ?></td>
							<td>
								<form method="post" class="d-inline">
									<?php echo csrf_field(); ?>
									<input type="hidden" name="action" value="remove">
									<input type="hidden" name="menu_id" value="<?php echo e($it['id']); ?>">
									<button class="btn btn-sm btn-outline-danger">Remove</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="d-flex justify-content-between align-items-center">
			<div>
				<button class="btn btn-outline-secondary">Update Cart</button>
				<a class="btn btn-link" href="<?php echo e(BASE_URL); ?>/index.php">Continue Shopping</a>
			</div>
			<div>
				<strong>Subtotal: $<?php echo e(price_format($subtotal)); ?></strong>
				<a class="btn btn-primary ms-2" href="<?php echo e(BASE_URL); ?>/checkout.php">Proceed to Checkout</a>
			</div>
		</div>
	</form>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>

