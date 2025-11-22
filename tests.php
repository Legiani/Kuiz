<?php
require_once 'functions.php';

function assert_true($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

echo "Starting Tests...\n\n";

// 1. Test CSV Loading
$questions = get_questions('out.csv');
assert_true(count($questions) > 0, "CSV loaded successfully. Count: " . count($questions));
if (count($questions) > 0) {
    assert_true(isset($questions[0]['question']), "Question text exists");
    assert_true(isset($questions[0]['options']['A']), "Option A exists");
    assert_true(isset($questions[0]['correct']), "Correct answer exists");
}

// 2. Test Progress Saving/Loading
// Clean up old test data
if (file_exists('progress.json')) {
    copy('progress.json', 'progress.json.bak'); // Backup real progress
    unlink('progress.json');
}

$test_q = "Test Question 1";
save_progress($test_q, true);
$progress = get_progress();
$key = md5($test_q);

assert_true(isset($progress[$key]), "Progress saved for question");
assert_true($progress[$key]['correct'] === 1, "Correct count incremented");
assert_true($progress[$key]['last_result'] === 'correct', "Last result is correct");

save_progress($test_q, false);
$progress = get_progress();
assert_true($progress[$key]['wrong'] === 1, "Wrong count incremented");
assert_true($progress[$key]['last_result'] === 'wrong', "Last result is wrong");

// 3. Test Weighted Selection
// Create a dummy set of questions
$dummy_questions = [
    ['question' => 'Q1', 'options' => [], 'correct' => 'A'],
    ['question' => 'Q2', 'options' => [], 'correct' => 'A'],
    ['question' => 'Q3', 'options' => [], 'correct' => 'A'],
];

// Mock progress for Q1 (Wrong) and Q2 (Correct)
// We need to manually manipulate the json file or use save_progress
// Clear progress first
unlink('progress.json');
save_progress('Q1', false); // Wrong
save_progress('Q2', true);  // Correct
// Q3 is unseen

$selected = get_weighted_questions($dummy_questions, 3);
assert_true(count($selected) === 3, "Selected 3 questions");

// Check if Q3 (Unseen) and Q1 (Wrong) are prioritized? 
// The logic is: Unseen -> Wrong -> Correct.
// Since we requested 3 and have 3, we should get all of them.
// Let's try requesting 1. It should be Q3 (Unseen).
$selected_1 = get_weighted_questions($dummy_questions, 1);
assert_true($selected_1[0]['question'] === 'Q3', "Unseen question prioritized (Got " . $selected_1[0]['question'] . ")");

// Now mark Q3 as correct.
save_progress('Q3', true);
// Now Unseen is empty. Wrong is Q1. Correct is Q2, Q3.
// Request 1. Should be Q1.
$selected_2 = get_weighted_questions($dummy_questions, 1);
assert_true($selected_2[0]['question'] === 'Q1', "Wrong question prioritized (Got " . $selected_2[0]['question'] . ")");


// Restore backup
if (file_exists('progress.json.bak')) {
    rename('progress.json.bak', 'progress.json');
} else {
    unlink('progress.json');
}

echo "\nTests Completed.\n";
?>
