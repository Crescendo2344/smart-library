<?php
require_once 'config.php';
require_once 'auth.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $book_id = $_POST['book_id'] ?? '';
    
    if (empty($book_id)) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Check if book exists and is available
    $book_check = $conn->prepare("SELECT copies_available FROM books WHERE id = ?");
    $book_check->bind_param("i", $book_id);
    $book_check->execute();
    $book_result = $book_check->get_result();
    
    if ($book_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit();
    }
    
    $book = $book_result->fetch_assoc();
    if ($book['copies_available'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No copies available']);
        exit();
    }
    
    // Check user's borrowing limit based on role
    $user_role = $_SESSION['role'];
    $borrowing_limits = [
        'student' => 3,
        'teacher' => 999,
        'librarian' => 0,
        'staff' => 0
    ];
    $user_limit = $borrowing_limits[$user_role] ?? 5;
    
    $current_borrowings = $conn->prepare("SELECT COUNT(*) as count FROM borrowing_records WHERE user_id = ? AND status = 'borrowed'");
    $current_borrowings->bind_param("i", $user_id);
    $current_borrowings->execute();
    $borrowings_result = $current_borrowings->get_result();
    $borrowings = $borrowings_result->fetch_assoc();
    
    if ($borrowings['count'] >= $user_limit) {
        echo json_encode(['success' => false, 'message' => "You have reached your borrowing limit of {$user_limit} books"]);
        exit();
    }
    
    // Set borrowing period based on role
    $borrowing_periods = [
        'student' => 14,
        'teacher' => 30,
        'librarian' => 21,
        'staff' => 30
    ];
    $period_days = $borrowing_periods[$user_role] ?? 14;
    $due_date = date('Y-m-d', strtotime("+{$period_days} days"));
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create borrowing record
        $borrow_stmt = $conn->prepare("INSERT INTO borrowing_records (user_id, book_id, due_date, status) VALUES (?, ?, ?, 'borrowed')");
        $borrow_stmt->bind_param("iis", $user_id, $book_id, $due_date);
        $borrow_stmt->execute();
        
        // Update book availability
        $update_stmt = $conn->prepare("UPDATE books SET copies_available = copies_available - 1 WHERE id = ?");
        $update_stmt->bind_param("i", $book_id);
        $update_stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book borrowed successfully',
            'due_date' => date('M d, Y', strtotime($due_date))
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
}
?>