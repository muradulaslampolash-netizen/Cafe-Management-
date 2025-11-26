<?php
declare(strict_types=1);
/**
 * Direct Admin Login Page
 * Quick access to admin login
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';

// If already logged in as admin, redirect to dashboard
if (is_logged_in() && current_user_role() === 'admin') {
	redirect(BASE_URL . '/admin/dashboard.php');
}

// Handle login
$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$lockedMessage = '';

if (is_post()) {
	verify_csrf();
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Enter a valid email.';
	}
	if ($errors === []) {
		try {
			$pdo = get_pdo();
			$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, login_attempts, lock_until FROM users WHERE email = ?');
			$stmt->execute([$email]);
			$user = $stmt->fetch();
			if ($user) {
				// Check if user is admin
				if ($user['role'] !== 'admin') {
					$errors[] = 'Access denied. Admin credentials required.';
				} else {
					$now = new DateTimeImmutable();
					if (!empty($user['lock_until']) && (new DateTimeImmutable($user['lock_until'])) > $now) {
						$lockedMessage = 'Account locked due to too many attempts. Try again later.';
					} else {
						if (password_verify($password, $user['password_hash'])) {
							// success: reset attempts and lock
							$stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, lock_until = NULL WHERE id = ?');
							$stmt->execute([$user['id']]);
							$_SESSION['user_id'] = (int)$user['id'];
							$_SESSION['user_name'] = $user['name'];
							$_SESSION['user_role'] = $user['role'];
							redirect(BASE_URL . '/admin/dashboard.php');
						} else {
							$attempts = (int)$user['login_attempts'] + 1;
							$lockUntil = null;
							if ($attempts >= LOGIN_MAX_ATTEMPTS) {
								$lockUntil = (new DateTimeImmutable())->modify('+' . LOCKOUT_MINUTES . ' minutes')->format('Y-m-d H:i:s');
								$lockedMessage = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
								$attempts = LOGIN_MAX_ATTEMPTS;
							}
							$stmt = $pdo->prepare('UPDATE users SET login_attempts = ?, lock_until = ? WHERE id = ?');
							$stmt->execute([$attempts, $lockUntil, $user['id']]);
							$errors[] = 'Invalid credentials.';
						}
					}
				}
			} else {
				$errors[] = 'Invalid credentials.';
			}
		} catch (PDOException $e) {
			$errors[] = 'Login failed. Please try again.';
			if (defined('DEBUG') && DEBUG) {
				$errors[] = htmlspecialchars($e->getMessage());
			}
		}
	}
}

include __DIR__ . '/inc/header.php';
?>
<div class="row justify-content-center">
	<div class="col-md-5">
		<div class="card shadow-lg border-primary">
			<div class="card-header bg-primary text-white">
				<h3 class="card-title mb-0">🔐 Admin Login</h3>
			</div>
			<div class="card-body">
				<p class="text-muted">Access the admin panel to manage your cafe.</p>
				<?php if ($lockedMessage): ?>
					<div class="alert alert-warning"><?php echo e($lockedMessage); ?></div>
				<?php endif; ?>
				<?php if ($errors): ?>
					<div class="alert alert-danger">
						<ul class="mb-0">
							<?php foreach ($errors as $err): ?>
								<li><?php echo e($err); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<form method="post">
					<?php echo csrf_field(); ?>
					<div class="mb-3">
						<label class="form-label">Email</label>
						<input type="email" class="form-control" name="email" required value="<?php echo e($email); ?>" placeholder="admin@local.test" autofocus>
					</div>
					<div class="mb-3">
						<label class="form-label">Password</label>
						<input type="password" class="form-control" name="password" required placeholder="Enter admin password">
					</div>
					<button type="submit" class="btn btn-primary w-100 mb-2">
						<strong>Login to Admin Panel</strong>
					</button>
					<div class="text-center">
						<a href="<?php echo e(BASE_URL); ?>/auth/login.php" class="btn btn-link">Regular Login</a>
					</div>
				</form>
			</div>
			<div class="card-footer bg-light">
				<small class="text-muted">
					<strong>Default Admin Credentials:</strong><br>
					Email: <code>admin@local.test</code><br>
					Password: <code>Admin@123</code><br>
					<em>If login doesn't work, run <code>setup_admin.php</code> first.</em>
				</small>
			</div>
		</div>
		<div class="card mt-3">
			<div class="card-body">
				<h6 class="card-title">Admin Features</h6>
				<ul class="small mb-0">
					<li>📋 Manage Menu Items</li>
					<li>🖼️ Upload Images</li>
					<li>📦 Manage Orders</li>
					<li>📊 View Reports</li>
					<li>💰 Revenue Analytics</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>

