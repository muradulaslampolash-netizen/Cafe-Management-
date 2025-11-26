<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_admin();

$pdo = get_pdo();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$errors = [];
$name = '';
$description = '';
$price = '';
$available = 1;
$image = null;

if ($editing) {
	$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
	$stmt->execute([$id]);
	$existing = $stmt->fetch();
	if (!$existing) {
		http_response_code(404);
		exit('Item not found');
	}
	$name = $existing['name'];
	$description = $existing['description'];
	$price = price_format((float)$existing['price']);
	$available = (int)$existing['available'];
	$image = $existing['image'];
}

if (is_post()) {
	verify_csrf();
	$name = trim($_POST['name'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$price = trim($_POST['price'] ?? '');
	$available = isset($_POST['available']) ? 1 : 0;

	if ($name === '') $errors[] = 'Name is required.';
	if ($description === '') $errors[] = 'Description is required.';
	if (!validate_price($price)) $errors[] = 'Price must be positive with up to 2 decimals.';

	$uploadedFile = $_FILES['image'] ?? null;
	$filename = null;
	if ($uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
		// Check for upload errors
		$uploadError = $uploadedFile['error'] ?? UPLOAD_ERR_OK;
		if ($uploadError !== UPLOAD_ERR_OK) {
			$errorMessages = [
				UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
				UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
				UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
				UPLOAD_ERR_NO_FILE => 'No file was uploaded',
				UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
				UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
				UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload',
			];
			$errors[] = 'Image upload error: ' . ($errorMessages[$uploadError] ?? 'Unknown error');
		} else {
			[$ok, $fname, $err] = handle_image_upload($uploadedFile);
			if (!$ok) {
				$errors[] = 'Image upload failed: ' . ($err ?? 'Unknown error');
			} else {
				$filename = $fname;
			}
		}
	}

	if (!$errors) {
		try {
			if ($editing) {
				$sql = 'UPDATE menu_items SET name=?, description=?, price=?, available=?, image = COALESCE(?, image) WHERE id=?';
				$stmt = $pdo->prepare($sql);
				$stmt->execute([$name, $description, (float)$price, $available, $filename, $id]);
				// If a new image was uploaded, remove the previous file
				if ($filename !== null && !empty($image)) {
					$path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $image;
					if (file_exists($path)) {
						@unlink($path);
					}
				}
			} else {
				$sql = 'INSERT INTO menu_items (name, description, price, image, available, created_at) VALUES (?, ?, ?, ?, ?, NOW())';
				$stmt = $pdo->prepare($sql);
				$stmt->execute([$name, $description, (float)$price, $filename, $available]);
				$id = (int)$pdo->lastInsertId();
			}
			redirect(BASE_URL . '/admin/menu.php');
		} catch (PDOException $e) {
			$errors[] = 'Failed to save menu item. Please try again.';
		}
	}
}

include __DIR__ . '/../inc/header.php';
?>
<h2 class="mb-3"><?php echo $editing ? 'Edit' : 'Add'; ?> Menu Item</h2>
<?php if ($errors): ?>
	<div class="alert alert-danger">
		<ul class="mb-0">
			<?php foreach ($errors as $err): ?>
				<li><?php echo e($err); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm">
	<?php echo csrf_field(); ?>
	<div class="row">
		<div class="col-md-8">
			<div class="mb-3">
				<label class="form-label">Name</label>
				<input type="text" class="form-control" name="name" required value="<?php echo e($name); ?>">
			</div>
			<div class="mb-3">
				<label class="form-label">Description</label>
				<textarea class="form-control" name="description" rows="4" required><?php echo e($description); ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label">Price</label>
				<input type="text" class="form-control" name="price" required value="<?php echo e($price); ?>" pattern="^\d+(\.\d{1,2})?$" title="Positive number, up to 2 decimals">
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="available" id="available" <?php echo $available ? 'checked' : ''; ?>>
				<label class="form-check-label" for="available">Available</label>
			</div>
		</div>
		<div class="col-md-4">
			<div class="mb-3">
				<label class="form-label">Image (JPG/PNG/GIF/WEBP, max 2MB)</label>
				<input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
				<small class="text-muted">Supported formats: JPEG, PNG, GIF, WebP. Maximum file size: 2MB</small>
			</div>
			<?php if (!empty($image)): ?>
				<div class="mb-3">
					<label class="form-label">Current Image:</label>
					<img src="<?php echo e(UPLOAD_URL . '/' . rawurlencode($image)); ?>" class="img-fluid rounded border" alt="Current image" style="max-height: 200px; object-fit: cover;">
					<p class="small text-muted mt-2">Upload a new image to replace this one.</p>
				</div>
			<?php else: ?>
				<div class="alert alert-info">
					<small>No image uploaded yet. Upload an image to display it on the menu.</small>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div>
		<button class="btn btn-primary"><?php echo $editing ? 'Update' : 'Create'; ?></button>
		<a href="<?php echo e(BASE_URL); ?>/admin/menu.php" class="btn btn-link">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../inc/footer.php'; ?>

