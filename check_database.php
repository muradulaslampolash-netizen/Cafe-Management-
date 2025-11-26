<?php
/**
 * Database Checker Script
 * This script checks if the database and tables exist
 * Run this before using the application
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Checker - Cafe Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Database Setup Checker</h1>";

try {
    // Test connection without database
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✓ Connected to MySQL server</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        echo "<p class='success'>✓ Database '" . DB_NAME . "' exists</p>";
        
        // Connect to database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check tables
        $requiredTables = ['users', 'menu_items', 'orders', 'order_items', 'order_status_history', 'feedback'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ Table '$table' exists</p>";
            } else {
                echo "<p class='error'>✗ Table '$table' is missing</p>";
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            // Check if menu_items has data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                echo "<p class='success'>✓ Menu items table has $count items</p>";
            } else {
                echo "<p class='warning'>⚠ Menu items table is empty (you may need to run seed.sql again)</p>";
            }
            
            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
            $stmt->execute(['admin@local.test']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "<p class='success'>✓ Admin user exists</p>";
                echo "<p><strong>IMPORTANT:</strong> Run <a href='setup_admin.php'>setup_admin.php</a> to set the correct admin password.</p>";
            } else {
                echo "<p class='warning'>⚠ Admin user not found</p>";
            }
            
            echo "<hr>";
            echo "<h2>✅ Database Setup Complete!</h2>";
            echo "<p><a href='index.php'>Go to Homepage</a> | <a href='setup_admin.php'>Setup Admin Password</a></p>";
        } else {
            echo "<hr>";
            echo "<h2 class='error'>❌ Database Tables Missing</h2>";
            echo "<p>Please import the <code>sql/seed.sql</code> file in phpMyAdmin.</p>";
            echo "<h3>Steps:</h3>";
            echo "<ol>";
            echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
            echo "<li>Select or create database 'cafe_manager'</li>";
            echo "<li>Go to 'Import' tab</li>";
            echo "<li>Choose file: <code>sql/seed.sql</code></li>";
            echo "<li>Click 'Go'</li>";
            echo "</ol>";
        }
    } else {
        echo "<p class='error'>✗ Database '" . DB_NAME . "' does not exist</p>";
        echo "<hr>";
        echo "<h2 class='error'>❌ Database Not Found</h2>";
        echo "<p>Please create the database first:</p>";
        echo "<h3>Option 1: Via phpMyAdmin</h3>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Click 'New' in the left sidebar</li>";
        echo "<li>Enter database name: <strong>cafe_manager</strong></li>";
        echo "<li>Select collation: <strong>utf8mb4_unicode_ci</strong></li>";
        echo "<li>Click 'Create'</li>";
        echo "<li>Then import <code>sql/seed.sql</code> file</li>";
        echo "</ol>";
        echo "<h3>Option 2: Via SQL</h3>";
        echo "<pre>CREATE DATABASE cafe_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre>";
        echo "<p>Then import <code>sql/seed.sql</code> file</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h2 class='error'>❌ Connection Failed</h2>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL service is running in XAMPP</li>";
    echo "<li>Database credentials in <code>config.php</code> are correct</li>";
    echo "<li>Default XAMPP: user='root', password='' (empty)</li>";
    echo "</ul>";
    echo "<p><strong>Current settings:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "<li>Password: " . (DB_PASS ? "***" : "(empty)") . "</li>";
    echo "</ul>";
}

echo "</body></html>";

