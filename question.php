<?php
session_start();
$servername = "localhost";
$username = "online_quiz_user";
$password = "secure_password";
$dbname = "online_quiz";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];

    
    $totalQuestionsQuery = "SELECT COUNT(*) AS totalQuestions FROM questions";
    $totalQuestionsResult = $conn->query($totalQuestionsQuery);
    $totalQuestions = $totalQuestionsResult->fetch_assoc()['totalQuestions'];

    
    $stmt = $conn->prepare("SELECT q.id, q.question_text FROM questions q WHERE q.id NOT IN (SELECT question_id FROM user_responses WHERE user_id = ?) LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $question = $result->fetch_assoc();
        $stmt = $conn->prepare("SELECT id, answer_text FROM answers WHERE question_id = ?");
        $stmt->bind_param("i", $question['id']);
        $stmt->execute();
        $answers_result = $stmt->get_result();

        $answers = [];
        while ($row = $answers_result->fetch_assoc()) {
            $answers[] = $row;
        }

        echo json_encode(['status' => 'success', 'question' => $question, 'answers' => $answers, 'totalQuestions' => $totalQuestions]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No more questions.']);
    }

    $stmt->close();
}

$conn->close();
?>
