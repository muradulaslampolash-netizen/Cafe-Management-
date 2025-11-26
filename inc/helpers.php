<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/csrf.php';

function e($value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool {
	return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void {
	header('Location: ' . $path);
	exit;
}

function is_logged_in(): bool {
	return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
	return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user_role(): ?string {
	return $_SESSION['user_role'] ?? null;
}

function require_login(): void {
	if (!is_logged_in()) {
		redirect(BASE_URL . '/auth/login.php');
	}
}

function require_admin(): void {
	require_login();
	if (current_user_role() !== 'admin') {
		http_response_code(403);
		exit('Forbidden');
	}
}

function get_user(PDO $pdo, int $userId): ?array {
	$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
	$stmt->execute([$userId]);
	$user = $stmt->fetch();
	return $user ?: null;
}

function price_format(float $amount): string {
	return number_format($amount, 2, '.', '');
}

function validate_price(string $price): bool {
	return (bool)preg_match('/^\d+(\.\d{1,2})?$/', $price) && ((float)$price) > 0;
}

function get_cart(): array {
	return $_SESSION['cart'] ?? [];
}

function save_cart(array $cart): void {
	$_SESSION['cart'] = $cart;
}

function add_to_cart(int $menuItemId, int $quantity = 1): void {
	$cart = get_cart();
	if (isset($cart[$menuItemId])) {
		$cart[$menuItemId] += $quantity;
	} else {
		$cart[$menuItemId] = $quantity;
	}
	save_cart($cart);
}

function remove_from_cart(int $menuItemId): void {
	$cart = get_cart();
	unset($cart[$menuItemId]);
	save_cart($cart);
}

function handle_image_upload(array $fileInput): array {
	// Returns [success(bool), filename(string|null), error(string|null)]
	if (($fileInput['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
		return [true, null, null]; // No file provided
	}
	if ($fileInput['error'] !== UPLOAD_ERR_OK) {
		return [false, null, 'Upload error'];
	}
	if (($fileInput['size'] ?? 0) > MAX_UPLOAD_BYTES) {
		return [false, null, 'File too large (max 2MB)'];
	}
	$tmp = $fileInput['tmp_name'];
	$info = @getimagesize($tmp);
	if ($info === false) {
		return [false, null, 'Invalid image'];
	}
	$mime = $info['mime'] ?? '';
	$allowed = unserialize(ALLOWED_MIME);
	if (!in_array($mime, $allowed, true)) {
		return [false, null, 'Unsupported image type'];
	}
	// Map common image MIME types to file extensions
	$mimeToExt = [
		'image/jpeg' => 'jpg',
		'image/pjpeg' => 'jpg',
		'image/png'  => 'png',
		'image/gif'  => 'gif',
		'image/webp' => 'webp',
	];
	$ext = $mimeToExt[$mime] ?? 'jpg';
	$filename = uniqid('img_', true) . '.' . $ext;
	$dest = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
	// Try to move uploaded file; if that fails, try copy as a fallback
	if (!@move_uploaded_file($tmp, $dest)) {
		if (!@copy($tmp, $dest)) {
			return [false, null, 'Failed to save image'];
		}
	}
	return [true, $filename, null];
}

