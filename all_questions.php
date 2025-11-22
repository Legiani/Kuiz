<?php
require_once 'auth.php';
require_once 'functions.php';
require_once 'db.php';

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

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

$selected_test_id = $_GET['test_id'] ?? ($tests[0]['id'] ?? 1);
$stmt = $pdo->prepare("SELECT filename FROM tests WHERE id = ?");
$stmt->execute([$selected_test_id]);
$test = $stmt->fetch();
$filename = $test ? 'tests/' . $test['filename'] : 'tests/out.csv';

$questions = get_questions($filename) ?: [];
$progress = get_progress();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Questions - Kuizio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="card p-4 shadow border-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>All Questions</h1>
                <div class="d-flex">
                    <form method="GET" class="d-flex me-2">
                        <select class="form-select me-2" name="test_id" onchange="this.form.submit()">
                            <?php foreach ($tests as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($selected_test_id == $t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <button id="toggleAnswers" class="btn btn-outline-secondary me-2">Show Answers</button>
                    <a href="download.php?test_id=<?php echo $selected_test_id; ?>" class="btn btn-outline-primary">Download CSV</a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th class="answer-col d-none">Correct Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $index => $q): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($q['question']); ?></td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($q['options'] as $key => $val): ?>
                                        <li class="answer-option <?php echo ($key === $q['correct']) ? 'correct-answer' : ''; ?>">
                                            <strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($val); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td class="text-center align-middle answer-col d-none">
                                <span class="badge bg-success fs-5"><?php echo $q['correct']; ?></span>
                                <?php
                                    $key = md5($q['question']);
                                    $stats = $progress[$key] ?? ['correct' => 0, 'wrong' => 0];
                                ?>
                                <div class="mt-2 fw-bold">
                                    <span class="text-danger"><?php echo $stats['wrong']; ?></span> / 
                                    <span class="text-success"><?php echo $stats['correct']; ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <style>
        .correct-answer {
            color: #198754;
            font-weight: bold;
        }
        /* Ensure the option text inherits the green color */
        .answer-option.correct-answer {
            color: #198754;
            font-weight: bold;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleAnswers').addEventListener('click', function() {
            const isHidden = document.querySelector('.answer-col').classList.contains('d-none');
            const cols = document.querySelectorAll('.answer-col');
            const table = document.querySelector('table');
            
            if (isHidden) {
                cols.forEach(el => el.classList.remove('d-none'));
                this.textContent = 'Hide Answers';
                this.classList.replace('btn-outline-secondary', 'btn-secondary');
            } else {
                cols.forEach(el => el.classList.add('d-none'));
                this.textContent = 'Show Answers';
                this.classList.replace('btn-secondary', 'btn-outline-secondary');
            }
        });
    </script>
</body>
</html>
