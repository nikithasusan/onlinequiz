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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $answers = json_decode(file_get_contents('php://input'), true);

    $conn->begin_transaction();


    try {
        foreach ($answers as $question => $answer) {
            $question_id = substr($question, 1);
            
            $stmt = $conn->prepare("SELECT * FROM user_responses WHERE user_id = ? AND question_id = ?");
            $stmt->bind_param("ii", $user_id, $question_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // If it exists, update the answer
                $stmt = $conn->prepare("UPDATE user_responses SET answer_id = ? WHERE user_id = ? AND question_id = ?");
                $stmt->bind_param("iii", $answer, $user_id, $question_id);
                $stmt->execute();
            } else {
                // If it doesn't exist, insert a new response
                $stmt = $conn->prepare("INSERT INTO user_responses (user_id, question_id, answer_id) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $question_id, $answer);
                $stmt->execute();
            }
        }
        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to confirm answers.']);
    }
}

$conn->close();
?>
