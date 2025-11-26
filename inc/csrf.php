<?php

function csrf_token(): string {
	if (empty($_SESSION[CSRF_TOKEN_KEY])) {
		$_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
	}
	return $_SESSION[CSRF_TOKEN_KEY];
}

function csrf_field(): string {
	$token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
	return '<input type="hidden" name="_csrf" value="' . $token . '">';
}

function verify_csrf(): void {
	// Only verify on POST requests
	if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
		return;
	}
	// Initialize token if not exists (should not happen, but safe)
	if (empty($_SESSION[CSRF_TOKEN_KEY])) {
		csrf_token();
	}
	$sent = $_POST['_csrf'] ?? '';
	$valid = hash_equals($_SESSION[CSRF_TOKEN_KEY] ?? '', (string)$sent);
	if (!$valid) {
		http_response_code(400);
		die('Invalid CSRF token. Please try again.');
	}
}

