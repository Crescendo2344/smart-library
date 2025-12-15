<?php

require_once 'config.php';
require_once 'auth.php';



if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'] ?? null;
$course_name = $_POST['course_name'] ?? null;
$semester = $_POST['semester'] ?? null;

if (!$book_id || !$course_name) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn = getDBConnection();


$check_query = "SELECT id FROM course_materials 
                WHERE teacher_id = ? AND book_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending request for this book']);
    exit;
}


$insert_query = "INSERT INTO course_materials (teacher_id, book_id, course_name, semester, request_date, status) 
                 VALUES (?, ?, ?, ?, NOW(), 'pending')";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iiss", $user_id, $book_id, $course_name, $semester);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Course material request submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>