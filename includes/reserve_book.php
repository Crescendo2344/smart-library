<?php

require_once 'config.php';
require_once 'auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to reserve books']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}


$user_id = $_SESSION['user_id'];


$conn = getDBConnection();


$book_query = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($book_query);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book_result = $stmt->get_result();

if ($book_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

$book = $book_result->fetch_assoc();


if ($book['copies_available'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'This book is currently available. Would you like to borrow it instead?',
        'available' => true
    ]);
    exit;
}


$existing_reservation_query = "SELECT id FROM reservations 
                              WHERE user_id = ? AND book_id = ? AND status = 'active'";
$stmt2 = $conn->prepare($existing_reservation_query);
$stmt2->bind_param("ii", $user_id, $book_id);
$stmt2->execute();
$existing_reservation = $stmt2->get_result();

if ($existing_reservation->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have an active reservation for this book']);
    exit;
}


$active_reservations_query = "SELECT COUNT(*) as active_count FROM reservations 
                             WHERE user_id = ? AND status = 'active'";
$stmt3 = $conn->prepare($active_reservations_query);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$active_result = $stmt3->get_result();
$active_data = $active_result->fetch_assoc();

$max_reservations = 3; 
if ($active_data['active_count'] >= $max_reservations) {
    echo json_encode(['success' => false, 'message' => 'You have reached the maximum number of active reservations (3)']);
    exit;
}

$queue_query = "SELECT COUNT(*) as queue_position FROM reservations 
               WHERE book_id = ? AND status = 'active'";
$stmt4 = $conn->prepare($queue_query);
$stmt4->bind_param("i", $book_id);
$stmt4->execute();
$queue_result = $stmt4->get_result();
$queue_data = $queue_result->fetch_assoc();
$queue_position = $queue_data['queue_position'];


$expiry_date = date('Y-m-d H:i:s', strtotime('+7 days'));
$reservation_query = "INSERT INTO reservations (user_id, book_id, reservation_date, expiry_date, status) 
                     VALUES (?, ?, NOW(), ?, 'active')";
$stmt5 = $conn->prepare($reservation_query);
$stmt5->bind_param("iis", $user_id, $book_id, $expiry_date);

if ($stmt5->execute()) {
    $reservation_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Book reserved successfully',
        'reservation_id' => $reservation_id,
        'expiry_date' => date('M d, Y', strtotime($expiry_date)),
        'queue_position' => $queue_position + 1 
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create reservation: ' . $conn->error]);
}

$conn->close();
?>