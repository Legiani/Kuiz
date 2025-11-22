<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];

    // Verify the ID token using Google's tokeninfo endpoint
    // In a production environment with Composer, use the Google API Client Library
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $id_token;
    $response = file_get_contents($url);

    if ($response) {
        $payload = json_decode($response, true);
        $config = require 'config.php';

        if (isset($payload['sub']) && isset($payload['aud']) && $payload['aud'] === $config['google_client_id']) {
            // User is verified
            require_once 'db.php';
            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("INSERT INTO users (google_id, email, name, picture) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = ?, picture = ?, last_login = CURRENT_TIMESTAMP");
                $stmt->execute([
                    $payload['sub'], $payload['email'], $payload['name'], $payload['picture'],
                    $payload['name'], $payload['picture']
                ]);
            } catch (PDOException $e) {
                // Handle error if needed
            }

            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $payload['sub'];
            $_SESSION['email'] = $payload['email'];
            $_SESSION['name'] = $payload['name'];
            $_SESSION['picture'] = $payload['picture'];

            header("Location: index.php");
            exit;
        }
    }
}

// If verification fails
header("Location: login.php?error=Google+Login+Failed");
exit;
?>
