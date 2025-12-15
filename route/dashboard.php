<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and approved
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Get user role
$role = $_SESSION['role'];

// Redirect to appropriate dashboard
switch ($role) {
    case 'student':
        include '/student.php';
        break;
    case 'teacher':
        include '/teacher.php';
        break;
    case 'librarian':
        include '/librarian.php';
        break;
    case 'staff':
        include '/staff.php';
        break;
    default:
        // If role is not recognized, log out
        session_destroy();
        header("Location: login.php");
        exit();
}
?>