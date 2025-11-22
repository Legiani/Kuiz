<?php
require_once 'auth.php';
session_start();
require_once 'functions.php';

if (!isset($_SESSION['questions']) || !isset($_SESSION['answers'])) {
    header("Location: index.php");
    exit;
}

$questions = $_SESSION['questions'];
$answers = $_SESSION['answers'];
$score = 0;
$total = count($questions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mamon Quiz - Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card p-4 shadow border-0">
                    <h1 class="text-center mb-4">Quiz Results</h1>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Options</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $index => $q): 
                                    $user_ans_key = $answers[$index];
                                    $correct_key = $q['correct'];
                                    $is_correct = ($user_ans_key === $correct_key);
                                    if ($is_correct) $score++;
                                    
                                    // Save progress
                                    save_progress($q['question'], $is_correct);
                                    
                                    $row_class = $is_correct ? 'table-success' : 'table-danger';
                                    
                                    $user_ans_text = $user_ans_key ? $q['options'][$user_ans_key] : '<span class="text-muted">No Answer</span>';
                                    $correct_ans_text = $q['options'][$correct_key];
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($q['question']); ?></td>
                                    <td>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php foreach ($q['options'] as $key => $val): ?>
                                                <li class="<?php echo ($key === $q['correct']) ? 'text-success fw-bold' : ''; ?>">
                                                    <strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($val); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <?php if ($is_correct): ?>
                                            <span class="badge bg-success">Correct</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Incorrect</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <h2>Score: <?php echo $score; ?> / <?php echo $total; ?></h2>
                        <a href="index.php" class="btn btn-primary btn-lg mt-3">Start New Quiz</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
