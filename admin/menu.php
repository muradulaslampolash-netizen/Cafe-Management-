<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

// Handle delete
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
	verify_csrf();
	$id = (int)($_POST['id'] ?? 0);
	if ($id > 0) {
		try {
			$pdo = get_pdo();
			// Fetch existing image so we can remove the file
			$s = $pdo->prepare('SELECT image FROM menu_items WHERE id = ?');
			$s->execute([$id]);
			$existingImage = $s->fetchColumn();
			if ($existingImage) {
				$path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $existingImage;
				if (file_exists($path)) {
					@unlink($path);
				}
			}
			$stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
			$stmt->execute([$id]);
		} catch (PDOException $e) {
			// Error handling
		}
	}
	redirect(BASE_URL . '/admin/menu.php');
}

// Handle image upload per-item
if (is_post() && ($_POST['action'] ?? '') === 'upload_image') {
	verify_csrf();
	$id = (int)($_POST['id'] ?? 0);
	if ($id > 0) {
		$uploadedFile = $_FILES['image'] ?? null;
		if ($uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
			[$ok, $fname, $err] = handle_image_upload($uploadedFile);
			if ($ok && $fname) {
				try {
					$pdo = get_pdo();
					// Remove previous image file if present
					$s = $pdo->prepare('SELECT image FROM menu_items WHERE id = ?');
					$s->execute([$id]);
					$existingImage = $s->fetchColumn();
					if ($existingImage) {
						$path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $existingImage;
						if (file_exists($path)) {
							@unlink($path);
						}
					}
					$u = $pdo->prepare('UPDATE menu_items SET image = ? WHERE id = ?');
					$u->execute([$fname, $id]);
				} catch (PDOException $e) {
					// Error will be handled after redirect
				}
			}
		}
	}
	// Redirect to prevent stale data and duplicate form submissions
	redirect(BASE_URL . '/admin/menu.php');
}

try {
	$pdo = get_pdo();
	$stmt = $pdo->query('SELECT id, name, price, available, image FROM menu_items ORDER BY created_at DESC');
	$items = $stmt->fetchAll();
} catch (PDOException $e) {
	$items = [];
	$error_message = "Unable to load menu items.";
}
include __DIR__ . '/../inc/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h2>Menu Items</h2>
	<a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php">Add New</a>
</div>
<?php if (isset($error_message)): ?>
	<div class="alert alert-danger"><?php echo e($error_message); ?></div>
<?php endif; ?>
<div class="table-responsive">
	<table class="table align-middle">
		<thead>
			<tr>
				<th>ID</th>
				<th>Image</th>
				<th>Name</th>
				<th>Price</th>
				<th>Available</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($items)): ?>
				<tr><td colspan="6" class="text-center text-muted">No menu items found.</td></tr>
			<?php else: ?>
			<?php foreach ($items as $it): ?>
				<tr>
					<td><?php echo e((int)$it['id']); ?></td>
					<td>
						<?php if (!empty($it['image'])): ?>
							<img src="<?php echo e(UPLOAD_URL . '/' . rawurlencode($it['image'])); ?>" style="width:56px;height:56px;object-fit:cover" class="rounded" alt="">
						<?php endif; ?>
					</td>
					<td><?php echo e($it['name']); ?></td>
					<td>$<?php echo e(price_format((float)$it['price'])); ?></td>
					<td><?php echo e($it['available'] ? 'Yes' : 'No'); ?></td>
					<td>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php?id=<?php echo e((int)$it['id']); ?>">Edit</a>
						<form method="post" class="d-inline" onsubmit="return confirm('Delete this item?');">
							<?php echo csrf_field(); ?>
							<input type="hidden" name="action" value="delete">
							<input type="hidden" name="id" value="<?php echo e((int)$it['id']); ?>">
							<button class="btn btn-sm btn-outline-danger">Delete</button>
						</form>
						<!-- Quick upload form -->
						<form method="post" enctype="multipart/form-data" class="d-inline ms-2">
							<?php echo csrf_field(); ?>
							<input type="hidden" name="action" value="upload_image">
							<input type="hidden" name="id" value="<?php echo e((int)$it['id']); ?>">
							<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" style="display:inline-block;vertical-align:middle">
							<button class="btn btn-sm btn-primary">Upload</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>

