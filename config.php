<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cafe_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App configuration
define('APP_NAME', 'Cafe Manager');

// Calculate BASE_URL - works from any subdirectory
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get document root and project root
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$projectRoot = str_replace('\\', '/', dirname(__FILE__));

// Calculate relative path from document root to project
$relativePath = str_replace($docRoot, '', $projectRoot);
$relativePath = trim($relativePath, '/');

// Build base URL
$basePath = $relativePath ? '/' . $relativePath : '';
define('BASE_URL', $protocol . '://' . $host . $basePath);

define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2MB
// Allowed image MIME types for uploads (add more as needed)
define('ALLOWED_MIME', serialize([
	'image/jpeg',
	'image/png',
	'image/gif',
	'image/webp'
]));

// Security
define('CSRF_TOKEN_KEY', '_csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// Timezone
date_default_timezone_set('UTC');

// Enable error reporting for debugging (set to false in production)
define('DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Create PDO connection with error handling
function get_pdo(): PDO {
	static $pdo = null;
	if ($pdo !== null) {
		return $pdo;
	}
	try {
		$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		return $pdo;
	} catch (PDOException $e) {
		$error = "Database connection failed. Please check your database configuration and ensure the database 'cafe_manager' exists.";
		if (defined('DEBUG') && DEBUG) {
			$error .= " Error: " . htmlspecialchars($e->getMessage());
		}
		die($error);
	}
}

// Ensure uploads dir exists
if (!is_dir(UPLOAD_DIR)) {
	@mkdir(UPLOAD_DIR, 0777, true);
	@file_put_contents(UPLOAD_DIR . DIRECTORY_SEPARATOR . 'index.php', '<?php // Prevent directory listing');
}

