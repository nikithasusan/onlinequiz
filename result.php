<?php
session_start();

$servername = "localhost";
$username = "online_quiz_user";
$password = "secure_password";
$dbname = "online_quiz";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT q.id, q.question_text, a.id AS answer_id, a.answer_text, a.is_correct, ur.answer_id AS selected_answer FROM questions q INNER JOIN answers a ON q.id = a.question_id LEFT JOIN user_responses ur ON q.id = ur.question_id AND ur.user_id = ? ORDER BY q.id, a.id");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    $current_question = null;
    $passed = 0;
    $failed = 0;

    while ($row = $result->fetch_assoc()) {
        if ($current_question == null || $current_question['id'] != $row['id']) {
            if ($current_question != null) {
                $questions[] = $current_question;
                $correct_answer = false;
                foreach ($current_question['answers'] as $answer) {
                    if ($answer['selected'] && $answer['correct']) {
                        $correct_answer = true;
                    }
                }
                if ($correct_answer) {
                    $passed++;
                } else {
                    $failed++;
                }
            }
            $current_question = [
                'id' => $row['id'],
                'question_text' => $row['question_text'],
                'answers' => []
            ];
        }
        $current_question['answers'][] = [
            'id' => $row['answer_id'],
            'answer_text' => $row['answer_text'],
            'correct' => $row['is_correct'],
            'selected' => $row['answer_id'] == $row['selected_answer']
        ];
    }
    if ($current_question != null) {
        $questions[] = $current_question;
        $correct_answer = false;
        foreach ($current_question['answers'] as $answer) {
            if ($answer['selected'] && $answer['correct']) {
                $correct_answer = true;
            }
        }
        if ($correct_answer) {
            $passed++;
        } else {
            $failed++;
        }
    }

    echo json_encode(['status' => 'success', 'questions' => $questions, 'passed' => $passed, 'failed' => $failed]);
}

$conn->close();
?>
