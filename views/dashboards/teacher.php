<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Get user info
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get teacher's borrowed books
$borrowed_query = "SELECT b.*, br.borrow_date, br.due_date, br.status 
                   FROM borrowing_records br 
                   JOIN books b ON br.book_id = b.id 
                   WHERE br.user_id = ? AND br.status = 'borrowed' 
                   ORDER BY br.due_date ASC";
$stmt = $conn->prepare($borrowed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_books = $stmt->get_result();

// Get all books
$all_books_query = "SELECT * FROM books ORDER BY title LIMIT 15";
$all_books = $conn->query($all_books_query);

// Get extended borrowing limit (teachers have longer borrowing periods)
$borrowing_limit = 30; // Days for teachers

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Library Management System</title>
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
                <span class="role-badge teacher-badge">Teacher</span>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard-header">
            <h1>Teacher Dashboard</h1>
            <p>Access library resources with extended borrowing privileges</p>
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
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Borrowing Period</h3>
                </div>
                <div class="card-content">
                    <div class="stat-number"><?php echo $borrowing_limit; ?> days</div>
                    <div class="stat-label">Extended Limit</div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-search-plus"></i>
                    <h3>Research Materials</h3>
                </div>
                <div class="card-content">
                    <p>Access academic journals and research materials</p>
                </div>
                <div class="card-actions">
                    <a href="#research" class="action-btn">Browse</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Course Resources</h3>
                </div>
                <div class="card-content">
                    <p>Request books for your courses</p>
                </div>
                <div class="card-actions">
                    <a href="#course-resources" class="action-btn">Request</a>
                </div>
            </div>
        </div>
        
        <!-- Borrowed Books -->
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
                        $interval = $today->diff($due_date);
                        $days_remaining = $interval->format('%r%a');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $days_remaining < 0 ? 'status-overdue' : 'status-borrowed'; ?>">
                                <?php echo $days_remaining >= 0 ? $days_remaining . ' days' : 'Overdue'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-small btn-view" onclick="returnBook(<?php echo $book['id']; ?>)">Return</button>
                            <button class="btn-small btn-approve" onclick="renewBook(<?php echo $book['id']; ?>)">Renew</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>You have no borrowed books at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- Book Catalog -->
        <div class="table-container">
            <h2><i class="fas fa-book"></i> Book Catalog</h2>
            <?php if ($all_books->num_rows > 0): ?>
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
                    <?php while($book = $all_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td><?php echo $book['copies_available']; ?></td>
                        <td>
                            <?php if ($book['copies_available'] > 0): ?>
                            <button class="btn-small btn-approve" onclick="borrowBook(<?php echo $book['id']; ?>)">Borrow</button>
                            <?php else: ?>
                            <button class="btn-small" onclick="reserveBook(<?php echo $book['id']; ?>)">Reserve</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No books available in the catalog.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
            <p>Teacher Dashboard - Extended borrowing period: <?php echo $borrowing_limit; ?> days</p>
        </div>
    </footer>
    
    <script src="../js/scripts.js"></script>
    <script>
    function renewBook(bookId) {
        if (!confirm('Renew this book for another <?php echo $borrowing_limit; ?> days?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        fetch('../includes/renew_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book renewed successfully!');
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
    
    function reserveBook(bookId) {
        if (!confirm('Reserve this book? You will be notified when it becomes available.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        fetch('../includes/reserve_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book reserved successfully!');
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
    </script>
</body>
</html>