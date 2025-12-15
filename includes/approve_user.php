<?php
// includes/approve_user.php
require_once 'config.php';
require_once 'auth.php';

// Start session
session_start();

// Check if user is staff and logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $staff_id = $_SESSION['user_id'] ?? 0;
    
    if (empty($user_id) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit();
    }
    
    $conn = getDBConnection();
    
    if ($action === 'approve') {
        // Approve the user
        $stmt = $conn->prepare("UPDATE users SET approved = TRUE, approved_by = ?, approval_date = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $staff_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve user: ' . $conn->error]);
        }
        $stmt->close();
        
    } elseif ($action === 'reject') {
        // Delete the pending user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND approved = FALSE");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject user: ' . $conn->error]);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>