<?php
require_once 'config.php';
require_once 'auth.php';



if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $record_id = $_POST['record_id'] ?? '';
    
    if (empty($record_id)) {
        echo json_encode(['success' => false, 'message' => 'Record ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if record exists and belongs to user
        $record_query = $conn->prepare("SELECT br.*, b.title, b.id as book_id FROM borrowing_records br JOIN books b ON br.book_id = b.id WHERE br.id = ? AND br.user_id = ? AND br.status = 'borrowed'");
        $record_query->bind_param("ii", $record_id, $user_id);
        $record_query->execute();
        $record_result = $record_query->get_result();
        
        if ($record_result->num_rows === 0) {
            throw new Exception('Borrowing record not found or already returned');
        }
        
        $record = $record_result->fetch_assoc();
        $book_title = $record['title'];
        $book_id = $record['book_id'];
        
        // Check if book is overdue
        $due_date = new DateTime($record['due_date']);
        $today = new DateTime();
        $is_overdue = $today > $due_date;
        $fine_amount = 0;
        $fine_applied = false;
        $fine_id = null;
        
        if ($is_overdue) {
            $days_overdue = $today->diff($due_date)->days;
            // $0.50 per day after 2-day grace period
            $grace_period = 2;
            $daily_rate = 0.50;
            $max_fine = 25.00;
            
            if ($days_overdue > $grace_period) {
                $fine_days = $days_overdue - $grace_period;
                $fine_amount = min($fine_days * $daily_rate, $max_fine);
                $fine_applied = true;
            }
        }
        
        // Check if fine already exists for this borrowing
        $fine_check = $conn->prepare("SELECT id, amount, paid FROM fines WHERE borrowing_id = ?");
        $fine_check->bind_param("i", $record_id);
        $fine_check->execute();
        $fine_result = $fine_check->get_result();
        
        if ($fine_result->num_rows > 0) {
            $existing_fine = $fine_result->fetch_assoc();
            $fine_id = $existing_fine['id'];
            
            if ($existing_fine['paid'] == 0) {
                // Update existing unpaid fine if amount differs
                if ($existing_fine['amount'] != $fine_amount) {
                    $update_fine = $conn->prepare("UPDATE fines SET amount = ? WHERE id = ?");
                    $update_fine->bind_param("di", $fine_amount, $fine_id);
                    $update_fine->execute();
                }
            } else {
                // Fine already paid, no new fine
                $fine_amount = 0;
                $fine_applied = false;
            }
        } else if ($fine_amount > 0) {
            // Create new fine record
            $create_fine = $conn->prepare("INSERT INTO fines (user_id, borrowing_id, amount, reason) VALUES (?, ?, ?, 'Overdue fine for late return')");
            $create_fine->bind_param("iid", $user_id, $record_id, $fine_amount);
            $create_fine->execute();
            $fine_id = $conn->insert_id;
        }
        
        // Update borrowing record to returned
        $update_stmt = $conn->prepare("UPDATE borrowing_records SET status = 'returned', return_date = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $record_id);
        $update_stmt->execute();
        
        // Update book availability
        $book_update = $conn->prepare("UPDATE books SET copies_available = copies_available + 1 WHERE id = ?");
        $book_update->bind_param("i", $book_id);
        $book_update->execute();
        
        // Log the activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'return_book', ?)");
        $log_details = "Returned book: " . $book_title . " (Record ID: {$record_id})" . ($fine_applied ? " with fine: ₱" . number_format($fine_amount, 2) : "");
        $log_stmt->bind_param("is", $user_id, $log_details);
        $log_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book returned successfully' . ($fine_applied ? ' (Fine applied)' : ''),
            'fine_applied' => $fine_applied,
            'fine_amount' => $fine_amount,
            'fine_id' => $fine_id,
            'reason' => $fine_applied ? 'Overdue return' : 'On time return',
            'book_title' => $book_title
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>