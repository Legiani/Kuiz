<?php
require_once 'auth.php';
require_once 'db.php';

if (!isset($_GET['test_id'])) {
    die('Test ID required');
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

// 1. System test (public)
if ($test['uploaded_by'] === 'system') {
    $has_access = true;
}
// 2. Own test
elseif ($test['uploaded_by'] === $user_id) {
    $has_access = true;
}
// 3. Shared test
else {
    $stmt = $pdo->prepare("SELECT 1 FROM test_access WHERE user_id = ? AND test_id = ?");
    $stmt->execute([$user_id, $test_id]);
    if ($stmt->fetch()) {
        $has_access = true;
    }
}

if (!$has_access) {
    die('Access denied');
}

// Serve file
$filepath = 'tests/' . $test['filename'];

if (!file_exists($filepath)) {
    die('File not found');
}

// Clean filename for download
$download_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $test['name']) . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
?>
