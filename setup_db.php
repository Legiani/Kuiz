<?php
require_once 'db.php';

try {
    $pdo = get_db_connection();
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        google_id VARCHAR(255) PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255),
        picture VARCHAR(255),
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Table 'users' created successfully.<br>";
    
    // Create progress table
    // Using google_id as foreign key reference
    $pdo->exec("CREATE TABLE IF NOT EXISTS progress (
        user_id VARCHAR(255),
        question_hash VARCHAR(32),
        attempts INT DEFAULT 0,
        correct_count INT DEFAULT 0,
        wrong_count INT DEFAULT 0,
        last_result VARCHAR(20),
        PRIMARY KEY (user_id, question_hash),
        FOREIGN KEY (user_id) REFERENCES users(google_id) ON DELETE CASCADE
    )");
    echo "Table 'progress' created successfully.<br>";
    
    // Create tests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        uploaded_by VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'tests' created successfully.<br>";

    // Insert default test if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM tests");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO tests (name, filename, uploaded_by) VALUES ('Default Test', 'out.csv', 'system')");
        echo "Default test inserted.<br>";
    }

    // Create invitations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_id INT NOT NULL,
        sender_id VARCHAR(255) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
    )");
    echo "Table 'invitations' created successfully.<br>";

    // Create test_access table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_access (
        user_id VARCHAR(255) NOT NULL,
        test_id INT NOT NULL,
        PRIMARY KEY (user_id, test_id),
        FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
    )");
    echo "Table 'test_access' created successfully.<br>";
    
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
