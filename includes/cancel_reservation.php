<?php
require_once 'config.php';
require_once 'auth.php';



header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $reservation_id = $_POST['reservation_id'] ?? '';
    
    if (empty($reservation_id)) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    try {
        // Check if reservation exists and belongs to user
        $reservation_query = $conn->prepare("SELECT r.*, b.title FROM reservations r JOIN books b ON r.book_id = b.id WHERE r.id = ? AND r.user_id = ?");
        $reservation_query->bind_param("ii", $reservation_id, $user_id);
        $reservation_query->execute();
        $reservation_result = $reservation_query->get_result();
        
        if ($reservation_result->num_rows === 0) {
            throw new Exception('Reservation not found');
        }
        
        $reservation = $reservation_result->fetch_assoc();
        
        // Update reservation status to cancelled
        $update_stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
        $update_stmt->bind_param("i", $reservation_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation cancelled successfully'
            ]);
        } else {
            throw new Exception('Failed to cancel reservation: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>