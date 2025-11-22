<?php
require_once 'auth.php';
session_start();
require_once 'functions.php';

// Initialize Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num_questions'])) {
    $all_questions = get_questions('out.csv');
    $num_questions = min((int)$_POST['num_questions'], count($all_questions));
    
    // Use weighted selection to prioritize unseen and wrong answers
    $selected_questions = get_weighted_questions($all_questions, $num_questions);
    
    $_SESSION['questions'] = $selected_questions;
    $_SESSION['current_index'] = 0;
    $_SESSION['answers'] = array_fill(0, $num_questions, null);
    
    header("Location: quiz.php");
    exit;
}

// Handle Navigation and Answer Saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $current_index = $_SESSION['current_index'];
    
    // Save answer if provided
    if (isset($_POST['answer'])) {
        $_SESSION['answers'][$current_index] = $_POST['answer'];
    }

    if ($_POST['action'] === 'next') {
        if ($current_index < count($_SESSION['questions']) - 1) {
            $_SESSION['current_index']++;
        }
    } elseif ($_POST['action'] === 'prev') {
        if ($current_index > 0) {
            $_SESSION['current_index']--;
        }
    } elseif ($_POST['action'] === 'finish') {
        header("Location: result.php");
        exit;
    }
    
    header("Location: quiz.php");
    exit;
}

// Redirect if no session
if (!isset($_SESSION['questions'])) {
    header("Location: index.php");
    exit;
}

$current_index = $_SESSION['current_index'];
$question = $_SESSION['questions'][$current_index];
$total_questions = count($_SESSION['questions']);
$current_answer = $_SESSION['answers'][$current_index];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mamon Quiz - Question <?php echo $current_index + 1; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-3 shadow border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-muted">Question <?php echo $current_index + 1; ?> of <?php echo $total_questions; ?></h4>
                        <form action="quiz.php" method="POST" class="d-inline">
                             <button type="submit" name="action" value="finish" class="btn btn-danger btn-sm">Finish Quiz</button>
                        </form>
                    </div>
                    
                    <h2 class="mb-3"><?php echo htmlspecialchars($question['question']); ?></h2>
                    
                    <form action="quiz.php" method="POST">
                        <div class="list-group mb-3">
                            <?php foreach ($question['options'] as $key => $text): ?>
                                <label class="list-group-item d-flex align-items-center list-group-item-action <?php echo ($current_answer === $key) ? 'active' : ''; ?>">
                                    <input class="form-check-input me-3" type="radio" name="answer" value="<?php echo $key; ?>" <?php echo ($current_answer === $key) ? 'checked' : ''; ?>>
                                    <span class="fs-5"><?php echo htmlspecialchars($text); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <?php if ($current_index > 0): ?>
                                <button type="submit" name="action" value="prev" class="btn btn-secondary">Previous</button>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            
                            <?php if ($current_index < $total_questions - 1): ?>
                                <button type="submit" name="action" value="next" class="btn btn-primary">Next</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="finish" class="btn btn-success">Finish</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
