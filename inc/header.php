<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo e(APP_NAME); ?></title>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo e(BASE_URL); ?>/assets/style.css" rel="stylesheet">
		<style>body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial,'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';}</style>
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark mb-4">
			<div class="container">
				<a class="navbar-brand" href="<?php echo e(BASE_URL); ?>/index.php"><?php echo e(APP_NAME); ?></a>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample" aria-controls="navbarsExample" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarsExample">
					<ul class="navbar-nav me-auto mb-2 mb-lg-0">
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/index.php">Menu</a></li>
						<?php if (is_logged_in()): ?>
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/cart.php">Cart</a></li>
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/orders.php">My Orders</a></li>
						<?php endif; ?>
						<?php if (current_user_role()==='admin'): ?>
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="adminDropdown">
								<strong>Admin Panel</strong>
							</a>
							<ul class="dropdown-menu" aria-labelledby="adminDropdown">
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/dashboard.php"><strong>📊 Dashboard</strong></a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/menu.php">📋 Manage Menu</a></li>
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/menu_edit.php">➕ Add Menu Item</a></li>
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/bulk_add_images.php">🖼️ Add Images</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/orders.php">📦 Manage Orders</a></li>
								<li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/reports.php">📊 Reports & Analytics</a></li>
							</ul>
						</li>
						<?php endif; ?>
					</ul>
					<ul class="navbar-nav ms-auto">
						<?php if (!is_logged_in()): ?>
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/auth/login.php">Login</a></li>
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/auth/register.php">Register</a></li>
						<?php else: ?>
						<li class="nav-item">
							<span class="navbar-text me-2">
								Hello, <?php echo e($_SESSION['user_name'] ?? 'User'); ?>
							</span>
						</li>
						<li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/auth/logout.php">Logout</a></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</nav>
		<main class="container">

