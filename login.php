<?php
session_start();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: none;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="card p-4">
        <h2 class="text-center mb-4">Login Required</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

<?php
require_once 'config.php';
$config = require 'config.php';
?>
        <!-- Google Sign-In Configuration -->
        <div id="g_id_onload"
             data-client_id="<?php echo htmlspecialchars($config['google_client_id']); ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="<?php echo htmlspecialchars($config['google_login_uri']); ?>"
             data-auto_prompt="false">
        </div>

        <div class="d-flex justify-content-center">
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>
        </div>

        <div class="text-center my-3">
            <p class="text-muted">Please sign in to continue.</p>
        </div>
    </div>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>
