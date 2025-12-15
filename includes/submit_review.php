<?php

require_once 'config.php';
require_once 'auth.php';



if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}


$conn = getDBConnection();
$check_query = "SELECT id FROM borrowing_records 
                WHERE user_id = ? AND book_id = ? 
                LIMIT 1";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You must borrow this book before reviewing it']);
    exit;
}


$review_check = "SELECT id FROM reviews 
                 WHERE user_id = ? AND book_id = ? 
                 LIMIT 1";
$stmt2 = $conn->prepare($review_check);
$stmt2->bind_param("ii", $user_id, $book_id);
$stmt2->execute();
$review_result = $stmt2->get_result();

if ($review_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this book']);
    exit;
}


$insert_query = "INSERT INTO reviews (user_id, book_id, rating, comment, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
$stmt3 = $conn->prepare($insert_query);
$stmt3->bind_param("iiis", $user_id, $book_id, $rating, $comment);

if ($stmt3->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review: ' . $conn->error]);
}

$conn->close();
?>