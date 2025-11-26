<?php
/**
 * Test Registration Script
 * This script tests the registration process and shows detailed errors
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Registration - Cafe Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; padding: 10px; background: #f0f0f0; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffe0e0; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e0e0ff; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Registration Test</h1>";

try {
    $pdo = get_pdo();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Users table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Users Table Structure:</h3>";
        echo "<pre>";
        echo "Column Name | Type | Null | Key | Default | Extra\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($columns as $col) {
            printf("%-12s | %-20s | %-4s | %-3s | %-7s | %s\n",
                $col['Field'],
                $col['Type'],
                $col['Null'],
                $col['Key'],
                $col['Default'] ?? 'NULL',
                $col['Extra']
            );
        }
        echo "</pre>";
        
        // Check if email already exists
        $testEmail = '0562310005101006@neub.edu.bd';
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo "<p class='error'>✗ Email '$testEmail' already exists in database</p>";
            echo "<p>Existing user:</p>";
            echo "<pre>";
            print_r($existing);
            echo "</pre>";
            echo "<p><strong>Solution:</strong> Use a different email address or delete the existing user from the database.</p>";
        } else {
            echo "<p class='success'>✓ Email '$testEmail' is available</p>";
        }
        
        // Test inserting a user
        echo "<h3>Test Registration:</h3>";
        $testName = 'Test User';
        $testEmail = 'test' . time() . '@example.com';
        $testPassword = 'Test123';
        $testHash = password_hash($testPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $result = $stmt->execute([$testName, $testEmail, $testHash, 'customer']);
            
            if ($result) {
                $userId = $pdo->lastInsertId();
                echo "<p class='success'>✓ Test user created successfully (ID: $userId)</p>";
                
                // Clean up test user
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                echo "<p class='info'>ℹ Test user deleted</p>";
            } else {
                echo "<p class='error'>✗ Failed to create test user</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Error creating test user: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Error Code: " . $e->getCode() . "</p>";
        }
        
        // Check for constraints
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'users'
        ");
        $constraints = $stmt->fetchAll();
        if (!empty($constraints)) {
            echo "<h3>Table Constraints:</h3>";
            echo "<pre>";
            print_r($constraints);
            echo "</pre>";
        }
        
    } else {
        echo "<p class='error'>✗ Users table does not exist</p>";
        echo "<p><strong>Solution:</strong> Import sql/seed.sql file in phpMyAdmin</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If email already exists, use a different email or delete the existing user</li>";
echo "<li>If table doesn't exist, import sql/seed.sql in phpMyAdmin</li>";
echo "<li>Check the error messages above for specific issues</li>";
echo "<li>Try registering again at <a href='auth/register.php'>Register Page</a></li>";
echo "</ol>";

echo "</body></html>";

