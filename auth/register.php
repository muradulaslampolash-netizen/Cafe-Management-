<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/helpers.php';

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (is_post()) {
	verify_csrf();
	if ($name === '' || strlen($name) < 2) {
		$errors[] = 'Name is required (min 2 chars).';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Valid email is required.';
	}
	if (strlen($password) < 6) {
		$errors[] = 'Password must be at least 6 characters.';
	}
	if (!$errors) {
		try {
			$pdo = get_pdo();
			
			// Check if email already exists
			$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
			$stmt->execute([$email]);
			if ($stmt->fetch()) {
				$errors[] = 'Email already registered. Please use a different email or try logging in.';
			} else {
				// Validate name length in database
				if (strlen($name) > 100) {
					$errors[] = 'Name is too long (max 100 characters).';
				} else {
					// Hash password
					$hash = password_hash($password, PASSWORD_DEFAULT);
					if ($hash === false) {
						$errors[] = 'Failed to hash password. Please try again.';
					} else {
						// Insert user
						$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
						$result = $stmt->execute([$name, $email, $hash, 'customer']);
						
						if ($result) {
							// Success - redirect to login
							redirect(BASE_URL . '/auth/login.php?registered=1');
						} else {
							$errors[] = 'Registration failed. Please try again.';
						}
					}
				}
			}
		} catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMsg = $e->getMessage();
			
			// Handle specific database errors
			if ($errorCode == '23000') { // Integrity constraint violation
				if (strpos($errorMsg, 'email') !== false) {
					$errors[] = 'Email already registered. Please use a different email.';
				} else {
					$errors[] = 'Registration failed: Email may already be registered.';
				}
			} elseif ($errorCode == '42S02') {
				$errors[] = 'Database table not found. Please check database setup.';
			} elseif ($errorCode == '42S22') {
				$errors[] = 'Database column not found. Please check database structure.';
			} else {
				$errors[] = 'Registration failed: ' . htmlspecialchars($errorMsg);
				if (defined('DEBUG') && DEBUG) {
					$errors[] = 'Error Code: ' . $errorCode;
				}
			}
			
			// Log error for debugging
			if (defined('DEBUG') && DEBUG) {
				error_log("Registration Error: " . $errorMsg . " (Code: " . $errorCode . ")");
			}
		} catch (Exception $e) {
			$errors[] = 'An unexpected error occurred. Please try again.';
			if (defined('DEBUG') && DEBUG) {
				$errors[] = htmlspecialchars($e->getMessage());
			}
		}
	}
}
include __DIR__ . '/../inc/header.php';
?>
<div class="row justify-content-center">
	<div class="col-md-6">
		<h2 class="mb-3">Create your account</h2>
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
				<label class="form-label">Name</label>
				<input type="text" name="name" class="form-control" required value="<?php echo e($name); ?>">
			</div>
			<div class="mb-3">
				<label class="form-label">Email</label>
				<input type="email" name="email" class="form-control" required value="<?php echo e($email); ?>">
			</div>
			<div class="mb-3">
				<label class="form-label">Password</label>
				<input type="password" name="password" class="form-control" required>
			</div>
			<button class="btn btn-primary">Register</button>
			<a class="btn btn-link" href="<?php echo e(BASE_URL); ?>/auth/login.php">Already have an account?</a>
		</form>
	</div>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>

