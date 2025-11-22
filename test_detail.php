<?php
require_once 'auth.php';
require_once 'db.php';

if (!isset($_GET['test_id'])) {
    header("Location: index.php");
    exit;
}

$test_id = $_GET['test_id'];
$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

// Fetch test details
$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
$stmt->execute([$test_id]);
$test = $stmt->fetch();

if (!$test) {
    die('Test not found');
}

// Check access
$has_access = false;
$is_owner = ($test['uploaded_by'] === $user_id);

if ($test['uploaded_by'] === 'system' || $is_owner) {
    $has_access = true;
} else {
    $stmt = $pdo->prepare("SELECT 1 FROM test_access WHERE user_id = ? AND test_id = ?");
    $stmt->execute([$user_id, $test_id]);
    if ($stmt->fetch()) {
        $has_access = true;
    }
}

if (!$has_access) {
    die('Access denied');
}

// Handle Invitation
$invite_message = '';
$invite_error = '';

require_once 'functions.php'; // Ensure functions are loaded

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner) {
    // Handle New Invitation
    if (isset($_POST['invite_email'])) {
        $recipient_email = trim($_POST['invite_email']);
        if (strpos($recipient_email, '@gmail.com') !== false) {
            try {
                // Check if already invited
                $stmt = $pdo->prepare("SELECT id FROM invitations WHERE test_id = ? AND recipient_email = ?");
                $stmt->execute([$test_id, $recipient_email]);
                
                if ($stmt->fetch()) {
                    $invite_error = 'Invitation already sent to this email.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO invitations (test_id, sender_id, recipient_email) VALUES (?, ?, ?)");
                    $stmt->execute([$test_id, $user_id, $recipient_email]);
                    
                    if (send_invitation_email($recipient_email, $test['name'], $_SESSION['name'])) {
                        $invite_message = 'Invitation sent successfully!';
                    } else {
                        $invite_message = 'Invitation saved, but email could not be sent (server configuration issue).';
                    }
                }
            } catch (PDOException $e) {
                $invite_error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $invite_error = 'Only @gmail.com addresses are allowed.';
        }
    }
    
    // Handle Resend Invitation
    if (isset($_POST['resend_invite_id'])) {
        $invite_id = $_POST['resend_invite_id'];
        try {
            $stmt = $pdo->prepare("SELECT recipient_email FROM invitations WHERE id = ? AND test_id = ?");
            $stmt->execute([$invite_id, $test_id]);
            $invite = $stmt->fetch();
            
            if ($invite) {
                if (send_invitation_email($invite['recipient_email'], $test['name'], $_SESSION['name'])) {
                    $invite_message = 'Invitation resent successfully!';
                } else {
                    $invite_message = 'Could not resend email (server configuration issue).';
                }
            }
        } catch (PDOException $e) {
            $invite_error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($test['name']); ?> - Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0 mb-4">
                    <div class="card-body p-5">
                        <h1 class="mb-3"><?php echo htmlspecialchars($test['name']); ?></h1>
                        <p class="text-muted mb-4">
                            Uploaded by: <?php echo ($test['uploaded_by'] === 'system') ? 'System' : 'User'; ?> <br>
                            Date: <?php echo $test['created_at']; ?>
                        </p>

                        <div class="d-grid gap-3">
                            <!-- Start Quiz Section -->
                            <div class="card bg-light border-0 p-3">
                                <h5>Start Quiz</h5>
                                <form action="quiz.php" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                    <select class="form-select" name="count" style="max-width: 200px;">
                                        <option value="5">5 Questions</option>
                                        <option value="10">10 Questions</option>
                                        <option value="15">15 Questions</option>
                                        <option value="20">20 Questions</option>
                                        <option value="25">25 Questions</option>
                                        <option value="50">50 Questions</option>
                                        <option value="100">100 Questions</option>
                                        <option value="all">All Questions</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary flex-grow-1">Start Now</button>
                                </form>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <a href="all_questions.php?test_id=<?php echo $test['id']; ?>" class="btn btn-outline-secondary flex-grow-1">View All Questions</a>
                                <a href="download.php?test_id=<?php echo $test['id']; ?>" class="btn btn-outline-secondary flex-grow-1">Download CSV</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invite Section (Owner Only) -->
                <?php if ($is_owner): ?>
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Invite Users</h4>
                        <?php if ($invite_message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($invite_message); ?></div>
                        <?php endif; ?>
                        <?php if ($invite_error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($invite_error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="d-flex gap-2 mb-4">
                            <input type="email" name="invite_email" class="form-control" placeholder="friend@gmail.com" required>
                            <button type="submit" class="btn btn-outline-primary">Send Invite</button>
                        </form>

                        <!-- Pending Invitations List -->
                        <?php
                        $stmt = $pdo->prepare("SELECT id, recipient_email, created_at FROM invitations WHERE test_id = ? AND status = 'pending' ORDER BY created_at DESC");
                        $stmt->execute([$test_id]);
                        $pending_invites = $stmt->fetchAll();
                        ?>
                        
                        <?php if (!empty($pending_invites)): ?>
                            <h5 class="mb-3">Pending Invitations</h5>
                            <div class="list-group">
                                <?php foreach ($pending_invites as $invite): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($invite['recipient_email']); ?></strong>
                                            <br>
                                            <small class="text-muted">Sent: <?php echo $invite['created_at']; ?></small>
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="resend_invite_id" value="<?php echo $invite['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Resend</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
