<?php
require_once 'auth.php';
require_once 'functions.php';
$questions = get_questions('out.csv');
$progress = get_progress();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Questions - Mamon Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mb-5">
        <div class="card p-4 shadow border-0">
            <h1 class="text-center mb-4">All Questions Database</h1>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th>Correct Answer</th>
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
                                        <li class="<?php echo ($key === $q['correct']) ? 'text-success fw-bold' : ''; ?>">
                                            <strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($val); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td class="text-center align-middle">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
