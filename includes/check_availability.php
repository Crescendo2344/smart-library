<?php
require_once 'config.php';
require_once 'auth.php';



header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'] ?? '';
    
    if (empty($book_id)) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
  
    $book_query = $conn->prepare("SELECT copies_available FROM books WHERE id = ?");
    $book_query->bind_param("i", $book_id);
    $book_query->execute();
    $book_result = $book_query->get_result();
    
    if ($book_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit();
    }
    
    $book = $book_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'available' => $book['copies_available'] > 0,
        'copies_available' => $book['copies_available']
    ]);
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>