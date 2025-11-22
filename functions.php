<?php

function get_questions($filename) {
    $questions = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ";", '"', "\\"); // Explicit escape char
        
        while (($data = fgetcsv($handle, 1000, ";", '"', "\\")) !== FALSE) {
            // Convert encoding if necessary
            $row = array_map(function($text) {
                // Check if already UTF-8
                if (mb_check_encoding($text, 'UTF-8')) {
                    return $text;
                }
                
                // Try MacRoman (detected from debug), then Windows-1250, then ISO-8859-2
                $encodings = ['MacRoman', 'Windows-1250', 'ISO-8859-2'];
                foreach ($encodings as $enc) {
                    try {
                        // Suppress warning for invalid encoding names on older PHP
                        $converted = @mb_convert_encoding($text, 'UTF-8', $enc);
                        if ($converted !== false) {
                            return $converted;
                        }
                    } catch (ValueError $e) {
                        continue;
                    }
                }
                return $text; // Fallback
            }, $data);

            // Support variable options (Question, Opt1, Opt2, [Opt3...], Correct)
            // Minimum 4 columns: Question, A, B, Correct
            if (count($row) >= 4) {
                $question_text = $row[0];
                $correct_answer = trim(end($row));
                
                // Extract options (from index 1 to second to last)
                $options_data = array_slice($row, 1, -1);
                $options = [];
                $keys = range('A', 'Z'); // A, B, C, D, E...
                
                foreach ($options_data as $index => $opt) {
                    if (isset($keys[$index])) {
                        $options[$keys[$index]] = $opt;
                    }
                }

                $questions[] = [
                    'question' => $question_text,
                    'options' => $options,
                    'correct' => $correct_answer
                ];
            }
        }
        fclose($handle);
    }
    return $questions;
}

require_once 'db.php';

function get_progress() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT question_hash, attempts, correct_count, wrong_count, last_result FROM progress WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $progress = [];
        while ($row = $stmt->fetch()) {
            $progress[$row['question_hash']] = [
                'attempts' => $row['attempts'],
                'correct' => $row['correct_count'],
                'wrong' => $row['wrong_count'],
                'last_result' => $row['last_result']
            ];
        }
        return $progress;
    } catch (PDOException $e) {
        return [];
    }
}

function save_progress($question_text, $is_correct) {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $key = md5($question_text);
    $last_result = $is_correct ? 'correct' : 'wrong';
    $correct_inc = $is_correct ? 1 : 0;
    $wrong_inc = $is_correct ? 0 : 1;
    
    try {
        $pdo = get_db_connection();
        $sql = "INSERT INTO progress (user_id, question_hash, attempts, correct_count, wrong_count, last_result) 
                VALUES (?, ?, 1, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, 
                correct_count = correct_count + ?, 
                wrong_count = wrong_count + ?, 
                last_result = ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'], $key, $correct_inc, $wrong_inc, $last_result,
            $correct_inc, $wrong_inc, $last_result
        ]);
    } catch (PDOException $e) {
        // Silently fail or log error
    }
}

function get_weighted_questions($all_questions, $count) {
    $progress = get_progress();
    
    $unseen = [];
    $wrong = [];
    $correct = [];
    
    foreach ($all_questions as $q) {
        $key = md5($q['question']);
        if (!isset($progress[$key])) {
            $unseen[] = $q;
        } elseif ($progress[$key]['last_result'] === 'wrong') {
            $wrong[] = $q;
        } else {
            $correct[] = $q;
        }
    }
    
    shuffle($unseen);
    shuffle($wrong);
    shuffle($correct);
    
    $selected = [];
    
    // Prioritize Unseen -> Wrong -> Correct
    // But we want a mix, mostly focusing on what needs practice.
    // Strategy: Fill with Unseen first, then Wrong, then Correct.
    
    foreach ($unseen as $q) {
        if (count($selected) < $count) $selected[] = $q;
    }
    
    foreach ($wrong as $q) {
        if (count($selected) < $count) $selected[] = $q;
    }
    
    foreach ($correct as $q) {
        if (count($selected) < $count) $selected[] = $q;
    }
    
    // If we still don't have enough (e.g. requested > total), just return what we have
    // or if we have enough, shuffle the final selection so they aren't ordered by type
    shuffle($selected);
    
    return $selected;
}

function send_invitation_email($recipient_email, $test_name, $inviter_name) {
    $subject = "Invitation to take a quiz: " . $test_name;
    $link = "https://app2.performancetuning.cz/index.php"; // Assuming this is the base URL
    $message = "Hello,\n\n" . 
               $inviter_name . " has invited you to take the quiz '" . $test_name . "'.\n\n" .
               "Please log in to accept the invitation and start the quiz:\n" . $link . "\n\n" .
               "Best regards,\nKuizio Team";
    $headers = "From: no-reply@quizio.cz\r\n" .
               "Reply-To: no-reply@quizio.cz\r\n" .
               "X-Mailer: PHP/" . phpversion();

    return mail($recipient_email, $subject, $message, $headers);
}
?>
