<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only librarians can access this page
if ($_SESSION['role'] !== 'librarian') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();

// Get all books
$books_query = "SELECT * FROM books ORDER BY title";
$books = $conn->query($books_query);

// Get borrowing records
$borrowings_query = "SELECT br.*, u.username, u.full_name, b.title 
                     FROM borrowing_records br 
                     JOIN users u ON br.user_id = u.id 
                     JOIN books b ON br.book_id = b.id 
                     WHERE br.status = 'borrowed' 
                     ORDER BY br.due_date ASC";
$borrowings = $conn->query($borrowings_query);

// Get overdue books
$overdue_query = "SELECT br.*, u.username, u.full_name, b.title 
                  FROM borrowing_records br 
                  JOIN users u ON br.user_id = u.id 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.status = 'borrowed' AND br.due_date < CURDATE() 
                  ORDER BY br.due_date ASC";
$overdue_books = $conn->query($overdue_query);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM books) as total_books,
    (SELECT SUM(copies_available) FROM books) as available_books,
    (SELECT COUNT(*) FROM borrowing_records WHERE status = 'borrowed') as borrowed_books,
    (SELECT COUNT(*) FROM borrowing_records WHERE due_date < CURDATE() AND status = 'borrowed') as overdue_books";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Library Management System</title>
    <link rel="stylesheet" href="../css/styles.css">
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
                <span class="role-badge librarian-badge">Librarian</span>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-header">
            <h1>Librarian Dashboard</h1>
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
                    <div class="stat-label">In Collection</div>
                </div>
                <div class="card-actions">
                    <a href="#books" class="action-btn">Manage Books</a>
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
                    <a href="#borrowings" class="action-btn">View All</a>
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
                    <a href="#overdue" class="action-btn">View Overdue</a>
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
                    <a href="#add-book" class="action-btn">Add Book</a>
                </div>
            </div>
        </div>
        
        <!-- Book Management -->
        <div class="table-container" id="books">
            <h2><i class="fas fa-book"></i> Book Management</h2>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search books...">
            </div>
            <?php if ($books->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Available</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td><?php echo $book['copies_available']; ?></td>
                        <td><?php echo $book['total_copies']; ?></td>
                        <td class="action-buttons">
                            <button class="btn-small btn-view">Edit</button>
                            <button class="btn-small btn-reject">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No books in the library collection.</p>
            <?php endif; ?>
        </div>
        
        <!-- Current Borrowings -->
        <div class="table-container" id="borrowings">
            <h2><i class="fas fa-exchange-alt"></i> Current Borrowings</h2>
            <?php if ($borrowings->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $borrowings->fetch_assoc()): 
                        $due_date = new DateTime($record['due_date']);
                        $today = new DateTime();
                        $is_overdue = $today > $due_date;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo htmlspecialchars($record['full_name']); ?> (<?php echo htmlspecialchars($record['username']); ?>)</td>
                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $is_overdue ? 'status-overdue' : 'status-borrowed'; ?>">
                                <?php echo $is_overdue ? 'Overdue' : 'Borrowed'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-small btn-view" onclick="markReturned(<?php echo $record['id']; ?>)">Mark Returned</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No current borrowings.</p>
            <?php endif; ?>
        </div>
        
        <!-- Overdue Books -->
        <div class="table-container" id="overdue">
            <h2><i class="fas fa-exclamation-triangle"></i> Overdue Books</h2>
            <?php if ($overdue_books->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $overdue_books->fetch_assoc()): 
                        $due_date = new DateTime($record['due_date']);
                        $today = new DateTime();
                        $interval = $today->diff($due_date);
                        $days_overdue = abs($interval->format('%a'));
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                        <td><?php echo $days_overdue; ?> days</td>
                        <td>
                            <button class="btn-small btn-view" onclick="sendReminder(<?php echo $record['user_id']; ?>, <?php echo $record['id']; ?>)">Send Reminder</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No overdue books at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- Add New Book Form -->
        <div class="table-container" id="add-book">
            <h2><i class="fas fa-plus-circle"></i> Add New Book</h2>
            <form id="add-book-form">
                <div class="form-group">
                    <label for="title">Book Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" required>
                </div>
                
                <div class="form-group">
                    <label for="copies">Number of Copies</label>
                    <input type="number" id="copies" name="copies" min="1" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="publication_year">Publication Year</label>
                    <input type="number" id="publication_year" name="publication_year" min="1000" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                </div>
                
                <button type="submit" class="btn">Add Book</button>
            </form>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Librarian Dashboard - Book and borrowing management</p>
        </div>
    </footer>
    
    <script src="../js/scripts.js"></script>
    <script>
    document.getElementById('add-book-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('../includes/add_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book added successfully!');
                this.reset();
                location.reload();
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
    
    function markReturned(recordId) {
        if (!confirm('Mark this book as returned?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        
        fetch('../includes/mark_returned.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book marked as returned!');
                location.reload();
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    function sendReminder(userId, recordId) {
        if (!confirm('Send overdue reminder to this user?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('record_id', recordId);
        
        fetch('../includes/send_reminder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    </script>
</body>
</html>