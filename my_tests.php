<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$error = '';

// Handle sending invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_email'])) {
    $test_id = $_POST['test_id'];
    $recipient_email = trim($_POST['invite_email']);
    
    if (strpos($recipient_email, '@gmail.com') !== false) {
        try {
            $pdo = get_db_connection();
            // Check if already invited
            $stmt = $pdo->prepare("SELECT id FROM invitations WHERE test_id = ? AND recipient_email = ?");
            $stmt->execute([$test_id, $recipient_email]);
            
            if ($stmt->fetch()) {
                $error = 'Invitation already sent to this email.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO invitations (test_id, sender_id, recipient_email) VALUES (?, ?, ?)");
                $stmt->execute([$test_id, $_SESSION['user_id'], $recipient_email]);
                $message = 'Invitation sent successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Only @gmail.com addresses are allowed.';
    }
}

// Fetch user's tests
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT * FROM tests WHERE uploaded_by = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_tests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tests - Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">My Tests</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <?php foreach ($my_tests as $test): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow border-0">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($test['name']); ?></h5>
                            <p class="card-text text-muted">Uploaded: <?php echo $test['created_at']; ?></p>
                            
                            <form method="POST" class="d-flex mt-3">
                                <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                <input type="email" name="invite_email" class="form-control me-2" placeholder="friend@gmail.com" required>
                                <button type="submit" class="btn btn-outline-primary me-2">Invite</button>
                                <a href="download.php?test_id=<?php echo $test['id']; ?>" class="btn btn-outline-secondary">Download</a>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($my_tests)): ?>
                <div class="col-12">
                    <p class="text-muted">You haven't uploaded any tests yet. <a href="upload_test.php">Upload one now!</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
