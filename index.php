<?php
require_once 'auth.php';
require_once 'db.php';

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Handle invitation acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_invite'])) {
    $invite_id = $_POST['invite_id'];
    $test_id = $_POST['test_id'];
    
    // Verify invitation belongs to user
    $stmt = $pdo->prepare("SELECT id FROM invitations WHERE id = ? AND recipient_email = ? AND status = 'pending'");
    $stmt->execute([$invite_id, $user_email]);
    
    if ($stmt->fetch()) {
        // Grant access
        $stmt = $pdo->prepare("INSERT IGNORE INTO test_access (user_id, test_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $test_id]);
        
        // Update invitation status
        $stmt = $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$invite_id]);
    }
}

// Fetch pending invitations
$stmt = $pdo->prepare("
    SELECT i.id, i.test_id, t.name as test_name, u.name as sender_name 
    FROM invitations i 
    JOIN tests t ON i.test_id = t.id 
    JOIN users u ON i.sender_id = u.google_id 
    WHERE i.recipient_email = ? AND i.status = 'pending'
");
$stmt->execute([$user_email]);
$invitations = $stmt->fetchAll();

// Fetch available tests (System + Own + Shared)
$stmt = $pdo->prepare("
    SELECT DISTINCT t.* 
    FROM tests t 
    LEFT JOIN test_access ta ON t.id = ta.test_id 
    WHERE t.uploaded_by = 'system' 
       OR t.uploaded_by = ? 
       OR (ta.user_id = ?)
    ORDER BY t.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$tests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Dashboard</h1>

        <?php if (!empty($invitations)): ?>
            <div class="alert alert-info mb-4">
                <h5>Pending Invitations</h5>
                <?php foreach ($invitations as $invite): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 bg-white p-2 rounded shadow-sm">
                        <span>
                            <strong><?php echo htmlspecialchars($invite['sender_name']); ?></strong> 
                            invited you to take 
                            <strong><?php echo htmlspecialchars($invite['test_name']); ?></strong>
                        </span>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['id']; ?>">
                            <input type="hidden" name="test_id" value="<?php echo $invite['test_id']; ?>">
                            <button type="submit" name="accept_invite" class="btn btn-sm btn-success">Accept</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($tests as $test): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($test['name']); ?></h5>
                            <p class="card-text text-muted small">
                                Uploaded by: <?php echo ($test['uploaded_by'] === 'system') ? 'System' : 'User'; ?>
                            </p>
                            <div class="mt-auto">
                                <a href="test_detail.php?test_id=<?php echo $test['id']; ?>" class="btn btn-primary w-100">Open Quiz</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Upload New Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow border-0 h-100 bg-light border-dashed" style="border-style: dashed !important;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                        <h5 class="card-title text-muted">Add New Test</h5>
                        <a href="upload_test.php" class="btn btn-outline-primary mt-3">Upload CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
