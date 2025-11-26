<?php
/**
 * Check if a user exists in the database
 */

require_once __DIR__ . '/config.php';

$emailToCheck = $_GET['email'] ?? '0562310005101006@neub.edu.bd';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check User - Cafe Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; padding: 10px; background: #f0f0f0; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffe0e0; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e0e0ff; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        form { margin: 20px 0; }
        input[type='email'] { padding: 8px; width: 300px; }
        button { padding: 8px 15px; }
    </style>
</head>
<body>
    <h1>Check User Registration</h1>
    
    <form method='get'>
        <label>Email to check:</label><br>
        <input type='email' name='email' value='" . htmlspecialchars($emailToCheck) . "' required>
        <button type='submit'>Check</button>
    </form>
    <hr>";

try {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE email = ?");
    $stmt->execute([$emailToCheck]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<div class='error'>";
        echo "<h2>✗ Email Already Registered</h2>";
        echo "<p>The email <strong>" . htmlspecialchars($emailToCheck) . "</strong> is already registered.</p>";
        echo "<h3>User Details:</h3>";
        echo "<pre>";
        echo "ID: " . htmlspecialchars($user['id']) . "\n";
        echo "Name: " . htmlspecialchars($user['name']) . "\n";
        echo "Email: " . htmlspecialchars($user['email']) . "\n";
        echo "Role: " . htmlspecialchars($user['role']) . "\n";
        echo "Created: " . htmlspecialchars($user['created_at']) . "\n";
        echo "</pre>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>Options:</h3>";
        echo "<ol>";
        echo "<li><strong>Use a different email</strong> to register a new account</li>";
        echo "<li><strong>Login with this email</strong> if it's your account: <a href='auth/login.php'>Login Page</a></li>";
        echo "<li><strong>Delete this user</strong> if you want to register again (see below)</li>";
        echo "</ol>";
        echo "</div>";
        
        // Option to delete user (for testing)
        if (isset($_GET['delete']) && $_GET['delete'] === 'yes') {
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
                $deleteStmt->execute([$emailToCheck]);
                echo "<div class='success'>";
                echo "<p>✓ User deleted successfully. You can now register with this email.</p>";
                echo "<p><a href='auth/register.php'>Go to Registration Page</a></p>";
                echo "</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo "<p>✗ Error deleting user: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "<p><strong>Warning:</strong> If you want to delete this user and register again, click:</p>";
            echo "<p><a href='?email=" . urlencode($emailToCheck) . "&delete=yes' onclick=\"return confirm('Are you sure you want to delete this user?');\" style='color: red;'>Delete User</a></p>";
            echo "</div>";
        }
    } else {
        echo "<div class='success'>";
        echo "<h2>✓ Email Available</h2>";
        echo "<p>The email <strong>" . htmlspecialchars($emailToCheck) . "</strong> is not registered.</p>";
        echo "<p>You can proceed with registration.</p>";
        echo "<p><a href='auth/register.php'>Go to Registration Page</a></p>";
        echo "</div>";
    }
    
    // Show all registered users
    echo "<hr>";
    echo "<h2>All Registered Users:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll();
    
    if (empty($allUsers)) {
        echo "<p class='info'>No users found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        foreach ($allUsers as $u) {
            $highlight = ($u['email'] === $emailToCheck) ? " style='background: #ffffcc;'" : "";
            echo "<tr$highlight>";
            echo "<td>" . htmlspecialchars($u['id']) . "</td>";
            echo "<td>" . htmlspecialchars($u['name']) . "</td>";
            echo "<td>" . htmlspecialchars($u['email']) . "</td>";
            echo "<td>" . htmlspecialchars($u['role']) . "</td>";
            echo "<td>" . htmlspecialchars($u['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<p>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";

