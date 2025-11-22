<?php
function get_db_connection() {
    $config = require 'config.php';
    
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        return new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
