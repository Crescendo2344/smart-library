<?php
require_once 'config.php';
require_once 'auth.php';



if ($_SESSION['role'] !== 'librarian') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$record_id = $_POST['record_id'] ?? 0;

if ($record_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit;
}

$conn = getDBConnection();


$record_query = "SELECT br.*, b.title, DATEDIFF(CURDATE(), br.due_date) as days_overdue 
                 FROM borrowing_records br 
                 JOIN books b ON br.book_id = b.id 
                 WHERE br.id = ?";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

$record = $result->fetch_assoc();


$fine_applied = false;
$fine_amount = 0;

if ($record['days_overdue'] > 2) {
    $fine_days = $record['days_overdue'] - 2;
    $fine_amount = min($fine_days * 0.50, 25.00); 
    
    
    $fine_query = "INSERT INTO fines (borrowing_id, amount, reason, created_at) 
                   VALUES (?, ?, 'Overdue book return', NOW())";
    $stmt2 = $conn->prepare($fine_query);
    $stmt2->bind_param("id", $record_id, $fine_amount);
    $stmt2->execute();
    $fine_applied = true;
}


$update_query = "UPDATE borrowing_records SET 
                 status = 'returned', 
                 return_date = NOW(),
                 fine_applied = ?,
                 fine_amount = ?
                 WHERE id = ?";
$stmt3 = $conn->prepare($update_query);
$stmt3->bind_param("idi", $fine_applied, $fine_amount, $record_id);
$stmt3->execute();


$book_query = "UPDATE books SET copies_available = copies_available + 1 WHERE id = ?";
$stmt4 = $conn->prepare($book_query);
$stmt4->bind_param("i", $record['book_id']);
$stmt4->execute();

echo json_encode([
    'success' => true,
    'message' => 'Book marked as returned',
    'fine_applied' => $fine_applied,
    'fine_amount' => $fine_amount
]);

$conn->close();
?>