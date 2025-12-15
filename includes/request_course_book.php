<?php

require_once 'config.php';
require_once 'auth.php';



if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_SESSION['user_id'];
    $course_name = $_POST['course_name'] ?? '';
    $book_info = $_POST['book_info'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
   
}
?>