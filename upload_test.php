<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileSize = $_FILES['csv_file']['size'];
        $fileType = $_FILES['csv_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if ($fileExtension === 'csv') {
            $newFileName = md5(time() . $fileName) . '.csv';
            $uploadFileDir = './tests/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                try {
                    $pdo = get_db_connection();
                    $stmt = $pdo->prepare("INSERT INTO tests (name, filename, uploaded_by) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $newFileName, $_SESSION['user_id'] ?? 'unknown']);
                    $message = 'Test uploaded successfully!';
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error = 'Error moving uploaded file.';
            }
        } else {
            $error = 'Upload failed. Allowed file types: .csv';
        }
    } else {
        $error = 'No file uploaded or upload error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Test - Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Upload New Test</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Test Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Upload Test</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
