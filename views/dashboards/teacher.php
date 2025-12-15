<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';


$user_id = $_SESSION['user_id'];
$conn = getDBConnection();


$borrowed_query = "SELECT b.*, br.id as record_id, br.borrow_date, br.due_date, br.status 
                   FROM borrowing_records br 
                   JOIN books b ON br.book_id = b.id 
                   WHERE br.user_id = ? AND br.status = 'borrowed' 
                   ORDER BY br.due_date ASC";
$stmt = $conn->prepare($borrowed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();


$overdue_query = "SELECT b.*, br.id as record_id, br.borrow_date, br.due_date, 
                         DATEDIFF(CURDATE(), br.due_date) as days_overdue
                  FROM borrowing_records br 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.user_id = ? 
                    AND br.status = 'borrowed' 
                    AND br.due_date < CURDATE()
                  ORDER BY br.due_date ASC";
$stmt2 = $conn->prepare($overdue_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$overdue_books = $stmt2->get_result();


$fines_query = "SELECT 
                    SUM(CASE WHEN f.paid = FALSE THEN f.amount ELSE 0 END) as total_unpaid,
                    SUM(CASE WHEN f.paid = TRUE THEN f.amount ELSE 0 END) as total_paid
                 FROM fines f
                 WHERE f.user_id = ?";
$stmt3 = $conn->prepare($fines_query);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$fines_result = $stmt3->get_result();
$fines_data = $fines_result->fetch_assoc();


$available_books_query = "SELECT * FROM books WHERE copies_available >= 0 ORDER BY title LIMIT 15";
$available_books = $conn->query($available_books_query);


$reservations_query = "SELECT r.*, b.title, b.author, 
                      (SELECT COUNT(*) FROM reservations r2 
                       WHERE r2.book_id = r.book_id 
                       AND r2.status = 'active' 
                       AND r2.reservation_date < r.reservation_date) as queue_position
                      FROM reservations r 
                      JOIN books b ON r.book_id = b.id 
                      WHERE r.user_id = ? AND r.status = 'active'
                      ORDER BY r.reservation_date ASC";
$reservations_stmt = $conn->prepare($reservations_query);
$reservations_stmt->bind_param("i", $user_id);
$reservations_stmt->execute();
$reservations = $reservations_stmt->get_result();


$history_query = "SELECT b.title, b.author, br.borrow_date, br.return_date 
                  FROM borrowing_records br 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.user_id = ? AND br.status = 'returned' 
                  ORDER BY br.borrow_date DESC LIMIT 10";
$stmt4 = $conn->prepare($history_query);
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$borrowing_history = $stmt4->get_result();


$course_materials_query = "SELECT cm.*, b.title, b.author 
                           FROM course_materials cm 
                           JOIN books b ON cm.book_id = b.id 
                           WHERE cm.teacher_id = ? 
                           ORDER BY cm.request_date DESC";
$course_stmt = $conn->prepare($course_materials_query);
$course_stmt->bind_param("i", $user_id);
$course_stmt->execute();
$course_materials = $course_stmt->get_result();

$conn->close();


$borrowing_limit = 30; 
$max_books = 999; 
$max_reservations = 999; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="/smart-library/assets/css/teacher.css">
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
                    <a href="fines_history.php" class="nav-btn">
                        <i class="fas fa-money-bill-wave"></i> Fines History
                    </a>
                    <a href="course_requests.php" class="nav-btn">
                        <i class="fas fa-chalkboard-teacher"></i> Course Requests
                    </a>
                </div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="role-badge teacher-badge">Teacher</span>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
            <p>Access library resources with extended privileges and manage course materials</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book-open"></i>
                    <h3>Borrowed Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $borrowed_books->num_rows; ?></div>
                    <div class="stat-label">Currently Borrowed</div>
                </div>
                <div class="card-actions">
                    <a href="#borrowed-books" class="action-btn">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-clock"></i>
                    <h3>Overdue Books</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $overdue_books->num_rows; ?></div>
                    <div class="stat-label">Need Attention</div>
                </div>
                <div class="card-actions">
                    <a href="#overdue-books" class="action-btn">Check Now</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-money-bill"></i>
                    <h3>Total Fines</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number">₱<?php echo number_format($fines_data['total_unpaid'] ?? 0, 2); ?></div>
                    <div class="stat-label">Unpaid Balance</div>
                </div>
                <div class="card-actions">
                    <a href="#fines-section" class="action-btn">Pay Now</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-bookmark"></i>
                    <h3>Reservations</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $reservations->num_rows; ?></div>
                    <div class="stat-label">Active Reservations</div>
                </div>
                <div class="card-actions">
                    <a href="#reservations" class="action-btn">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chalkboard"></i>
                    <h3>Course Materials</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $course_materials->num_rows; ?></div>
                    <div class="stat-label">Requested Items</div>
                </div>
                <div class="card-actions">
                    <a href="#course-materials" class="action-btn">Manage</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h3>Reading History</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $borrowing_history->num_rows; ?>+</div>
                    <div class="stat-label">Books Read</div>
                </div>
                <div class="card-actions">
                    <a href="#history" class="action-btn">View History</a>
                </div>
            </div>
        </div>
        
        
        <div class="table-container" id="overdue-books">
            <h2><i class="fas fa-exclamation-triangle"></i> Overdue Books</h2>
            <?php if ($overdue_books->num_rows > 0): ?>
            <div class="warning-message">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>You have <?php echo $overdue_books->num_rows; ?> overdue book(s)</strong>
                    <p>Return overdue books immediately to avoid additional fines.</p>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $overdue_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td>
                            <span class="text-danger">
                                <?php echo date('M d, Y', strtotime($book['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge danger">
                                <?php echo $book['days_overdue']; ?> days
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="returnBook('<?php echo $book['record_id']; ?>', '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-undo"></i> Return
                                </button>
                                <button class="btn-small btn-success" onclick="payFine('<?php echo $book['record_id']; ?>', '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-credit-card"></i> Pay Fine
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
                    <strong>Great news! You have no overdue books.</strong>
                    <p>Keep up the good work and return your books on time.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
       
        <div class="table-container" id="borrowed-books">
            <h2><i class="fas fa-bookmark"></i> Your Borrowed Books</h2>
            <?php if ($borrowed_books->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $borrowed_books->fetch_assoc()): 
                        $due_date = new DateTime($book['due_date']);
                        $today = new DateTime();
                        $is_overdue = $today > $due_date;
                        $interval = $today->diff($due_date);
                        $days_remaining = $is_overdue ? 0 : $interval->days;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                        <td>
                            <span class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                <?php echo date('M d, Y', strtotime($book['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $is_overdue ? 'danger' : ($days_remaining <= 7 ? 'warning' : 'success'); ?>">
                                <?php echo $is_overdue ? 'Overdue' : $days_remaining . ' days'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="returnBook('<?php echo $book['record_id']; ?>', '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-undo"></i> Return
                                </button>
                                <?php if (!$is_overdue): ?>
                                <button class="btn-small btn-approve" onclick="renewBook('<?php echo $book['record_id']; ?>', '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-redo"></i> Renew
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>You have no borrowed books at the moment.</strong>
                    <p>Browse our collection to find books you'd like to borrow.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
       
        <div class="table-container" id="available-books">
            <h2><i class="fas fa-book"></i> Available Books</h2>
            <?php if ($available_books->num_rows > 0): ?>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search books..." onkeyup="searchBooks(this.value)">
                <button class="btn-small" onclick="clearSearch()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
            <table id="booksTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Available Copies</th>
                        <th>Borrow Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $available_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td>
                            <span class="badge <?php echo $book['copies_available'] <= 2 ? 'warning' : 'success'; ?>">
                                <?php echo $book['copies_available']; ?> available
                            </span>
                        </td>
                        <td><?php echo $borrowing_limit; ?> days</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-approve" onclick="borrowBook(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-book"></i> Borrow
                                </button>
                                <button class="btn-small btn-reserve" onclick="reserveBook(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-bookmark"></i> Reserve
                                </button>
                                <button class="btn-small btn-info" onclick="requestForCourse(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
                                    <i class="fas fa-chalkboard"></i> Course Use
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="warning-message">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>No books available at the moment.</strong>
                    <p>Please check back later or contact library staff for assistance.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        
        <div class="table-container" id="reservations">
            <h2><i class="fas fa-bookmark"></i> My Reservations</h2>
            <?php if ($reservations->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Reservation Date</th>
                        <th>Expiry Date</th>
                        <th>Queue Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($reservation = $reservations->fetch_assoc()): 
                        $expiry_date = new DateTime($reservation['expiry_date']);
                        $today = new DateTime();
                        $days_left = $today->diff($expiry_date)->days;
                        $is_expiring_soon = $days_left <= 2;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reservation['title']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                        <td>
                            <span class="<?php echo $is_expiring_soon ? 'text-danger' : ''; ?>">
                                <?php echo date('M d, Y', strtotime($reservation['expiry_date'])); ?>
                                <?php if ($is_expiring_soon): ?>
                                <br><small class="text-danger">(<?php echo $days_left; ?> days left)</small>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($reservation['queue_position'] == 0): ?>
                                <span class="badge success">Next in line</span>
                            <?php else: ?>
                                <span class="badge info">#<?php echo $reservation['queue_position'] + 1; ?> in queue</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-pending">
                                Active
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>, '<?php echo addslashes($reservation['title']); ?>')">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <?php if ($reservation['queue_position'] == 0): ?>
                                <button class="btn-small btn-success" onclick="checkAvailability(<?php echo $reservation['book_id']; ?>)">
                                    <i class="fas fa-bell"></i> Check Availability
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>You have no active reservations.</strong>
                    <p>Reserve books that are currently unavailable to get notified when they become available.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        
        <div class="table-container" id="course-materials">
            <h2><i class="fas fa-chalkboard-teacher"></i> Course Materials</h2>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Request Books for Your Courses</strong>
                    <p>Teachers can request books to be reserved for specific courses. These books get priority in reservations.</p>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="showCourseRequestForm()">
                <i class="fas fa-plus-circle"></i> Request New Course Material
            </button>
            
            <?php if ($course_materials->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Course</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($material = $course_materials->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($material['title']); ?></td>
                        <td><?php echo htmlspecialchars($material['author']); ?></td>
                        <td><?php echo htmlspecialchars($material['course_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($material['request_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $material['status'] == 'approved' ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo ucfirst($material['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="viewCourseMaterial(<?php echo $material['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if ($material['status'] == 'pending'): ?>
                                <button class="btn-small btn-danger" onclick="cancelCourseRequest(<?php echo $material['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
       
        <div class="table-container" id="history">
            <h2><i class="fas fa-history"></i> Borrowing History</h2>
            <?php if ($borrowing_history->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $borrowing_history->fetch_assoc()): 
                        $borrow_date = new DateTime($record['borrow_date']);
                        $return_date = $record['return_date'] ? new DateTime($record['return_date']) : null;
                        $duration = $return_date ? $borrow_date->diff($return_date)->days : 'N/A';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo htmlspecialchars($record['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                        <td>
                            <?php if ($record['return_date']): ?>
                                <?php echo date('M d, Y', strtotime($record['return_date'])); ?>
                            <?php else: ?>
                                <span class="text-warning">Not returned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($duration !== 'N/A'): ?>
                                <span class="badge info"><?php echo $duration; ?> days</span>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>You have no borrowing history.</strong>
                    <p>Start your reading journey by borrowing books from our collection.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        
        <div class="table-container" id="fines-section">
            <h2><i class="fas fa-money-bill-wave"></i> Fines Summary</h2>
            <div class="fines-summary">
                <div class="fine-item">
                    <div class="fine-label">Total Unpaid Fines:</div>
                    <div class="fine-amount text-danger">
                        ₱<?php echo number_format($fines_data['total_unpaid'] ?? 0, 2); ?>
                    </div>
                </div>
                <div class="fine-item">
                    <div class="fine-label">Total Paid Fines:</div>
                    <div class="fine-amount text-success">
                        ₱<?php echo number_format($fines_data['total_paid'] ?? 0, 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="fines-actions">
                <button class="btn btn-primary" onclick="viewAllFines()">
                    <i class="fas fa-list"></i> View All Fines
                </button>
                <button class="btn btn-success" onclick="payAllFines()" <?php echo ($fines_data['total_unpaid'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-credit-card"></i> Pay All Fines
                </button>
                <button class="btn btn-info" onclick="requestFineWaiver()">
                    <i class="fas fa-handshake"></i> Request Waiver
                </button>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Teacher Dashboard - Borrowing Limit: <?php echo $max_books; ?> books | Borrowing Period: <?php echo $borrowing_limit; ?> days | Max Reservations: <?php echo $max_reservations; ?></p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/scripts.js"></script>
    <script>
    
    function borrowBook(bookId, bookTitle) {
        if (!confirm('Borrow "' + bookTitle + '" for <?php echo $borrowing_limit; ?> days?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('book_id', bookId);
        formData.append('user_role', 'teacher');
        
        fetch('../../includes/borrow_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book borrowed successfully! Due date: ' + data.due_date);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
   
    function returnBook(recordId, bookTitle) {
        if (!confirm('Return "' + bookTitle + '"?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        
        fetch('../../includes/return_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.fine_applied && data.fine_amount > 0) {
                    alert('Book returned successfully!\n\nFine applied: ₱' + data.fine_amount.toFixed(2) + '\nReason: ' + data.reason);
                } else {
                    alert('Book returned successfully!');
                }
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    
    function renewBook(recordId, bookTitle) {
        if (!confirm('Renew "' + bookTitle + '" for another <?php echo $borrowing_limit; ?> days?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        formData.append('user_role', 'teacher');
        
        fetch('../../includes/renew_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book renewed successfully! New due date: ' + data.new_due_date);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    
    function reserveBook(bookId, bookTitle) {
        if (!confirm('Reserve "' + bookTitle + '"?\n\nYou will be notified when it becomes available.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('book_id', bookId);
        formData.append('user_role', 'teacher');
        
        fetch('../../includes/reserve_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book reserved successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
  
    function cancelReservation(reservationId, bookTitle) {
        if (!confirm('Cancel reservation for "' + bookTitle + '"?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('reservation_id', reservationId);
        
        fetch('../../includes/cancel_reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reservation cancelled successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
  
    function checkAvailability(bookId) {
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        fetch('../../includes/check_availability.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                if (confirm('Book is now available!\n\nWould you like to borrow it?')) {
                    
                    borrowBook(bookId, 'Available Book');
                }
            } else {
                alert('Book is still unavailable. We will notify you when it becomes available.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
   
    function payFine(recordId, bookTitle) {
        const amount = prompt('Enter fine amount for "' + bookTitle + '":', '50.00');
        if (!amount || isNaN(amount) || amount <= 0) {
            alert('Please enter a valid amount.');
            return;
        }
        
        const paymentMethod = prompt('Enter payment method (Cash/Card/Online):', 'Cash');
        if (!paymentMethod) return;
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        formData.append('amount', amount);
        formData.append('method', paymentMethod);
        
        fetch('../../includes/pay_fine.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Fine paid successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
   
    function payAllFines() {
        const totalAmount = <?php echo $fines_data['total_unpaid'] ?? 0; ?>;
        if (totalAmount <= 0) {
            alert('You have no unpaid fines.');
            return;
        }
        
        if (!confirm('Pay all fines totaling ₱' + totalAmount.toFixed(2) + '?')) {
            return;
        }
        
        const paymentMethod = prompt('Enter payment method (Cash/Card/Online):', 'Cash');
        if (!paymentMethod) return;
        
        const formData = new FormData();
        formData.append('amount', totalAmount);
        formData.append('method', paymentMethod);
        
        fetch('../../includes/pay_all_fines.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All fines paid successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    
    function viewAllFines() {
        window.open('fines_history.php', 'Fines History', 'width=1200,height=800');
    }
    
    
    function requestFineWaiver() {
        const reason = prompt('Please explain why you are requesting a fine waiver:', 'Course material / Research purposes');
        if (reason) {
            alert('Fine waiver request submitted. Library staff will review your request.');
        }
    }
    
    
    
function requestForCourse(bookId, bookTitle) {
    console.log('DEBUG - Function called with:', {bookId, bookTitle});
    
    const courseName = prompt('Enter course name for "' + bookTitle + '":');
    console.log('DEBUG - Course name entered:', courseName);
    
    if (!courseName) {
        console.log('DEBUG - User cancelled at course name');
        return;
    }
    
    const semester = prompt('Enter semester:');
    console.log('DEBUG - Semester entered:', semester);
    
    if (!semester) {
        console.log('DEBUG - User cancelled at semester');
        return;
    }
    
    console.log('DEBUG - Preparing to send request...');
    
    const formData = new FormData();
    formData.append('book_id', bookId);
    formData.append('course_name', courseName);
    formData.append('semester', semester);
    
    console.log('DEBUG - FormData prepared:', {
        book_id: bookId,
        course_name: courseName,
        semester: semester
    });
    
    
    const url = '../../includes/request_course_material.php';
    console.log('DEBUG - Fetch URL:', url);
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('DEBUG - Response status:', response.status);
        console.log('DEBUG - Response headers:', response.headers);
        return response.text(); // Use text() first to see raw response
    })
    .then(text => {
        console.log('DEBUG - Raw response:', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('DEBUG - JSON parse error:', e);
            return {success: false, message: 'Invalid JSON response: ' + text};
        }
    })
    .then(data => {
        console.log('DEBUG - Parsed data:', data);
        if (data.success) {
            alert('Course material request submitted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('DEBUG - Fetch error:', error);
        alert('An error occurred. Please check console for details.');
    });
}
    
  
    function showCourseRequestForm() {
        const courseName = prompt('Enter course name:');
        if (!courseName) return;
        
        const bookTitle = prompt('Enter book title (or ISBN):');
        if (!bookTitle) return;
        
        const reason = prompt('Why do you need this book for your course?');
        if (!reason) return;
        
        const formData = new FormData();
        formData.append('course_name', courseName);
        formData.append('book_info', bookTitle);
        formData.append('reason', reason);
        
        fetch('../../includes/request_course_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Course book request submitted successfully! Library staff will review your request.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
   
    function viewCourseMaterial(materialId) {
        window.open('course_material_details.php?id=' + materialId, 'Course Material Details', 'width=600,height=400');
    }
    
  
    function cancelCourseRequest(requestId) {
        if (!confirm('Cancel this course material request?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('request_id', requestId);
        
        fetch('../../includes/cancel_course_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Course request cancelled successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    
    function searchBooks(query) {
        const table = document.getElementById('booksTable');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const lowerQuery = query.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lowerQuery) ? '' : 'none';
        });
    }
    
    
    function clearSearch() {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            searchBooks('');
        }
    }
    
    
    document.addEventListener('DOMContentLoaded', function() {
        
    });
    </script>
    
</body>
</html>