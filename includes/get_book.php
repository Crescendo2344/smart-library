<?php
require_once 'config.php';
require_once 'auth.php';



if ($_SESSION['role'] !== 'librarian') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$book_id = $_GET['id'] ?? 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

$conn = getDBConnection();

$query = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

$book = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'book' => $book
]);

$conn->close();
?>