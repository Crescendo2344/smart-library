<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';


if ($_SESSION['role'] !== 'librarian') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $book_id = intval($_GET['delete']);
    $delete_query = "UPDATE books SET status = 'deleted', deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Book archived successfully!";
    }
}


if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $book_id = intval($_GET['restore']);
    $restore_query = "UPDATE books SET status = 'active', deleted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($restore_query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Book restored successfully!";
    }
}


if (isset($_GET['permanent_delete']) && is_numeric($_GET['permanent_delete'])) {
    $book_id = intval($_GET['permanent_delete']);
    

    $check_query = "SELECT COUNT(*) as active_count FROM borrowing_records WHERE book_id = ? AND status = 'borrowed'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data['active_count'] > 0) {
        $error_message = "Cannot delete book with active borrowings!";
    } else {
        
        $delete_records = "DELETE FROM borrowing_records WHERE book_id = ?";
        $stmt2 = $conn->prepare($delete_records);
        $stmt2->bind_param("i", $book_id);
        $stmt2->execute();
        
        
        $delete_query = "DELETE FROM books WHERE id = ?";
        $stmt3 = $conn->prepare($delete_query);
        $stmt3->bind_param("i", $book_id);
        $stmt3->execute();
        
        if ($stmt3->affected_rows > 0) {
            $success_message = "Book permanently deleted!";
        }
    }
}


$filter = $_GET['filter'] ?? 'active';
$search = $_GET['search'] ?? '';


$where_conditions = [];
$params = [];
$types = "";

if ($filter === 'active') {
    $where_conditions[] = "b.status = 'active'";
} elseif ($filter === 'archived') {
    $where_conditions[] = "b.status = 'deleted'";
}

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.category LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";


$books_query = "SELECT b.*, 
                (SELECT COUNT(*) FROM borrowing_records br WHERE br.book_id = b.id AND br.status = 'borrowed') as currently_borrowed,
                (SELECT COUNT(*) FROM borrowing_records br WHERE br.book_id = b.id) as total_borrowed
                FROM books b 
                $where_clause 
                ORDER BY b.title";
                
$stmt = $conn->prepare($books_query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result();


$borrowings_query = "SELECT br.*, u.username, u.full_name, u.email, b.title 
                     FROM borrowing_records br 
                     JOIN users u ON br.user_id = u.id 
                     JOIN books b ON br.book_id = b.id 
                     WHERE br.status = 'borrowed' 
                     ORDER BY br.due_date ASC";
$borrowings = $conn->query($borrowings_query);


$overdue_query = "SELECT br.*, u.username, u.full_name, u.email, b.title, 
                  DATEDIFF(CURDATE(), br.due_date) as days_overdue
                  FROM borrowing_records br 
                  JOIN users u ON br.user_id = u.id 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.status = 'borrowed' AND br.due_date < CURDATE() 
                  ORDER BY br.due_date ASC";
$overdue_books = $conn->query($overdue_query);


$stats_query = "SELECT 
    (SELECT COUNT(*) FROM books WHERE status = 'active') as total_books,
    (SELECT SUM(copies_available) FROM books WHERE status = 'active') as available_books,
    (SELECT COUNT(*) FROM borrowing_records WHERE status = 'borrowed') as borrowed_books,
    (SELECT COUNT(*) FROM borrowing_records WHERE due_date < CURDATE() AND status = 'borrowed') as overdue_books,
    (SELECT COUNT(*) FROM books WHERE status = 'deleted') as archived_books,
    (SELECT COUNT(DISTINCT user_id) FROM borrowing_records WHERE status = 'borrowed') as active_borrowers";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent activities
$activities_query = "SELECT 
    'borrow' as type, u.username, b.title, br.borrow_date as date
    FROM borrowing_records br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    WHERE br.status = 'borrowed'
    UNION ALL
    SELECT 
    'return' as type, u.username, b.title, br.return_date as date
    FROM borrowing_records br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    WHERE br.status = 'returned'
    ORDER BY date DESC
    LIMIT 10";
$activities = $conn->query($activities_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="/smart-library/assets/css/librarian.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-book"></i> Library System
            </div>
            <div class="user-info">
                <div class="nav-links">
                    <a href="book_archive.php" class="nav-btn">
                        <i class="fas fa-archive"></i> Archive
                    </a>
                    <a href="reports.php" class="nav-btn">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="users.php" class="nav-btn">
                        <i class="fas fa-users"></i> Users
                    </a>
                </div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="role-badge librarian-badge">Librarian</span>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>Success!</strong>
                <p><?php echo $success_message; ?></p>
            </div>
            <button class="close-alert" onclick="document.getElementById('successMessage').remove()">&times;</button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Error!</strong>
                <p><?php echo $error_message; ?></p>
            </div>
            <button class="close-alert" onclick="document.getElementById('errorMessage').remove()">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-header">
            <h1><i class="fas fa-user-shield"></i> Librarian Dashboard</h1>
            <p>Manage books, track borrowings, and handle library operations</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book"></i>
                    <h3>Total Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                    <div class="stat-label">Active in Collection</div>
                </div>
                <div class="card-actions">
                    <a href="#books" class="action-btn">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book-open"></i>
                    <h3>Borrowed Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['borrowed_books']; ?></div>
                    <div class="stat-label">Currently Loaned</div>
                </div>
                <div class="card-actions">
                    <a href="#borrowings" class="action-btn">Manage</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Overdue Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['overdue_books']; ?></div>
                    <div class="stat-label">Need Attention</div>
                </div>
                <div class="card-actions">
                    <a href="#overdue" class="action-btn">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users"></i>
                    <h3>Active Borrowers</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['active_borrowers']; ?></div>
                    <div class="stat-label">Currently Active</div>
                </div>
                <div class="card-actions">
                    <a href="users.php" class="action-btn">View Users</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-archive"></i>
                    <h3>Archived Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $stats['archived_books']; ?></div>
                    <div class="stat-label">In Archive</div>
                </div>
                <div class="card-actions">
                    <a href="?filter=archived" class="action-btn">View Archive</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New Book</h3>
                </div>
                <div class="card-content">
                    <p>Add new books to the library collection</p>
                </div>
                <div class="card-actions">
                    <button onclick="showAddBookModal()" class="action-btn">Add Book</button>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <button class="action-btn quick-btn" onclick="showAddBookModal()">
                    <i class="fas fa-plus"></i> Add Book
                </button>
                <button class="action-btn quick-btn" onclick="showBulkUploadModal()">
                    <i class="fas fa-upload"></i> Bulk Upload
                </button>
                <button class="action-btn quick-btn" onclick="showQRGenerator()">
                    <i class="fas fa-qrcode"></i> Generate QR Codes
                </button>
                <button class="action-btn quick-btn" onclick="exportBooks()">
                    <i class="fas fa-file-export"></i> Export Books
                </button>
                <button class="action-btn quick-btn" onclick="printInventory()">
                    <i class="fas fa-print"></i> Print Inventory
                </button>
                <button class="action-btn quick-btn" onclick="showReports()">
                    <i class="fas fa-chart-line"></i> View Reports
                </button>
            </div>
        </div>
        
        <!-- Book Management -->
        <div class="table-container" id="books">
            <div class="table-header">
                <h2><i class="fas fa-book"></i> Book Management</h2>
                <div class="table-controls">
                    <form method="GET" class="search-form">
                        <div class="search-box">
                            <input type="text" name="search" class="search-input" placeholder="Search books..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    <div class="filter-tabs">
                        <a href="?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">
                            Active Books (<?php echo $stats['total_books']; ?>)
                        </a>
                        <a href="?filter=archived" class="filter-tab <?php echo $filter === 'archived' ? 'active' : ''; ?>">
                            Archived (<?php echo $stats['archived_books']; ?>)
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($books->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Available</th>
                            <th>Total</th>
                            <th>Borrowed</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $books->fetch_assoc()): 
                            $is_archived = $book['status'] === 'deleted';
                        ?>
                        <tr class="<?php echo $is_archived ? 'archived' : ''; ?>">
                            <td><?php echo $book['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                <?php if ($is_archived): ?>
                                <span class="badge danger">Archived</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><code><?php echo htmlspecialchars($book['isbn']); ?></code></td>
                            <td>
                                <span class="category-tag"><?php echo htmlspecialchars($book['category']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $book['copies_available'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $book['copies_available']; ?>
                                </span>
                            </td>
                            <td><?php echo $book['total_copies']; ?></td>
                            <td><?php echo $book['currently_borrowed']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $book['status'] === 'active' ? 'status-approved' : 'status-rejected'; ?>">
                                    <?php echo ucfirst($book['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-small btn-view" onclick="editBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-small btn-info" onclick="viewBookDetails(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    
                                    <?php if (!$is_archived): ?>
                                        <?php if ($book['currently_borrowed'] == 0): ?>
                                        <button class="btn-small btn-danger" onclick="archiveBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                        <?php else: ?>
                                        <button class="btn-small btn-warning" onclick="showCannotArchive('<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                            <i class="fas fa-ban"></i> In Use
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn-small btn-success" onclick="restoreBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                        <button class="btn-small btn-danger" onclick="permanentDelete(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-footer">
                <div class="table-info">
                    Showing <?php echo $books->num_rows; ?> books
                </div>
                <div class="table-actions">
                    <button class="btn btn-secondary" onclick="exportTable()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-primary" onclick="showAddBookModal()">
                        <i class="fas fa-plus"></i> Add New Book
                    </button>
                </div>
            </div>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No books found</h3>
                <p><?php echo $filter === 'archived' ? 'No archived books found.' : 'No active books found.'; ?></p>
                <button class="btn btn-primary" onclick="showAddBookModal()">
                    <i class="fas fa-plus"></i> Add Your First Book
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Current Borrowings -->
        <div class="table-container" id="borrowings">
            <h2><i class="fas fa-exchange-alt"></i> Current Borrowings</h2>
            <?php if ($borrowings->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $borrowings->fetch_assoc()): 
                        $due_date = new DateTime($record['due_date']);
                        $today = new DateTime();
                        $is_overdue = $today > $due_date;
                        $interval = $today->diff($due_date);
                        $days_left = $is_overdue ? 0 : $interval->days;
                    ?>
                    <tr class="<?php echo $is_overdue ? 'overdue-row' : ''; ?>">
                        <td>BR-<?php echo $record['id']; ?></td>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>
                            <br><small><?php echo htmlspecialchars($record['username']); ?></small>
                            <br><small><?php echo htmlspecialchars($record['email']); ?></small>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                        <td>
                            <span class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                <?php echo date('M d, Y', strtotime($record['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $is_overdue ? 'danger' : ($days_left <= 3 ? 'warning' : 'success'); ?>">
                                <?php echo $is_overdue ? 'Overdue' : $days_left . ' days'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $is_overdue ? 'status-overdue' : 'status-borrowed'; ?>">
                                <?php echo $is_overdue ? 'Overdue' : 'Borrowed'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-success" onclick="markReturned(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['title'])); ?>')">
                                    <i class="fas fa-check"></i> Return
                                </button>
                                <button class="btn-small btn-info" onclick="extendDueDate(<?php echo $record['id']; ?>)">
                                    <i class="fas fa-calendar-plus"></i> Extend
                                </button>
                                <button class="btn-small btn-view" onclick="viewBorrowerDetails(<?php echo $record['user_id']; ?>)">
                                    <i class="fas fa-user"></i> Profile
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-reader"></i>
                <h3>No current borrowings</h3>
                <p>All books are currently available in the library.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Overdue Books -->
        <div class="table-container" id="overdue">
            <h2><i class="fas fa-exclamation-triangle"></i> Overdue Books</h2>
            <?php if ($overdue_books->num_rows > 0): ?>
            <div class="warning-message">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Action Required!</strong>
                    <p>You have <?php echo $overdue_books->num_rows; ?> overdue book(s) that need attention.</p>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Fine</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $overdue_books->fetch_assoc()): 
                        $due_date = new DateTime($record['due_date']);
                        $today = new DateTime();
                        $days_overdue = $record['days_overdue'];
                        $fine_amount = calculateFine($days_overdue);
                    ?>
                    <tr class="overdue-row">
                        <td><strong><?php echo htmlspecialchars($record['title']); ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>
                            <br><small><?php echo htmlspecialchars($record['username']); ?></small>
                            <br><small><?php echo htmlspecialchars($record['email']); ?></small>
                        </td>
                        <td>
                            <span class="text-danger">
                                <?php echo date('M d, Y', strtotime($record['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge danger">
                                <?php echo $days_overdue; ?> days
                            </span>
                        </td>
                        <td>
                            <strong class="text-danger">
                                ₱<?php echo number_format($fine_amount, 2); ?>
                            </strong>
                        </td>
                        <td>
                            <button class="btn-small btn-view" onclick="contactBorrower('<?php echo htmlspecialchars($record['email']); ?>', '<?php echo htmlspecialchars($record['full_name']); ?>')">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                            <button class="btn-small btn-info" onclick="callBorrower('<?php echo htmlspecialchars($record['full_name']); ?>')">
                                <i class="fas fa-phone"></i> Call
                            </button>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-success" onclick="markReturned(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['title'])); ?>', true)">
                                    <i class="fas fa-check"></i> Return
                                </button>
                                <button class="btn-small btn-warning" onclick="sendReminder(<?php echo $record['user_id']; ?>, <?php echo $record['id']; ?>)">
                                    <i class="fas fa-bell"></i> Remind
                                </button>
                                <button class="btn-small btn-danger" onclick="applyFine(<?php echo $record['id']; ?>, <?php echo $fine_amount; ?>)">
                                    <i class="fas fa-money-bill"></i> Fine
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Great news!</strong>
                    <p>No overdue books at the moment.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Activities -->
        <div class="table-container" id="activities">
            <h2><i class="fas fa-history"></i> Recent Library Activities</h2>
            <?php if ($activities->num_rows > 0): ?>
            <div class="timeline">
                <?php while($activity = $activities->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-icon <?php echo $activity['type'] === 'borrow' ? 'borrow-icon' : 'return-icon'; ?>">
                        <i class="fas fa-<?php echo $activity['type'] === 'borrow' ? 'arrow-right' : 'arrow-left'; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                            <span class="timeline-time"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></span>
                        </div>
                        <p>
                            <?php echo $activity['type'] === 'borrow' ? 'borrowed' : 'returned'; ?> 
                            <strong>"<?php echo htmlspecialchars($activity['title']); ?>"</strong>
                        </p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>No recent activities</h3>
                <p>Library activities will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Librarian Dashboard - Total Books: <?php echo $stats['total_books']; ?> | Active Borrowers: <?php echo $stats['active_borrowers']; ?></p>
        </div>
    </footer>
    
    <!-- Add Book Modal -->
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Book</h2>
                <button class="close-btn" onclick="closeModal('addBookModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addBookForm" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Book Title *</label>
                            <input type="text" id="title" name="title" required placeholder="Enter book title">
                        </div>
                        <div class="form-group">
                            <label for="author"><i class="fas fa-user-pen"></i> Author *</label>
                            <input type="text" id="author" name="author" required placeholder="Enter author name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="isbn"><i class="fas fa-barcode"></i> ISBN *</label>
                            <input type="text" id="isbn" name="isbn" required placeholder="Enter ISBN">
                        </div>
                        <div class="form-group">
                            <label for="category"><i class="fas fa-tags"></i> Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Fiction">Fiction</option>
                                <option value="Non-Fiction">Non-Fiction</option>
                                <option value="Science">Science</option>
                                <option value="Technology">Technology</option>
                                <option value="History">History</option>
                                <option value="Biography">Biography</option>
                                <option value="Literature">Literature</option>
                                <option value="Academic">Academic</option>
                                <option value="Children">Children</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="publisher"><i class="fas fa-building"></i> Publisher</label>
                            <input type="text" id="publisher" name="publisher" placeholder="Enter publisher">
                        </div>
                        <div class="form-group">
                            <label for="published_year"><i class="fas fa-calendar"></i> Publication Year</label>
                            <input type="number" id="published_year" name="published_year" 
                                   min="1000" max="<?php echo date('Y'); ?>" 
                                   placeholder="YYYY" value="<?php echo date('Y'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="copies"><i class="fas fa-copy"></i> Total Copies *</label>
                            <input type="number" id="copies" name="copies" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label for="location"><i class="fas fa-map-marker-alt"></i> Shelf Location</label>
                            <input type="text" id="location" name="location" placeholder="e.g., A-12">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Enter book description..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="cover_image"><i class="fas fa-image"></i> Cover Image</label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addBookModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Book</h2>
                <button class="close-btn" onclick="closeModal('editBookModal')">&times;</button>
            </div>
            <div class="modal-body" id="editBookFormContainer">
                <!-- Form will be loaded here via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Bulk Upload Modal -->
    <div id="bulkUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-upload"></i> Bulk Book Upload</h2>
                <button class="close-btn" onclick="closeModal('bulkUploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="upload-instructions">
                    <h3><i class="fas fa-info-circle"></i> Instructions</h3>
                    <p>Upload a CSV file with the following columns:</p>
                    <ul>
                        <li>title (required)</li>
                        <li>author (required)</li>
                        <li>isbn (required)</li>
                        <li>category (required)</li>
                        <li>publisher (optional)</li>
                        <li>published_year (optional)</li>
                        <li>total_copies (required, default: 1)</li>
                        <li>description (optional)</li>
                    </ul>
                    <p><a href="/smart-library/assets/templates/books_template.csv" class="btn-small">
                        <i class="fas fa-download"></i> Download Template
                    </a></p>
                </div>
                
                <form id="bulkUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="csv_file"><i class="fas fa-file-csv"></i> CSV File *</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="skip_duplicates" checked>
                            Skip duplicate ISBNs
                        </label>
                    </div>
                    
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text" id="progressText">0%</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('bulkUploadModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="/smart-library/assets/js/scripts.js"></script>
    <script src="/smart-library/assets/js/librarian.js"></script>
    
</body>
</html>