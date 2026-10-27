<?php
require_once __DIR__ . '/../includes/init.php';

try {
    // Delete if exists first
    db()->prepare("DELETE FROM users WHERE email = 'user@example.com'")->execute();

    $hashed = password_hash('Password123', PASSWORD_BCRYPT);
    $stmt = db()->prepare("INSERT INTO users (name, email, password, role) VALUES ('Test User', 'user@example.com', :password, 'customer')");
    $stmt->execute([':password' => $hashed]);
    echo "Inserted customer user successfully\n";
} catch (Exception $e) {
    echo "Error inserting user: " . $e->getMessage() . "\n";
}
