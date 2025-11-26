<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$registered = isset($_GET['registered']);
$isAdminLogin = isset($_GET['admin']);
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
						
						// Redirect admin to dashboard, customers to homepage
						if ($user['role'] === 'admin') {
							redirect(BASE_URL . '/admin/dashboard.php');
						} else {
							redirect(BASE_URL . '/index.php');
						}
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
include __DIR__ . '/../inc/header.php';
?>
<div class="row justify-content-center">
	<div class="col-md-5">
		<h2 class="mb-3"><?php echo $isAdminLogin ? 'Admin Login' : 'Welcome back'; ?></h2>
		<?php if ($isAdminLogin): ?>
			<div class="alert alert-info">
				<strong>Admin Access:</strong> Use your admin credentials to access the admin panel.
			</div>
		<?php endif; ?>
		<?php if ($registered): ?>
			<div class="alert alert-success">Registration successful. Please login.</div>
		<?php endif; ?>
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
		<form method="post" class="card card-body shadow-sm">
			<?php echo csrf_field(); ?>
			<div class="mb-3">
				<label class="form-label">Email</label>
				<input type="email" class="form-control" name="email" required value="<?php echo e($email); ?>" placeholder="admin@local.test">
			</div>
			<div class="mb-3">
				<label class="form-label">Password</label>
				<input type="password" class="form-control" name="password" required placeholder="Enter your password">
			</div>
			<button class="btn btn-primary w-100 mb-2">Login</button>
			<a class="btn btn-link w-100" href="<?php echo e(BASE_URL); ?>/auth/register.php">Create account</a>
		</form>
		<div class="card mt-3" style="background: #f8f9fa;">
			<div class="card-body">
				<h6 class="card-title">Admin Login</h6>
				<p class="small text-muted mb-2">Default admin credentials:</p>
				<p class="small mb-1"><strong>Email:</strong> admin@local.test</p>
				<p class="small mb-0"><strong>Password:</strong> Admin@123</p>
				<p class="small text-muted mt-2 mb-0">If login doesn't work, run <code>setup_admin.php</code> first.</p>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>

