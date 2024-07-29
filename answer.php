<?php
session_start();

$servername = "localhost";
$username = "online_quiz_user";
$password = "secure_password";
$dbname = "online_quiz";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $answer_id = isset($_POST['answer']) ? intval($_POST['answer']) : null;

    if ($user_id === null || $answer_id === null) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
        $conn->close();
        exit();
    }

    $stmt = $conn->prepare("SELECT question_id FROM answers WHERE id = ?");
    $stmt->bind_param("i", $answer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $question = $result->fetch_assoc();

    if ($question) {
        $stmt = $conn->prepare("INSERT INTO user_responses (user_id, question_id, answer_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $question['question_id'], $answer_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Answer submission failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid answer ID.']);
    }

    $stmt->close();
}

$conn->close();
?>
