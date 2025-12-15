<?php
require_once 'config.php';


function registerUser($username, $email, $password, $full_name, $role) {
    $conn = getDBConnection();
    
   
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
   
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, approved) VALUES (?, ?, ?, ?, ?, FALSE)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return true;
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return $error;
    }
}


function loginUser($username, $password) {
    $conn = getDBConnection();
    
   
    $stmt = $conn->prepare("SELECT id, username, password, role, approved FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
  
        if (password_verify($password, $user['password'])) {
            
            if ($user['approved'] == 1) {
               
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['approved'] = true;
                
                $stmt->close();
                $conn->close();
                return true;
            } else {
                $stmt->close();
                $conn->close();
                return "account_pending";
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}


function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['approved']) && $_SESSION['approved'] == true;
}


function redirectByRole() {
    if (isLoggedIn()) {
        $role = $_SESSION['role'];
        
      
        $dashboardMap = [
            'student' => 'student.php',
            'teacher' => 'teacher.php', 
            'librarian' => 'librarian.php',
            'staff' => 'staff.php'
        ];
        
        if (isset($dashboardMap[$role])) {
            
            header("Location: ../dashboards/" . $dashboardMap[$role]);
            exit();
        } else {
           
            header("Location: ../dashboards/dashboard.php");
            exit();
        }
    }
}


function getPendingUsers() {
    $conn = getDBConnection();
    $sql = "SELECT id, username, email, full_name, role, registration_date FROM users WHERE approved = FALSE ORDER BY registration_date ASC";
    $result = $conn->query($sql);
    
    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}


function approveUser($user_id, $approved_by) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET approved = TRUE, approved_by = ?, approval_date = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $approved_by, $user_id);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}


function rejectUser($user_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND approved = FALSE");
    $stmt->bind_param("i", $user_id);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

function getFinesSummary($conn, $start_date, $end_date) {
    $query = "SELECT 
        SUM(CASE WHEN paid = TRUE THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN paid = FALSE THEN amount ELSE 0 END) as total_unpaid,
        COUNT(CASE WHEN paid = TRUE THEN 1 END) as paid_count,
        COUNT(CASE WHEN paid = FALSE THEN 1 END) as unpaid_count,
        AVG(amount) as avg_fine_amount
    FROM fines
    WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getTopFinesUsers($conn, $start_date, $end_date) {
    $query = "SELECT 
        u.id,
        u.username,
        u.full_name,
        COUNT(f.id) as fine_count,
        SUM(f.amount) as total_fines,
        SUM(CASE WHEN f.paid = TRUE THEN f.amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN f.paid = FALSE THEN f.amount ELSE 0 END) as unpaid_amount
    FROM users u
    JOIN fines f ON u.id = f.user_id
    WHERE f.created_at BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_fines DESC
    LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

?>