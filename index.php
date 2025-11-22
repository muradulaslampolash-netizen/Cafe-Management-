<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';

// Add to cart handler (only verify CSRF on POST)
if (is_post() && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
	verify_csrf();
	require_login();
	$menuId = (int)($_POST['menu_id'] ?? 0);
	if ($menuId > 0) {
		add_to_cart($menuId, 1);
		redirect(BASE_URL . '/cart.php');
	}
}

// Fetch available menu items
try {
	$pdo = get_pdo();
	$stmt = $pdo->query('SELECT id, name, description, price, image FROM menu_items WHERE available = 1 ORDER BY created_at DESC');
	$items = $stmt->fetchAll();
} catch (PDOException $e) {
	$items = [];
	$error_message = "Unable to load menu items. Please check database connection.";
}

include __DIR__ . '/inc/header.php';
?>
<div class="hero">
	<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
		<div class="mb-3 mb-md-0">
			<h1 class="h3 mb-1">Discover your next favorite brew</h1>
			<p class="text-secondary mb-0">Fresh coffee, snacks, and smoothies — straight from our cafe.</p>
		</div>
		<?php if (!is_logged_in()): ?>
			<div>
				<a class="btn btn-primary me-2" href="<?php echo e(BASE_URL); ?>/auth/login.php">Sign in</a>
				<a class="btn btn-outline-primary" href="<?php echo e(BASE_URL); ?>/auth/register.php">Create account</a>
			</div>
		<?php else: ?>
			<a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/cart.php">View Cart</a>
		<?php endif; ?>
	</div>
</div>
<h2 class="mb-3">Menu</h2>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php elseif (empty($items)): ?>
	<div class="alert alert-info">No menu items available at this time.</div>
<?php else: ?>
<div class="row g-3">
	<?php foreach ($items as $it): ?>
		<div class="col-md-4">
			<div class="card h-100 shadow-sm menu-card">
				<?php
					$imageSrc = null;
					$files = @scandir(UPLOAD_DIR) ?: [];

					// Helper: normalize string to compare names (remove non-alnum, lowercase)
					$normalize = function(string $s): string {
						$s = mb_strtolower($s, 'UTF-8');
						$s = preg_replace('/[^a-z0-9]+/u', '', $s);
						return (string)$s;
					};

					// 1) If DB provided an image filename, try to locate it (case-insensitive)
					if (!empty($it['image'])) {
						$filename = $it['image'];
						$filePath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
						if (!file_exists($filePath)) {
							foreach ($files as $f) {
								if (strcasecmp($f, $filename) === 0) {
									$filename = $f;
									$filePath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
									break;
								}
							}
						}
						if (file_exists($filePath)) {
							$imageSrc = UPLOAD_URL . '/' . rawurlencode($filename);
						}
					}

					// 2) Fallback: try to find a file by matching the menu item's name
					if (!$imageSrc) {
						$nameNorm = $normalize((string)$it['name']);
						if ($nameNorm !== '') {
							foreach ($files as $f) {
								if ($f === '.' || $f === '..') continue;
								$base = pathinfo($f, PATHINFO_FILENAME);
								if ($normalize((string)$base) === $nameNorm) {
									$imageSrc = UPLOAD_URL . '/' . rawurlencode($f);
									break;
								}
							}
						}
					}
				?>
				<?php if ($imageSrc): ?>
					<img src="<?php echo e($imageSrc); ?>" class="card-img-top" alt="<?php echo e($it['name']); ?>">
				<?php else: ?>
					<div class="ratio ratio-16x9 d-flex align-items-center justify-content-center text-muted" style="background:#10122a;border-bottom:1px solid rgba(255,255,255,.05)">No Image</div>
				<?php endif; ?>
				<div class="card-body d-flex flex-column">
					<h5 class="card-title"><?php echo e($it['name']); ?></h5>
					<p class="card-text small text-muted"><?php echo e($it['description']); ?></p>
					<div class="mt-auto d-flex justify-content-between align-items-center">
						<strong class="price">$<?php echo e(price_format((float)$it['price'])); ?></strong>
						<?php if (is_logged_in()): ?>
							<form method="post" class="m-0">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="add_to_cart">
								<input type="hidden" name="menu_id" value="<?php echo e((int)$it['id']); ?>">
								<button class="btn btn-sm btn-primary">Add to cart</button>
							</form>
						<?php else: ?>
							<a class="btn btn-sm btn-outline-primary" href="<?php echo e(BASE_URL); ?>/auth/login.php">Login to order</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>

