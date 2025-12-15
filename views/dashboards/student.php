<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Get user info
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get student's borrowed books
$borrowed_query = "SELECT b.*, br.id as record_id, br.borrow_date, br.due_date, br.status 
                   FROM borrowing_records br 
                   JOIN books b ON br.book_id = b.id 
                   WHERE br.user_id = ? AND br.status = 'borrowed' 
                   ORDER BY br.due_date ASC";
$stmt = $conn->prepare($borrowed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();

// Get overdue books with fine calculation
$overdue_query = "SELECT 
                    b.*, 
                    br.id as record_id, 
                    br.borrow_date, 
                    br.due_date, 
                    br.status,
                    DATEDIFF(CURDATE(), br.due_date) as days_overdue,
                    f.amount as fine_amount,
                    f.paid as fine_paid
                  FROM borrowing_records br 
                  JOIN books b ON br.book_id = b.id 
                  LEFT JOIN fines f ON br.id = f.borrowing_id
                  WHERE br.user_id = ? 
                    AND br.status = 'borrowed' 
                    AND br.due_date < CURDATE()
                  ORDER BY br.due_date ASC";
$stmt2 = $conn->prepare($overdue_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$overdue_books = $stmt2->get_result();

// Get total fines
$fines_query = "SELECT 
                    SUM(CASE WHEN f.paid = FALSE THEN f.amount ELSE 0 END) as total_unpaid,
                    SUM(CASE WHEN f.paid = TRUE THEN f.amount ELSE 0 END) as total_paid,
                    COUNT(CASE WHEN f.paid = FALSE THEN 1 END) as unpaid_count
                 FROM fines f
                 JOIN borrowing_records br ON f.borrowing_id = br.id
                 WHERE br.user_id = ?";
$stmt3 = $conn->prepare($fines_query);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$fines_result = $stmt3->get_result();
$fines_data = $fines_result->fetch_assoc();

// Get available books
$available_books_query = "SELECT * FROM books WHERE copies_available > 0 ORDER BY title LIMIT 10";
$available_books = $conn->query($available_books_query);

// Get borrowing history
$history_query = "SELECT b.title, b.author, br.borrow_date, br.return_date 
                  FROM borrowing_records br 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.user_id = ? AND br.status = 'returned' 
                  ORDER BY br.borrow_date DESC LIMIT 5";
$stmt4 = $conn->prepare($history_query);
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$borrowing_history = $stmt4->get_result();

$reservations_query = "SELECT r.*, b.title, b.author, b.isbn, 
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

$conn->close();

// Fine calculation function
function calculateFine($days_overdue) {
    // $0.50 per day after 2-day grace period
    $grace_period = 2;
    $daily_rate = 0.50;
    $max_fine_per_book = 25.00;
    
    if ($days_overdue <= $grace_period) {
        return 0;
    }
    
    $fine_days = $days_overdue - $grace_period;
    $fine = $fine_days * $daily_rate;
    
    return min($fine, $max_fine_per_book);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library Management System</title>
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
                <span class="role-badge student-badge">Student</span>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
            <p>Manage your library account, borrow books, and view your history</p>
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
                    <div class="stat-label">Need to Return</div>
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
        <h3>Active Reservations</h3>
    </div>
    <div class="card-content">
        <div class="stat-number"><?php echo $reservations->num_rows; ?></div>
        <div class="stat-label">Books Reserved</div>
    </div>
    <div class="card-actions">
        <a href="#reservations" class="action-btn">View All</a>
    </div>
</div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h3>Books Read</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $borrowing_history->num_rows; ?>+</div>
                    <div class="stat-label">Reading History</div>
                </div>
                <div class="card-actions">
                    <a href="#history" class="action-btn">View History</a>
                </div>
            </div>
        </div>
        
        <!-- Overdue Books with Fines -->
        <div class="table-container" id="overdue-books">
            <h2><i class="fas fa-exclamation-triangle"></i> Overdue Books & Fines</h2>
            
            <?php if ($overdue_books->num_rows > 0): ?>
            <div class="warning-message">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>You have <?php echo $overdue_books->num_rows; ?> overdue book(s)</strong>
                    <p>Return overdue books immediately to avoid additional fines. Current unpaid balance: <strong>$<?php echo number_format($fines_data['total_unpaid'] ?? 0, 2); ?></strong></p>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Fine Amount</th>
                        <th>Fine Status</th>
                        <th>Total Due</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_fine_due = 0;
                    while($book = $overdue_books->fetch_assoc()): 
                        $due_date = new DateTime($book['due_date']);
                        $today = new DateTime();
                        $days_overdue = $today->diff($due_date)->days;
                        if ($today > $due_date) {
                            $days_overdue = $days_overdue * ($today > $due_date ? 1 : -1);
                        }
                        
                        // Calculate fine if not already in database
                        if ($book['fine_amount'] === null) {
                            $fine_amount = calculateFine($days_overdue);
                        } else {
                            $fine_amount = $book['fine_amount'];
                        }
                        
                        $fine_status = $book['fine_paid'] ? 'Paid' : 'Unpaid';
                        $total_fine_due += ($book['fine_paid'] ? 0 : $fine_amount);
                    ?>
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
                                <?php echo $days_overdue; ?> days
                            </span>
                        </td>
                        <td>
                            <strong class="text-danger">
                                ₱<?php echo number_format($fine_amount, 2); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $book['fine_paid'] ? 'status-approved' : 'status-overdue'; ?>">
                                <?php echo $fine_status; ?>
                            </span>
                        </td>
                        <td>
                            <strong class="text-danger">
                                ₱<?php echo number_format($book['fine_paid'] ? 0 : $fine_amount, 2); ?>
                            </strong>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="returnBook(<?php echo $book['record_id']; ?>)">
                                    <i class="fas fa-undo"></i> Return
                                </button>
                                <?php if (!$book['fine_paid'] && $fine_amount > 0): ?>
                                <button class="btn-small btn-success" onclick="payFine(<?php echo $book['record_id']; ?>, <?php echo $fine_amount; ?>)">
                                    <i class="fas fa-credit-card"></i> Pay
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right"><strong>Total Amount Due:</strong></td>
                        <td colspan="2">
                            <strong class="text-danger total-due">
                                ₱<?php echo number_format($total_fine_due, 2); ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Payment Options -->
            <div class="payment-options">
                <h3><i class="fas fa-credit-card"></i> Payment Options</h3>
                <div class="options-grid">
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="option-content">
                            <h4>Pay at Library Desk</h4>
                            <p>Pay your fines in person at the library circulation desk during operating hours.</p>
                            <button class="btn-small" onclick="generateReceipt()">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="option-content">
                            <h4>Online Payment</h4>
                            <p>Pay online using credit/debit card or digital wallet (Coming Soon).</p>
                            <button class="btn-small btn-success" disabled>
                                <i class="fas fa-lock"></i> Secure Payment
                            </button>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="option-content">
                            <h4>Fine Forgiveness Program</h4>
                            <p>Return overdue books within 7 days for 50% fine reduction.</p>
                            <button class="btn-small btn-info" onclick="applyForgiveness()">
                                <i class="fas fa-gift"></i> Apply Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Great news! You have no overdue books.</strong>
                    <p>Keep up the good work and return your books on time to avoid fines.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Currently Borrowed Books -->
        <div class="table-container" id="borrowed-books">
            <h2><i class="fas fa-bookmark"></i> Currently Borrowed Books</h2>
            <?php if ($borrowed_books->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Reset pointer for borrowed books
                    $borrowed_books->data_seek(0);
                    while($book = $borrowed_books->fetch_assoc()): 
                        $due_date = new DateTime($book['due_date']);
                        $today = new DateTime();
                        $is_overdue = $today > $due_date;
                        $interval = $today->diff($due_date);
                        $days_left = $is_overdue ? 0 : $interval->days;
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
                            <span class="badge <?php echo $days_left <= 3 ? 'danger' : ($days_left <= 7 ? 'warning' : 'success'); ?>">
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
    <button class="btn-small btn-view" 
            onclick="returnBook('<?php echo $book['record_id']; ?>', '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
        <i class="fas fa-undo"></i> Return
    </button>
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
        
        <!-- Available Books -->
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
                        <td>14 days</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-approve" onclick="borrowBook(<?php echo $book['id']; ?>)">
                                    <i class="fas fa-book"></i> Borrow
                                </button>

                                <button class="btn-small btn-reserve" onclick="reserveBook(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
                                     <i class="fas fa-bookmark"></i> Reserve
                                </button>
                                <button class="btn-small btn-view" onclick="viewBookDetails(<?php echo $book['id']; ?>)">
                                    <i class="fas fa-info-circle"></i> Details
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
                    <p>Please check back later or ask library staff for assistance.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- My Reservations -->
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
                        <button class="btn-small btn-success" onclick="checkAvailability(<?php echo $reservation['book_id']; ?>, <?php echo $reservation['id']; ?>)">
                            <i class="fas fa-bell"></i> Notify Me
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


        <!-- Borrowing History -->
        <div class="table-container" id="history">
            <h2><i class="fas fa-history"></i> Recent Borrowing History</h2>
            <?php if ($borrowing_history->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Duration</th>
                        <th>Status</th>
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
                        <td>
                            <span class="status-badge status-approved">
                                Returned
                            </span>
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
        
        <!-- Fines Summary Section -->
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
                <div class="fine-item">
                    <div class="fine-label">Unpaid Fine Count:</div>
                    <div class="fine-count">
                        <?php echo $fines_data['unpaid_count'] ?? 0; ?> items
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
            <p>Student Dashboard - Borrowing Limit: 3 books | Borrowing Period: 14 days</p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/scripts.js"></script>
    <script>
    // Book borrowing function
    function borrowBook(bookId) {
        if (!confirm('Borrow this book for 14 days?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('book_id', bookId);
        
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
    
    // Book return function - updated with better error handling
function returnBook(recordId, bookTitle) {
    if (!confirm('Return "' + bookTitle + '"?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('record_id', recordId);
    
    // Show loading state
    const returnBtn = event.target;
    const originalText = returnBtn.innerHTML;
    returnBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Returning...';
    returnBtn.disabled = true;
    
    fetch('../../includes/return_book.php', {
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
            if (data.fine_applied && data.fine_amount > 0) {
                alert('✅ Book returned successfully!\n\n📚 Book: ' + bookTitle + 
                      '\n💰 Fine applied: ₱' + data.fine_amount.toFixed(2) + 
                      '\n📝 Reason: ' + data.reason);
            } else {
                alert('✅ Book returned successfully!\n\n📚 Book: ' + bookTitle);
            }
            // Refresh the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('❌ Error: ' + data.message);
            returnBtn.innerHTML = originalText;
            returnBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred. Please try again.');
        returnBtn.innerHTML = originalText;
        returnBtn.disabled = false;
    });
}
// Reserve book function
function reserveBook(bookId, bookTitle) {
    if (!confirm('Reserve "' + bookTitle + '"?\n\nYou will be notified when it becomes available.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('book_id', bookId);
    
    // Show loading state
    const reserveBtn = event.target;
    const originalText = reserveBtn.innerHTML;
    reserveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reserving...';
    reserveBtn.disabled = true;
    
    fetch('../../includes/reserve_book.php', {
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
            alert('✅ Book reserved successfully!\n\n📚 Book: ' + bookTitle + 
                  '\n📅 Reservation expires: ' + data.expiry_date + 
                  '\n📊 Your position in queue: ' + data.queue_position);
            // Refresh the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            if (data.available) {
                // Book is available, ask if they want to borrow instead
                if (confirm('This book is currently available!\n\nWould you like to borrow it instead?')) {
                    borrowBook(bookId);
                }
            } else {
                alert('❌ Error: ' + data.message);
            }
            reserveBtn.innerHTML = originalText;
            reserveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred. Please try again.');
        reserveBtn.innerHTML = originalText;
        reserveBtn.disabled = false;
    });
}

// Cancel reservation function
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
            alert('✅ Reservation cancelled successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred. Please try again.');
    });
}

// Check availability function
function checkAvailability(bookId, reservationId) {
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
                borrowBook(bookId);
            }
        } else {
            alert('Book is still unavailable. We will notify you when it becomes available.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred. Please try again.');
    });
}

// Enhanced payFine function with renewal option
function payFine(recordId, amount, bookTitle, renewAfterPayment = false) {
    const paymentMethod = prompt('Enter payment method (Cash/Card/Online):', 'Cash');
    if (!paymentMethod) {
        return;
    }
    
    const formData = new FormData();
    formData.append('record_id', recordId);
    formData.append('amount', amount);
    formData.append('method', paymentMethod);
    
    // For now, we'll use a simulated response since pay_fine.php might not exist
    // In production, you should use the actual API endpoint
    fetch('../../includes/pay_fine.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Fine paid successfully!\n\n📚 Book: ' + bookTitle + 
                  '\n💰 Amount: ₱' + amount.toFixed(2) + 
                  '\n💳 Payment method: ' + paymentMethod);
            
            if (renewAfterPayment) {
                // Try to renew the book after payment
                setTimeout(() => {
                    renewBook(recordId, bookTitle);
                }, 500);
            } else {
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Payment failed. Please try again.');
    });
}
    
    // Pay all fines
    function payAllFines() {
        const totalAmount = <?php echo $fines_data['total_unpaid'] ?? 0; ?>;
        if (totalAmount <= 0) {
            alert('You have no unpaid fines.');
            return;
        }
        
        if (!confirm(`Pay all fines totaling $${totalAmount.toFixed(2)}?`)) {
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
                alert('All fines paid successfully!\nPayment method: ' + paymentMethod + '\nTransaction ID: ' + data.transaction_id);
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
    
    // View book details
    function viewBookDetails(bookId) {
        window.open(`book_details.php?id=${bookId}`, 'Book Details', 'width=600,height=400');
    }
    
    // View all fines
    function viewAllFines() {
        window.open('fines_history.php', 'Fines History', 'width=800,height=600');
    }
    
    // Request fine waiver
    function requestFineWaiver() {
        const reason = prompt('Please explain why you are requesting a fine waiver:', 'Financial hardship / First-time offense');
        if (reason) {
            alert('Fine waiver request submitted. Library staff will review your request within 3 business days.');
            // In real application, this would submit a request to staff
        }
    }
    
    // Apply forgiveness program
    function applyForgiveness() {
        if (confirm('Apply for 50% fine forgiveness? You must return all overdue books within 7 days.')) {
            alert('Forgiveness program applied! Return your books within 7 days to qualify.');
        }
    }
    
    // Generate receipt
    function generateReceipt() {
        const receiptWindow = window.open('', 'Receipt', 'width=600,height=800');
        receiptWindow.document.write(`
            <html>
                <head>
                    <title>Library Fine Receipt</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .receipt-header { text-align: center; margin-bottom: 30px; }
                        .receipt-details { margin-bottom: 20px; }
                        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        .receipt-table th { background-color: #f5f5f5; }
                        .total { font-weight: bold; font-size: 1.2em; }
                        .footer { text-align: center; margin-top: 30px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="receipt-header">
                        <h2>Library Management System</h2>
                        <h3>Fine Payment Receipt</h3>
                        <p>Date: ${new Date().toLocaleDateString()}</p>
                        <p>Student: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    
                    <div class="receipt-details">
                        <p><strong>Unpaid Balance:</strong> $<?php echo number_format($fines_data['total_unpaid'] ?? 0, 2); ?></p>
                    </div>
                    
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Days Overdue</th>
                                <th>Fine Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $overdue_books->data_seek(0);
                            while($book = $overdue_books->fetch_assoc()):
                                $due_date = new DateTime($book['due_date']);
                                $today = new DateTime();
                                $days_overdue = $today->diff($due_date)->days;
                                if ($today > $due_date) $days_overdue = $days_overdue * 1;
                                
                                if ($book['fine_amount'] === null) {
                                    $fine_amount = calculateFine($days_overdue);
                                } else {
                                    $fine_amount = $book['fine_amount'];
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo $days_overdue; ?> days</td>
                                <td>$<?php echo number_format($fine_amount, 2); ?></td>
                                <td><?php echo $book['fine_paid'] ? 'Paid' : 'Unpaid'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>Thank you for paying your fines!</p>
                        <p>Bring this receipt to the library circulation desk.</p>
                        <p>Receipt ID: REC-<?php echo date('Ymd') . '-' . $_SESSION['user_id']; ?></p>
                    </div>
                </body>
            </html>
        `);
        receiptWindow.document.close();
        receiptWindow.focus();
        receiptWindow.print();
    }
    
    // Search books
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
    
    // Clear search
    function clearSearch() {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            searchBooks('');
        }
    }
    
    // Update dashboard stats
    function updateDashboardStats() {
        const overdueCount = <?php echo $overdue_books->num_rows; ?>;
        const overdueElement = document.querySelector('[data-stat="overdue-books"]');
        if (overdueElement) {
            overdueElement.textContent = overdueCount;
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardStats();
        
        // Set up auto-refresh for overdue books (every 5 minutes)
        setInterval(() => {
            const hasOverdue = <?php echo $overdue_books->num_rows > 0 ? 'true' : 'false'; ?>;
            if (hasOverdue) {
                // Refresh only the overdue section
                fetch('../../includes/get_overdue_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.count > 0) {
                            // Update count display
                            const countElements = document.querySelectorAll('.stat-number');
                            if (countElements[1]) { // Second stat card
                                countElements[1].textContent = data.count;
                            }
                        }
                    });
            }
        }, 300000); // 5 minutes
    });
    </script>
    
    <style>
    /* Additional styles for student dashboard */
    .warning-message, .success-message, .info-message {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .warning-message {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
    }
    
    .success-message {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    
    .info-message {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }
    
    .warning-message i, .success-message i, .info-message i {
        font-size: 1.5rem;
        margin-top: 2px;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .badge.danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge.warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge.success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge.info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .text-danger {
        color: #f44336;
        font-weight: 600;
    }
    
    .text-success {
        color: #4CAF50;
        font-weight: 600;
    }
    
    .text-warning {
        color: #FF9800;
    }
    
    .text-muted {
        color: #6c757d;
    }
    
    .text-right {
        text-align: right;
    }
    
    .total-due {
        font-size: 1.2rem;
    }
    
    .payment-options {
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .option-card {
        background: white;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }
    
    .option-icon {
        width: 50px;
        height: 50px;
        background: #4361ee;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .option-content h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #212529;
    }
    
    .option-content p {
        color: #6c757d;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    
    .fines-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .fine-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    
    .fine-label {
        font-weight: 600;
        color: #495057;
    }
    
    .fine-amount {
        font-size: 1.2rem;
        font-weight: 700;
    }
    
    .fine-count {
        font-weight: 600;
        color: #495057;
    }
    
    .fines-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .options-grid {
            grid-template-columns: 1fr;
        }
        
        .fines-summary {
            grid-template-columns: 1fr;
        }
        
        .fines-actions {
            flex-direction: column;
        }
        
        .fines-actions .btn {
            width: 100%;
        }
    }
    </style>
</body>
</html>