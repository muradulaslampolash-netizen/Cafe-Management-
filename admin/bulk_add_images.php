<?php
/**
 * Bulk Image Upload Helper
 * This page helps you add images to multiple menu items quickly
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

$pdo = get_pdo();

// Get all menu items without images
$stmt = $pdo->query("SELECT id, name, description, price, image FROM menu_items ORDER BY name ASC");
$allItems = $stmt->fetchAll();

$itemsWithoutImages = array_filter($allItems, function($item) {
	return empty($item['image']);
});

$itemsWithImages = array_filter($allItems, function($item) {
	return !empty($item['image']);
});

include __DIR__ . '/../inc/header.php';
?>

<h2 class="mb-3">Bulk Image Management</h2>

<div class="alert alert-info">
	<p class="mb-0"><strong>Instructions:</strong> To add images to menu items, go to <a href="<?php echo e(BASE_URL); ?>/admin/menu.php">Menu Management</a> and click "Edit" on each item. Then upload an image in the Image section.</p>
</div>

<div class="row g-3">
	<div class="col-md-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title">
					Items Without Images 
					<span class="badge bg-danger"><?php echo count($itemsWithoutImages); ?></span>
				</h5>
				<?php if (empty($itemsWithoutImages)): ?>
					<p class="text-success mb-0">✓ All items have images!</p>
				<?php else: ?>
					<div class="list-group">
						<?php foreach ($itemsWithoutImages as $item): ?>
							<div class="list-group-item">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<strong><?php echo e($item['name']); ?></strong>
										<br>
										<small class="text-muted">$<?php echo e(price_format((float)$item['price'])); ?></small>
									</div>
									<a href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php?id=<?php echo e((int)$item['id']); ?>" class="btn btn-sm btn-primary">Add Image</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<div class="col-md-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title">
					Items With Images 
					<span class="badge bg-success"><?php echo count($itemsWithImages); ?></span>
				</h5>
				<?php if (empty($itemsWithImages)): ?>
					<p class="text-muted mb-0">No items have images yet.</p>
				<?php else: ?>
					<div class="list-group">
						<?php foreach ($itemsWithImages as $item): ?>
							<div class="list-group-item">
								<div class="d-flex justify-content-between align-items-center">
									<div class="d-flex align-items-center">
										<?php if (!empty($item['image'])): ?>
											<img src="<?php echo e(UPLOAD_URL . '/' . $item['image']); ?>" 
												style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;" 
												alt="<?php echo e($item['name']); ?>">
										<?php endif; ?>
										<div>
											<strong><?php echo e($item['name']); ?></strong>
											<br>
											<small class="text-muted">$<?php echo e(price_format((float)$item['price'])); ?></small>
										</div>
									</div>
									<a href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php?id=<?php echo e((int)$item['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div class="card mt-4 shadow-sm">
	<div class="card-body">
		<h5 class="card-title">Quick Guide: Adding Images</h5>
		<ol>
			<li>Click <strong>"Add Image"</strong> button next to any item without an image</li>
			<li>Or go to <a href="<?php echo e(BASE_URL); ?>/admin/menu.php">Menu Management</a> and click <strong>"Edit"</strong> on any item</li>
			<li>In the Image section, click <strong>"Choose File"</strong></li>
			<li>Select an image file (JPEG or PNG, max 2MB)</li>
			<li>Click <strong>"Update"</strong> to save</li>
			<li>The image will appear on the homepage menu</li>
		</ol>
		<div class="alert alert-warning mt-3">
			<strong>Image Requirements:</strong>
			<ul class="mb-0">
				<li>Formats: JPEG (.jpg, .jpeg) or PNG (.png)</li>
				<li>Maximum file size: 2MB</li>
				<li>Recommended dimensions: 800x600 pixels</li>
			</ul>
		</div>
	</div>
</div>

<div class="mt-3">
	<a href="<?php echo e(BASE_URL); ?>/admin/menu.php" class="btn btn-primary">Go to Menu Management</a>
	<a href="<?php echo e(BASE_URL); ?>/admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>


