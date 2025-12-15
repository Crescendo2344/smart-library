<?php
require_once 'config.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $book_id = $_POST['book_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if (empty($book_id)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Check if book is available
    $check_query = "SELECT copies_available FROM books WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit();
    }
    
    $book = $result->fetch_assoc();
    
    if ($book['copies_available'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No copies available']);
        exit();
    }
    
    // Calculate due date (14 days from now for students, 30 for teachers)
    $role = $_SESSION['role'];
    $due_days = $role === 'teacher' ? 30 : 14;
    $due_date = date('Y-m-d', strtotime("+$due_days days"));
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert borrowing record
        $borrow_query = "INSERT INTO borrowing_records (user_id, book_id, due_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($borrow_query);
        $stmt->bind_param("iis", $user_id, $book_id, $due_date);
        $stmt->execute();
        
        // Update available copies
        $update_query = "UPDATE books SET copies_available = copies_available - 1 WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Book borrowed successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
}
?>