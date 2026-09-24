<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config/database.php';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

try {
    $stmt = $pdo->prepare("
    SELECT *
    FROM questions
    WHERE category_id = ?
    ORDER BY id DESC
");

$stmt->execute([$category_id]);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

foreach ($questions as $q) {

    $ans_stmt = $pdo->prepare("
        SELECT answer_text, is_correct
        FROM answers
        WHERE question_id = ?
        ORDER BY id
    ");
    $ans_stmt->execute([$q['id']]);

    $answers_data = $ans_stmt->fetchAll(PDO::FETCH_ASSOC);

    $answers = [];
    $correct = 0;

    foreach ($answers_data as $i => $ans) {
        $answers[] = $ans['answer_text'];

        if ($ans['is_correct']) {
            $correct = $i;
        }
    }

    $result[] = [
        "question" => $q["question_text"],
        "answers" => $answers,
        "correct" => $correct
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>