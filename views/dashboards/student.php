<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Get user info
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get student's borrowed books
$borrowed_query = "SELECT b.*, br.borrow_date, br.due_date, br.status 
                   FROM borrowing_records br 
                   JOIN books b ON br.book_id = b.id 
                   WHERE br.user_id = ? AND br.status = 'borrowed' 
                   ORDER BY br.due_date ASC";
$stmt = $conn->prepare($borrowed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();

// Get available books
$available_books_query = "SELECT * FROM books WHERE copies_available > 0 ORDER BY title LIMIT 10";
$available_books = $conn->query($available_books_query);

// Get borrowing history
$history_query = "SELECT b.title, b.author, br.borrow_date, br.return_date 
                  FROM borrowing_records br 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.user_id = ? AND br.status = 'returned' 
                  ORDER BY br.borrow_date DESC LIMIT 5";
$stmt2 = $conn->prepare($history_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$borrowing_history = $stmt2->get_result();

$conn->close();
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
                    <div class="stat-number" data-stat="overdue-books">0</div>
                    <div class="stat-label">Need to Return</div>
                </div>
                <div class="card-actions">
                    <a href="#overdue" class="action-btn">Check Now</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h3>Borrowing History</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $borrowing_history->num_rows; ?>+</div>
                    <div class="stat-label">Books Read</div>
                </div>
                <div class="card-actions">
                    <a href="#history" class="action-btn">View History</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-search"></i>
                    <h3>Browse Catalog</h3>
                </div>
                <div class="card-content">
                    <p>Explore our collection of books</p>
                </div>
                <div class="card-actions">
                    <a href="#available-books" class="action-btn">Browse Now</a>
                </div>
            </div>
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
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $borrowed_books->fetch_assoc()): 
                        $due_date = new DateTime($book['due_date']);
                        $today = new DateTime();
                        $is_overdue = $today > $due_date;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $is_overdue ? 'status-overdue' : 'status-borrowed'; ?>">
                                <?php echo $is_overdue ? 'Overdue' : 'Borrowed'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-small btn-view" onclick="returnBook(<?php echo $book['id']; ?>)">Return</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>You have no borrowed books at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- Available Books -->
        <div class="table-container" id="available-books">
            <h2><i class="fas fa-book"></i> Available Books</h2>
            <?php if ($available_books->num_rows > 0): ?>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search books...">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Available Copies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $available_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td><?php echo $book['copies_available']; ?></td>
                        <td>
                            <button class="btn-small btn-approve" onclick="borrowBook(<?php echo $book['id']; ?>)">Borrow</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No books available at the moment.</p>
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
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $borrowing_history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo htmlspecialchars($record['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                        <td><?php echo $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : 'Not returned'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>You have no borrowing history.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Student Dashboard - You can borrow up to 5 books at a time</p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/scripts.js"></script>
</body>
</html>