<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';



// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_year = $_GET['year'] ?? date('Y');
$filter_month = $_GET['month'] ?? 'all';

// Build WHERE clause for filters
$where_clause = "WHERE f.user_id = ?";
$params = [$user_id];
$param_types = "i";

if ($filter_status !== 'all') {
    $where_clause .= " AND f.paid = ?";
    $params[] = ($filter_status === 'paid' ? 1 : 0);
    $param_types .= "i";
}

if ($filter_year !== 'all') {
    // Use payment_date or use the borrowing date from borrowing_records as fallback
    $where_clause .= " AND YEAR(COALESCE(f.payment_date, br.due_date)) = ?";
    $params[] = $filter_year;
    $param_types .= "s";
}

if ($filter_month !== 'all') {
    $where_clause .= " AND MONTH(COALESCE(f.payment_date, br.due_date)) = ?";
    $params[] = $filter_month;
    $param_types .= "s";
}

// Get all fines for the user
$fines_query = "SELECT 
    f.id,
    f.amount,
    f.reason,
    f.paid,
    f.payment_date,
    b.title as book_title,
    b.author as book_author,
    br.borrow_date,
    br.due_date,
    br.return_date,
    DATEDIFF(COALESCE(br.return_date, CURDATE()), br.due_date) as days_overdue,
    CASE 
        WHEN f.paid = 1 THEN 'Paid'
        WHEN f.paid = 0 AND br.return_date IS NOT NULL THEN 'Pending Payment'
        ELSE 'Active'
    END as status_label
FROM fines f
LEFT JOIN borrowing_records br ON f.borrowing_id = br.id
LEFT JOIN books b ON br.book_id = b.id
{$where_clause}
ORDER BY COALESCE(f.payment_date, br.due_date) DESC";

$stmt = $conn->prepare($fines_query);
if ($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$fines = $stmt->get_result();

// Get fine statistics
$stats_query = "SELECT 
    COUNT(*) as total_fines,
    SUM(CASE WHEN paid = 1 THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN paid = 0 THEN amount ELSE 0 END) as total_unpaid,
    AVG(amount) as avg_fine,
    MAX(amount) as max_fine,
    MIN(amount) as min_fine,
    COUNT(CASE WHEN paid = 1 THEN 1 END) as paid_count,
    COUNT(CASE WHEN paid = 0 THEN 1 END) as unpaid_count
FROM fines 
WHERE user_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get monthly fine trends - use payment_date or due_date
$monthly_query = "SELECT 
    YEAR(COALESCE(f.payment_date, br.due_date)) as year,
    MONTH(COALESCE(f.payment_date, br.due_date)) as month,
    COUNT(*) as fine_count,
    SUM(f.amount) as total_amount,
    SUM(CASE WHEN f.paid = 1 THEN f.amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN f.paid = 0 THEN f.amount ELSE 0 END) as unpaid_amount
FROM fines f
LEFT JOIN borrowing_records br ON f.borrowing_id = br.id
WHERE f.user_id = ?
GROUP BY YEAR(COALESCE(f.payment_date, br.due_date)), MONTH(COALESCE(f.payment_date, br.due_date))
ORDER BY year DESC, month DESC
LIMIT 12";

$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->bind_param("i", $user_id);
$monthly_stmt->execute();
$monthly_trends = $monthly_stmt->get_result();

// Get available years for filter
$years_query = "SELECT DISTINCT YEAR(COALESCE(f.payment_date, br.due_date)) as year 
                FROM fines f
                LEFT JOIN borrowing_records br ON f.borrowing_id = br.id
                WHERE f.user_id = ? 
                ORDER BY year DESC";
$years_stmt = $conn->prepare($years_query);
$years_stmt->bind_param("i", $user_id);
$years_stmt->execute();
$years_result = $years_stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines History - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="/smart-library/assets/css/fines_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-money-bill-wave"></i> Fines History
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="role-badge student-badge">Student</span>
                <a href="student.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Fines History & Payments</h1>
            <p>View your fine records, payment history, and manage outstanding balances</p>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card primary">
                <div class="summary-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Fines</h3>
                    <div class="summary-amount">
                        ₱<?php echo number_format($stats['total_paid'] + $stats['total_unpaid'], 2); ?>
                    </div>
                    <div class="summary-details">
                        <span class="paid">Paid: ₱<?php echo number_format($stats['total_paid'], 2); ?></span>
                        <span class="unpaid">Unpaid: ₱<?php echo number_format($stats['total_unpaid'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="summary-card success">
                <div class="summary-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="summary-content">
                    <h3>Paid Fines</h3>
                    <div class="summary-amount">
                        ₱<?php echo number_format($stats['total_paid'], 2); ?>
                    </div>
                    <div class="summary-details">
                        <span><?php echo $stats['paid_count']; ?> fines paid</span>
                        <span>Avg: ₱<?php echo number_format($stats['avg_fine'] ?? 0, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="summary-card danger">
                <div class="summary-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="summary-content">
                    <h3>Unpaid Balance</h3>
                    <div class="summary-amount">
                        ₱<?php echo number_format($stats['total_unpaid'], 2); ?>
                    </div>
                    <div class="summary-details">
                        <span><?php echo $stats['unpaid_count']; ?> pending fines</span>
                        <?php if ($stats['total_unpaid'] > 0): ?>
                        <a href="#unpaid-fines" class="pay-now-btn">
                            <i class="fas fa-credit-card"></i> Pay Now
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="summary-card info">
                <div class="summary-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-content">
                    <h3>Fine Statistics</h3>
                    <div class="summary-stats">
                        <div class="stat-item">
                            <span>Highest Fine:</span>
                            <strong>₱<?php echo number_format($stats['max_fine'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Lowest Fine:</span>
                            <strong>₱<?php echo number_format($stats['min_fine'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <h3><i class="fas fa-filter"></i> Filter Fines</h3>
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="status"><i class="fas fa-check-circle"></i> Status</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Paid Only</option>
                            <option value="unpaid" <?php echo $filter_status == 'unpaid' ? 'selected' : ''; ?>>Unpaid Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="year"><i class="fas fa-calendar-alt"></i> Year</label>
                        <select id="year" name="year" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_year == 'all' ? 'selected' : ''; ?>>All Years</option>
                            <?php while($year = $years_result->fetch_assoc()): ?>
                            <option value="<?php echo $year['year']; ?>" <?php echo $filter_year == $year['year'] ? 'selected' : ''; ?>>
                                <?php echo $year['year']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="month"><i class="fas fa-calendar"></i> Month</label>
                        <select id="month" name="month" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_month == 'all' ? 'selected' : ''; ?>>All Months</option>
                            <option value="1" <?php echo $filter_month == '1' ? 'selected' : ''; ?>>January</option>
                            <option value="2" <?php echo $filter_month == '2' ? 'selected' : ''; ?>>February</option>
                            <option value="3" <?php echo $filter_month == '3' ? 'selected' : ''; ?>>March</option>
                            <option value="4" <?php echo $filter_month == '4' ? 'selected' : ''; ?>>April</option>
                            <option value="5" <?php echo $filter_month == '5' ? 'selected' : ''; ?>>May</option>
                            <option value="6" <?php echo $filter_month == '6' ? 'selected' : ''; ?>>June</option>
                            <option value="7" <?php echo $filter_month == '7' ? 'selected' : ''; ?>>July</option>
                            <option value="8" <?php echo $filter_month == '8' ? 'selected' : ''; ?>>August</option>
                            <option value="9" <?php echo $filter_month == '9' ? 'selected' : ''; ?>>September</option>
                            <option value="10" <?php echo $filter_month == '10' ? 'selected' : ''; ?>>October</option>
                            <option value="11" <?php echo $filter_month == '11' ? 'selected' : ''; ?>>November</option>
                            <option value="12" <?php echo $filter_month == '12' ? 'selected' : ''; ?>>December</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="fines_history.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            
            <div class="active-filters">
                <?php if ($filter_status !== 'all'): ?>
                <span class="filter-tag">
                    <i class="fas fa-check-circle"></i> Status: <?php echo ucfirst($filter_status); ?>
                    <button onclick="removeFilter('status')">&times;</button>
                </span>
                <?php endif; ?>
                
                <?php if ($filter_year !== 'all'): ?>
                <span class="filter-tag">
                    <i class="fas fa-calendar-alt"></i> Year: <?php echo $filter_year; ?>
                    <button onclick="removeFilter('year')">&times;</button>
                </span>
                <?php endif; ?>
                
                <?php if ($filter_month !== 'all'): ?>
                <span class="filter-tag">
                    <i class="fas fa-calendar"></i> Month: <?php echo date('F', mktime(0, 0, 0, $filter_month, 1)); ?>
                    <button onclick="removeFilter('month')">&times;</button>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Fine Distribution</h3>
                    <button class="btn-small" onclick="exportChart('fineDistributionChart', 'fine_distribution')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <canvas id="fineDistributionChart"></canvas>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Monthly Trends</h3>
                    <button class="btn-small" onclick="exportChart('monthlyTrendsChart', 'monthly_trends')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>
        
        <!-- Fines Table -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Fine Records</h2>
                <div class="table-actions">
                    <input type="text" id="searchFines" placeholder="Search fines..." class="search-input">
                    <button class="btn-small" onclick="exportToCSV()">
                        <i class="fas fa-file-export"></i> Export CSV
                    </button>
                    <?php if ($stats['total_unpaid'] > 0): ?>
                    <button class="btn-small btn-success" onclick="payAllFines()">
                        <i class="fas fa-credit-card"></i> Pay All ($<?php echo number_format($stats['total_unpaid'], 2); ?>)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($fines->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="finesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Book Details</th>
                            <th>Fine Reason</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        $total_amount = 0;
                        while($fine = $fines->fetch_assoc()): 
                            $total_amount += $fine['amount'];
                        ?>
                        <tr class="<?php echo $fine['paid'] ? 'paid-row' : 'unpaid-row'; ?>">
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <?php if ($fine['book_title']): ?>
                                <div class="book-info">
                                    <strong><?php echo htmlspecialchars($fine['book_title']); ?></strong>
                                    <small class="text-muted">by <?php echo htmlspecialchars($fine['book_author']); ?></small>
                                    <?php if ($fine['days_overdue'] > 0): ?>
                                    <div class="fine-details">
                                        <span class="badge danger">
                                            <?php echo $fine['days_overdue']; ?> days overdue
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Book information not available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="reason-cell">
                                    <span><?php echo htmlspecialchars($fine['reason']); ?></span>
                                    <?php if ($fine['borrow_date']): ?>
                                    <small class="text-muted">
                                        Borrowed: <?php echo date('M d, Y', strtotime($fine['borrow_date'])); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($fine['due_date']): ?>
                                    <?php echo date('M d, Y', strtotime($fine['due_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="<?php echo $fine['paid'] ? 'text-success' : 'text-danger'; ?>">
                                    $<?php echo number_format($fine['amount'], 2); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $fine['paid'] ? 'status-approved' : 'status-overdue'; ?>">
                                    <?php echo $fine['status_label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fine['payment_date']): ?>
                                <span class="text-success">
                                    <?php echo date('M d, Y', strtotime($fine['payment_date'])); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-warning">Not Paid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!$fine['paid']): ?>
                                    <button class="btn-small btn-success" onclick="payFineWithReceipt(
                                        <?php echo $fine['id']; ?>, 
                                        <?php echo $fine['amount']; ?>, 
                                        '<?php echo addslashes($fine['book_title']); ?>', 
                                        '<?php echo addslashes($fine['reason']); ?>'
                                    )">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn-small btn-view" onclick="viewFineDetails(<?php echo $fine['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if (!$fine['paid']): ?>
                                    <button class="btn-small btn-info" onclick="requestWaiver(<?php echo $fine['id']; ?>)">
                                        <i class="fas fa-handshake"></i> Waiver
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                            <td colspan="4">
                                <strong class="total-amount">
                                    ₱<?php echo number_format($total_amount, 2); ?>
                                </strong>
                                <span class="text-muted">
                                    (Paid: ₱<?php echo number_format($stats['total_paid'], 2); ?> | 
                                    Unpaid: ₱<?php echo number_format($stats['total_unpaid'], 2); ?>)
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination">
                <button class="btn-small" onclick="prevPage()">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span id="pageInfo">Page 1 of 1</span>
                <button class="btn-small" onclick="nextPage()">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-check-circle fa-3x"></i>
                <h3>No Fines Found</h3>
                <p>You have no fine records matching the current filters.</p>
                <a href="fines_history.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Unpaid Fines Section -->
        <?php if ($stats['total_unpaid'] > 0): ?>
        <div class="unpaid-section" id="unpaid-fines">
            <div class="section-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Unpaid Fines</h2>
                <span class="badge danger">Balance: ₱<?php echo number_format($stats['total_unpaid'], 2); ?></span>
            </div>
            
            <div class="payment-options">
                <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
                
                <div class="options-grid">
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="option-content">
                            <h4>Cash Payment</h4>
                            <p>Pay at library circulation desk</p>
                            <button class="btn btn-primary" onclick="generatePaymentReceipt()">
                                <i class="fas fa-print"></i> Generate Payment Invoice
                            </button>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="option-content">
                            <h4>Bank Transfer</h4>
                            <p>Transfer to library bank account</p>
                            <button class="btn btn-success" onclick="showBankDetails()">
                                <i class="fas fa-building"></i> Bank Details
                            </button>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="option-content">
                            <h4>Mobile Payment</h4>
                            <p>Pay via GCash, PayPal, etc.</p>
                            <button class="btn btn-warning" onclick="showMobilePayment()">
                                <i class="fas fa-qrcode"></i> Scan to Pay
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="bulk-payment">
                    <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                    <div class="bulk-actions">
                        <button class="btn btn-success" onclick="payAllFines()">
                            <i class="fas fa-credit-card"></i> Pay All Unpaid Fines
                            <span class="badge">₱<?php echo number_format($stats['total_unpaid'], 2); ?></span>
                        </button>
                        <button class="btn btn-info" onclick="setupPaymentPlan()">
                            <i class="fas fa-calendar-check"></i> Setup Payment Plan
                        </button>
                        <button class="btn btn-danger" onclick="requestAllWaivers()">
                            <i class="fas fa-handshake"></i> Request All Waivers
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Fine Policies -->
        <div class="policies-section">
            <h2><i class="fas fa-book"></i> Fine Policies & Information</h2>
            
            <div class="policies-grid">
                <div class="policy-card">
                    <h3><i class="fas fa-clock"></i> Overdue Fines</h3>
                    <ul>
                        <li>₱0.50 per day after 2-day grace period</li>
                        <li>Maximum fine: ₱25.00 per book</li>
                        <li>No fines on weekends/holidays</li>
                        <li>Fine calculation starts from due date</li>
                    </ul>
                </div>
                
                <div class="policy-card">
                    <h3><i class="fas fa-tools"></i> Damage/Lost Books</h3>
                    <ul>
                        <li>Damage fees: ₱5.00 - ₱50.00</li>
                        <li>Lost books: Replacement cost + ₱10</li>
                        <li>Assessment by librarian required</li>
                        <li>Receipt required for lost books</li>
                    </ul>
                </div>
                
                <div class="policy-card">
                    <h3><i class="fas fa-handshake"></i> Fine Forgiveness</h3>
                    <ul>
                        <li>First-time offenders: 50% reduction</li>
                        <li>Return within 7 days: 25% reduction</li>
                        <li>Community service option available</li>
                        <li>Financial hardship considerations</li>
                    </ul>
                </div>
                
                <div class="policy-card">
                    <h3><i class="fas fa-ban"></i> Consequences</h3>
                    <ul>
                        <li>₱50+ unpaid: Borrowing privileges suspended</li>
                        <li>₱100+ unpaid: Account frozen</li>
                        <li>30+ days overdue: Report to administration</li>
                        <li>Semester end: Holds on grades/transcripts</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?> | Fines History</p>
            <p>Current Balance: 
                <strong class="<?php echo $stats['total_unpaid'] > 0 ? 'text-danger' : 'text-success'; ?>">
                    ₱<?php echo number_format($stats['total_unpaid'], 2); ?>
                </strong>
                | Last Updated: <?php echo date('F d, Y h:i A'); ?>
            </p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/fines_history.js"></script>
    <script>
        // Enhanced generatePaymentReceipt function
    function generatePaymentReceipt(fineData = null) {
        // If no specific fine data provided, show summary of all unpaid fines
        const totalUnpaid = <?php echo $stats['total_unpaid']; ?>;
        const unpaidCount = <?php echo $stats['unpaid_count']; ?>;
        const username = "<?php echo htmlspecialchars($_SESSION['username']); ?>";
        const userId = "<?php echo $_SESSION['user_id']; ?>";
        const currentDate = new Date();
        
        // Get unpaid fines data
        const unpaidFines = [];
        <?php 
        // Reset result pointer to get unpaid fines
        $fines->data_seek(0);
        while($fine = $fines->fetch_assoc()): 
            if (!$fine['paid']):
        ?>
        unpaidFines.push({
            id: <?php echo $fine['id']; ?>,
            book_title: "<?php echo addslashes($fine['book_title']); ?>",
            amount: <?php echo $fine['amount']; ?>,
            reason: "<?php echo addslashes($fine['reason']); ?>",
            due_date: "<?php echo $fine['due_date']; ?>",
            days_overdue: <?php echo $fine['days_overdue'] ?? 0; ?>
        });
        <?php 
            endif;
        endwhile; 
        ?>
        
        // If fineData is provided, generate receipt for specific fine
        // Otherwise, generate for all unpaid fines
        const receiptData = fineData ? {
            type: 'SINGLE',
            fines: [fineData],
            total: fineData.amount
        } : {
            type: 'BULK',
            fines: unpaidFines,
            total: totalUnpaid
        };
        
        const receiptWindow = window.open('', 'Payment Receipt', 'width=600,height=800');
        const receiptNumber = 'REC-' + Date.now().toString().slice(-8);
        const transactionId = 'TXN-' + Math.random().toString(36).substr(2, 9).toUpperCase();
        
        receiptWindow.document.write(`
            <html>
                <head>
                    <title>Library Fine Payment Receipt</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 20px; 
                            background-color: #f5f5f5;
                            color: #333;
                        }
                        .receipt-container {
                            max-width: 500px;
                            margin: 0 auto;
                            background-color: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 0 20px rgba(0,0,0,0.1);
                        }
                        .receipt-header { 
                            text-align: center; 
                            margin-bottom: 30px; 
                            border-bottom: 2px solid #333; 
                            padding-bottom: 20px; 
                        }
                        .library-name {
                            font-size: 24px;
                            font-weight: bold;
                            color: #2c3e50;
                            margin-bottom: 5px;
                        }
                        .receipt-title {
                            font-size: 20px;
                            color: #3498db;
                            margin-top: 10px;
                        }
                        .info-section {
                            margin-bottom: 25px;
                            padding-bottom: 15px;
                            border-bottom: 1px dashed #ddd;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 8px;
                        }
                        .info-label {
                            font-weight: bold;
                            color: #555;
                        }
                        .info-value {
                            color: #2c3e50;
                        }
                        .fine-items {
                            margin: 20px 0;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            overflow: hidden;
                        }
                        .fine-item {
                            display: flex;
                            justify-content: space-between;
                            padding: 10px 15px;
                            border-bottom: 1px solid #eee;
                        }
                        .fine-item:last-child {
                            border-bottom: none;
                        }
                        .fine-item-title {
                            flex: 2;
                        }
                        .fine-item-amount {
                            flex: 1;
                            text-align: right;
                            color: #e74c3c;
                        }
                        .total-amount {
                            font-size: 22px;
                            font-weight: bold;
                            color: #27ae60;
                            text-align: center;
                            margin: 20px 0;
                        }
                        .payment-details {
                            background-color: #e8f4fc;
                            padding: 15px;
                            border-radius: 8px;
                            margin: 20px 0;
                        }
                        .footer {
                            text-align: center;
                            margin-top: 30px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            color: #7f8c8d;
                            font-size: 14px;
                        }
                        .receipt-number {
                            color: #e74c3c;
                            font-weight: bold;
                            font-size: 16px;
                        }
                        .timestamp {
                            color: #95a5a6;
                            font-style: italic;
                        }
                        .print-button {
                            display: block;
                            width: 100%;
                            padding: 12px;
                            background-color: #3498db;
                            color: white;
                            border: none;
                            border-radius: 5px;
                            font-size: 16px;
                            cursor: pointer;
                            margin-top: 20px;
                            transition: background-color 0.3s;
                        }
                        .print-button:hover {
                            background-color: #2980b9;
                        }
                        .receipt-type-badge {
                            display: inline-block;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 12px;
                            margin-left: 10px;
                            background-color: #3498db;
                            color: white;
                        }
                        @media print {
                            .print-button { display: none; }
                            body { background-color: white; }
                            .receipt-container { box-shadow: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        <div class="receipt-header">
                            <div class="library-name">Smart Library System</div>
                            <div class="receipt-title">
                                FINE PAYMENT RECEIPT
                                <span class="receipt-type-badge">
                                    ${receiptData.type === 'SINGLE' ? 'Single Fine' : 'Bulk Payment'}
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-row">
                                <span class="info-label">Receipt No:</span>
                                <span class="info-value receipt-number">${receiptNumber}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Date:</span>
                                <span class="info-value timestamp">${currentDate.toLocaleString()}</span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-row">
                                <span class="info-label">Member ID:</span>
                                <span class="info-value">LIB-${userId.toString().padStart(6, '0')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Name:</span>
                                <span class="info-value">${username}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Items:</span>
                                <span class="info-value">${receiptData.fines.length} fine(s)</span>
                            </div>
                        </div>
                        
                        <div class="fine-items">
                            <div class="fine-item" style="background-color: #f8f9fa; font-weight: bold;">
                                <div class="fine-item-title">Description</div>
                                <div class="fine-item-amount">Amount</div>
                            </div>
                            ${receiptData.fines.map((fine, index) => `
                                <div class="fine-item">
                                    <div class="fine-item-title">
                                        ${index + 1}. ${fine.book_title || 'Book not specified'}<br>
                                        <small style="color: #666;">${fine.reason} (${fine.days_overdue || 0} days overdue)</small>
                                    </div>
                                    <div class="fine-item-amount">$${fine.amount.toFixed(2)}</div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="total-amount">
                            Total Amount: $${receiptData.total.toFixed(2)}
                        </div>
                        
                        <div class="payment-details">
                            <div class="info-row">
                                <span class="info-label">Payment Method:</span>
                                <span class="info-value">To be determined at payment</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Transaction ID:</span>
                                <span class="info-value">${transactionId}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Status:</span>
                                <span class="info-value" style="color: #f39c12;">● Pending Payment</span>
                            </div>
                        </div>
                        
                        <div class="footer">
                            <p><strong>Important Notice:</strong> This is a payment invoice/receipt. Actual payment confirmation will be issued upon receipt of funds.</p>
                            <p>Payment Instructions:</p>
                            <ol style="text-align: left; font-size: 12px; margin: 10px 0;">
                                <li>Present this receipt at the library circulation desk</li>
                                <li>Pay via cash, card, or bank transfer</li>
                                <li>Keep this receipt for your records</li>
                                <li>Allow 24 hours for payment processing</li>
                            </ol>
                            <p>For any queries, contact: library@smartlib.edu | (555) 123-4567</p>
                        </div>
                        
                        <button class="print-button" onclick="window.print()">🖨️ Print Receipt</button>
                        <button class="print-button" style="background-color: #27ae60; margin-top: 10px;" onclick="window.close()">
                            ✓ Close Window
                        </button>
                    </div>
                    
                    <script>
                        window.focus();
                        window.addEventListener('keydown', function(e) {
                            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                                e.preventDefault();
                                window.print();
                            }
                        });
                        window.onafterprint = function() {
                            setTimeout(() => window.close(), 1000);
                        };
                    <\/script>
                </body>
            </html>
        `);
        
        receiptWindow.document.close();
        
        return {
            receiptNumber,
            transactionId,
            amount: receiptData.total,
            fineCount: receiptData.fines.length,
            date: currentDate
        };
    }
    
    // Enhanced payFine function that generates receipt
    function payFineWithReceipt(fineId, amount, bookTitle, reason) {
        if (!confirm('Pay fine of $' + amount.toFixed(2) + ' for "' + bookTitle + '"?')) {
            return;
        }
        
        const fineData = {
            id: fineId,
            amount: amount,
            book_title: bookTitle,
            reason: reason
        };
        
        const receipt = generatePaymentReceipt(fineData);
        
        const paymentMethod = prompt('Enter payment method (Cash/Card/Bank Transfer):', 'Cash');
        if (!paymentMethod) return;
        
        const formData = new FormData();
        formData.append('fine_id', fineId);
        formData.append('amount', amount);
        formData.append('method', paymentMethod);
        formData.append('receipt_number', receipt.receiptNumber);
        formData.append('transaction_id', receipt.transactionId);
        
        fetch('../../includes/pay_fine.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment successful!\nReceipt: ' + receipt.receiptNumber + '\nTransaction: ' + receipt.transactionId);
                setTimeout(() => location.reload(), 2000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    // Initialize charts and other functions
    document.addEventListener('DOMContentLoaded', function() {
        // Fine Distribution Chart
        const distributionCtx = document.getElementById('fineDistributionChart').getContext('2d');
        const paidAmount = <?php echo $stats['total_paid']; ?>;
        const unpaidAmount = <?php echo $stats['total_unpaid']; ?>;
        
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid Fines', 'Unpaid Fines'],
                datasets: [{
                    data: [paidAmount, unpaidAmount],
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Fine Distribution'
                    }
                }
            }
        });
        
        // Monthly Trends Chart
        const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        const monthlyData = <?php 
            $monthly_data = [];
            $monthly_trends->data_seek(0);
            while($trend = $monthly_trends->fetch_assoc()) {
                if ($trend['year']) {
                    $monthly_data[] = [
                        'month' => date('M Y', mktime(0, 0, 0, $trend['month'], 1, $trend['year'])),
                        'total' => $trend['total_amount'],
                        'paid' => $trend['paid_amount'],
                        'unpaid' => $trend['unpaid_amount']
                    ];
                }
            }
            echo json_encode(array_reverse($monthly_data));
        ?>;
        
        const months = monthlyData.map(item => item.month);
        const totals = monthlyData.map(item => item.total);
        const paid = monthlyData.map(item => item.paid);
        const unpaid = monthlyData.map(item => item.unpaid);
        
        new Chart(trendsCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Total Fines',
                        data: totals,
                        backgroundColor: '#4361ee',
                        borderColor: '#3a0ca3',
                        borderWidth: 1
                    },
                    {
                        label: 'Paid Fines',
                        data: paid,
                        backgroundColor: '#4CAF50',
                        borderColor: '#2E7D32',
                        borderWidth: 1
                    },
                    {
                        label: 'Unpaid Fines',
                        data: unpaid,
                        backgroundColor: '#f44336',
                        borderColor: '#c62828',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Fine Trends'
                    }
                }
            }
        });
        
        // Initialize search
        const searchInput = document.getElementById('searchFines');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                searchTable(this.value);
            });
        }
        
        // Initialize pagination
        initPagination();
    });
    
    function searchTable(query) {
        const table = document.getElementById('finesTable');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const lowerQuery = query.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lowerQuery) ? '' : 'none';
        });
    }
    
    function initPagination() {
        const rowsPerPage = 10;
        const table = document.getElementById('finesTable');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        let currentPage = 1;
        
        function showPage(page) {
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            
            rows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });
            
            document.getElementById('pageInfo').textContent = 'Page ' + page + ' of ' + totalPages;
            currentPage = page;
        }
        
        window.prevPage = function() {
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        };
        
        window.nextPage = function() {
            if (currentPage < totalPages) {
                showPage(currentPage + 1);
            }
        };
        
        showPage(1);
    }
    
    function removeFilter(filterName) {
        const url = new URL(window.location);
        url.searchParams.delete(filterName);
        window.location.href = url.toString();
    }
    
    function payFine(fineId, amount) {
        payFineWithReceipt(fineId, amount, 'Fine Payment', 'Library Fine');
    }
    
    function payAllFines() {
        const totalAmount = <?php echo $stats['total_unpaid']; ?>;
        if (totalAmount <= 0) {
            alert('You have no unpaid fines.');
            return;
        }
        
        if (!confirm('Pay all unpaid fines totaling $' + totalAmount.toFixed(2) + '?')) {
            return;
        }
        
        const paymentMethod = prompt('Enter payment method (Cash/Card/Bank Transfer):', 'Cash');
        if (!paymentMethod) return;
        
        const receipt = generatePaymentReceipt();
        
        const formData = new FormData();
        formData.append('amount', totalAmount);
        formData.append('method', paymentMethod);
        formData.append('receipt_number', receipt.receiptNumber);
        formData.append('transaction_id', receipt.transactionId);
        
        fetch('../../includes/pay_all_fines.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All fines paid successfully!\nReceipt: ' + receipt.receiptNumber + '\nTransaction: ' + receipt.transactionId);
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
    
    function viewFineDetails(fineId) {
        window.open('fine_details.php?id=' + fineId, 'Fine Details', 'width=600,height=500');
    }
    
    function requestWaiver(fineId) {
        const reason = prompt('Please explain why you need a fine waiver:', 'Financial hardship / First-time offense');
        if (reason) {
            setTimeout(() => {
                alert('Waiver request submitted. Library staff will review within 3 business days.');
            }, 1000);
        }
    }
    
    function requestAllWaivers() {
        if (confirm('Request waivers for all unpaid fines? Provide a reason:')) {
            const reason = prompt('Reason for waiver request:', 'Financial hardship / Multiple offenses');
            if (reason) {
                alert('Waiver requests submitted for all unpaid fines.');
            }
        }
    }
    
    function showBankDetails() {
        alert('Bank Transfer Details:\n\nBank Name: Smart Library Bank\nAccount Name: Smart Library System\nAccount Number: 1234-5678-9012\nSwift Code: SMLIBPHMM\n\nPlease include your Member ID in the reference.');
    }
    
    function showMobilePayment() {
        alert('Mobile Payment Options:\n\n1. GCash: 0917-123-4567\n2. PayPal: library@smartlib.edu\n3. Maya: 0918-987-6543\n\nScan QR code at library desk for payment.');
    }
    
    function setupPaymentPlan() {
        const amount = <?php echo $stats['total_unpaid']; ?>;
        const months = prompt('Enter number of months for payment plan (1-12):', '3');
        if (months && months >= 1 && months <= 12) {
            const monthlyPayment = (amount / months).toFixed(2);
            alert('Payment Plan Setup:\n\nTotal Amount: $' + amount.toFixed(2) + '\nMonths: ' + months + '\nMonthly Payment: $' + monthlyPayment + '\n\nFirst payment due in 7 days.');
        }
    }