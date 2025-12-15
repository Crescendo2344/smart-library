<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Only staff can access this page
if ($_SESSION['role'] !== 'staff') {
    header("Location: dashboard.php");
    exit();
}

// Get pending users for approval
$pending_users = getPendingUsers();

// Get all users
$conn = getDBConnection();
$all_users_query = "SELECT 
    u.id, 
    u.username, 
    u.email, 
    u.full_name, 
    u.role, 
    u.approved, 
    u.registration_date,
    u.approval_date,
    s.username as approved_by_username,
    s.full_name as approved_by_name
FROM users u
LEFT JOIN users s ON u.approved_by = s.id
ORDER BY u.registration_date DESC 
LIMIT 20";
$all_users = $conn->query($all_users_query);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE approved = FALSE) as pending_users,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM books) as total_books,
    (SELECT COUNT(*) FROM borrowing_records WHERE status = 'borrowed') as borrowed_books";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-book"></i> Library System
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="role-badge staff-badge">Staff</span>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-header">
            <h1>Staff Dashboard</h1>
            <p>Manage user accounts, approvals, and system overview</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user-clock"></i>
                    <h3>Pending Approvals</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['pending_users']; ?></div>
                    <div class="stat-label">Users Waiting Approval</div>
                </div>
                <div class="card-actions">
                    <a href="#pending-approvals" class="action-btn">Review Now</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users"></i>
                    <h3>Total Users</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
                <div class="card-actions">
                    <a href="#all-users" class="action-btn">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book"></i>
                    <h3>Total Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                    <div class="stat-label">In Library</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-exchange-alt"></i>
                    <h3>Borrowed Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['borrowed_books']; ?></div>
                    <div class="stat-label">Currently Borrowed</div>
                </div>
            </div>
        </div>
        
        <!-- Pending User Approvals -->
        <div class="table-container" id="pending-approvals">
            <h2><i class="fas fa-user-check"></i> Pending User Approvals</h2>
            <?php if (count($pending_users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_users as $user): ?>
                    <tr id="user-row-<?php echo $user['id']; ?>">
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge <?php echo $user['role']; ?>-badge"><?php echo ucfirst($user['role']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                        <td class="action-buttons">
                            <button class="btn-small btn-approve" onclick="handleUserAction(<?php echo $user['id']; ?>, 'approve')">Approve</button>
                            <button class="btn-small btn-reject" onclick="handleUserAction(<?php echo $user['id']; ?>, 'reject')">Reject</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No pending user approvals at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- All Users -->
        <div class="table-container" id="all-users">
            <h2><i class="fas fa-users"></i> All Users</h2>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search users...">
            </div>
            <table>
                <thead>
                    <tr>
                         <th>Username</th>
                         <th>Full Name</th>
                         <th>Email</th>
                         <th>Role</th>
                         <th>Status</th>
                         <th>Registration Date</th>
                         <th>Approval Date</th>
                         <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $all_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge <?php echo $user['role']; ?>-badge"><?php echo ucfirst($user['role']); ?></span></td>
                        <td>
                            <span class="status-badge <?php echo $user['approved'] ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo $user['approved'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                         <td>
                             <?php if ($user['approval_date']): ?>
                                <?php echo date('M d, Y', strtotime($user['approval_date'])); ?>
                             <?php else: ?>
                                <span class="text-muted"></span>
                            <?php endif; ?>
                         </td>
                         <td>
                             <?php if ($user['approved_by_name']): ?>
                                 <?php echo htmlspecialchars($user['approved_by_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($user['approved_by_username']); ?>)</small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- System Management -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-cog"></i>
                    <h3>System Settings</h3>
                </div>
                <div class="card-content">
                    <p>Configure library system settings</p>
                </div>
                <div class="card-actions">
                    <a href="#" class="action-btn">Manage Settings</a>
                </div>
            </div>
            
            <!-- In the System Management section -->
        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i>
            <h3>Reports & Analytics</h3>
        </div>
            <div class="card-content">
                <p>Generate system reports and analytics</p>
            </div>
        <div class="card-actions">
                <a href="reports.php" class="action-btn">View Reports</a>
        </div>
</div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Staff Dashboard - User approval and system management</p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/scripts.js"></script>
    <script>
// Handle user approval/rejection
function handleUserAction(userId, action) {
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    
    // Update this path to point to the correct location
    fetch('../../includes/approve_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`User ${action}ed successfully!`);
            // Remove the row from the table
            const row = document.getElementById(`user-row-${userId}`);
            if (row) {
                row.remove();
                // Update the pending users count
                updatePendingCount();
            }
        } else {
            alert(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Function to update pending users count
function updatePendingCount() {
    const pendingCountElement = document.querySelector('.stat-number');
    if (pendingCountElement) {
        let currentCount = parseInt(pendingCountElement.textContent) || 0;
        if (currentCount > 0) {
            pendingCountElement.textContent = currentCount - 1;
        }
    }
}
</script>
</body>
</html>