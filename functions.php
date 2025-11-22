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

            if (count($row) >= 5) {
                $questions[] = [
                    'question' => $row[0],
                    'options' => [
                        'A' => $row[1],
                        'B' => $row[2],
                        'C' => $row[3]
                    ],
                    'correct' => trim($row[4])
                ];
            }
        }
        fclose($handle);
    }
    return $questions;
}

function get_progress() {
    $filename = 'progress.json';
    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        return json_decode($json, true) ?? [];
    }
    return [];
}

function save_progress($question_text, $is_correct) {
    $progress = get_progress();
    $key = md5($question_text); // Use hash as key to avoid issues with special chars
    
    if (!isset($progress[$key])) {
        $progress[$key] = [
            'text' => $question_text, // Store text for debugging/reference
            'attempts' => 0,
            'correct' => 0,
            'wrong' => 0,
            'last_result' => null
        ];
    }
    
    $progress[$key]['attempts']++;
    if ($is_correct) {
        $progress[$key]['correct']++;
        $progress[$key]['last_result'] = 'correct';
    } else {
        $progress[$key]['wrong']++;
        $progress[$key]['last_result'] = 'wrong';
    }
    
    file_put_contents('progress.json', json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
?>
