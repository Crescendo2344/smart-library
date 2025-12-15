<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';


if ($_SESSION['role'] !== 'staff') {
    header("Location: staff.php");
    exit();
}


$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

$conn = getDBConnection();


function getReportData($conn, $report_type, $start_date, $end_date) {
    $data = [];
    
    switch($report_type) {
        case 'overview':
            $data = getOverviewReport($conn, $start_date, $end_date);
            break;
        case 'users':
            $data = getUserReport($conn, $start_date, $end_date);
            break;
        case 'books':
            $data = getBookReport($conn, $start_date, $end_date);
            break;
        case 'borrowing':
            $data = getBorrowingReport($conn, $start_date, $end_date);
            break;
        case 'fines':
            $data = getFinesReport($conn, $start_date, $end_date);
            break;
        default:
            $data = getOverviewReport($conn, $start_date, $end_date);
    }
    
    return $data;
}


function getOverviewReport($conn, $start_date, $end_date) {
    $data = [];
    
    
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE approved = FALSE) as pending_users,
        (SELECT COUNT(*) FROM books) as total_books,
        (SELECT SUM(copies_available) FROM books) as available_books,
        (SELECT COUNT(*) FROM borrowing_records WHERE status = 'borrowed') as borrowed_books,
        (SELECT COUNT(*) FROM borrowing_records WHERE status = 'overdue') as overdue_books,
        (SELECT COUNT(*) FROM borrowing_records WHERE DATE(borrow_date) BETWEEN '$start_date' AND '$end_date') as recent_borrowings,
        (SELECT COUNT(*) FROM users WHERE DATE(registration_date) BETWEEN '$start_date' AND '$end_date') as new_users,
        (SELECT COALESCE(SUM(amount), 0) FROM fines WHERE paid = FALSE) as pending_fines";
    
    $result = $conn->query($stats_query);
    $data['overview'] = $result->fetch_assoc();
    
    // Daily activity for the last 30 days
    $activity_query = "SELECT 
        DATE(borrow_date) as date,
        COUNT(*) as borrow_count
    FROM borrowing_records 
    WHERE borrow_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(borrow_date)
    ORDER BY date";
    
    $result = $conn->query($activity_query);
    $data['daily_activity'] = [];
    while($row = $result->fetch_assoc()) {
        $data['daily_activity'][] = $row;
    }
    
    // Top books
    $top_books_query = "SELECT 
        b.title,
        b.author,
        b.category,
        COUNT(br.id) as borrow_count
    FROM books b
    LEFT JOIN borrowing_records br ON b.id = br.book_id
    WHERE br.borrow_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY b.id
    ORDER BY borrow_count DESC
    LIMIT 10";
    
    $result = $conn->query($top_books_query);
    $data['top_books'] = [];
    while($row = $result->fetch_assoc()) {
        $data['top_books'][] = $row;
    }
    
    return $data;
}

// User Report
function getUserReport($conn, $start_date, $end_date) {
    $data = [];
    
    // User statistics by role
    $role_stats_query = "SELECT 
        role,
        COUNT(*) as count,
        SUM(CASE WHEN approved = TRUE THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN approved = FALSE THEN 1 ELSE 0 END) as pending_count
    FROM users
    GROUP BY role";
    
    $result = $conn->query($role_stats_query);
    $data['role_stats'] = [];
    while($row = $result->fetch_assoc()) {
        $data['role_stats'][] = $row;
    }
    
    // New users over time
    $new_users_query = "SELECT 
        DATE(registration_date) as date,
        COUNT(*) as new_users,
        GROUP_CONCAT(username SEPARATOR ', ') as usernames
    FROM users
    WHERE registration_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(registration_date)
    ORDER BY date";
    
    $result = $conn->query($new_users_query);
    $data['new_users'] = [];
    while($row = $result->fetch_assoc()) {
        $data['new_users'][] = $row;
    }
    
    // Most active users
    $active_users_query = "SELECT 
        u.username,
        u.full_name,
        u.role,
        COUNT(br.id) as borrow_count,
        COUNT(DISTINCT br.book_id) as unique_books
    FROM users u
    LEFT JOIN borrowing_records br ON u.id = br.user_id
    WHERE br.borrow_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY u.id
    ORDER BY borrow_count DESC
    LIMIT 10";
    
    $result = $conn->query($active_users_query);
    $data['active_users'] = [];
    while($row = $result->fetch_assoc()) {
        $data['active_users'][] = $row;
    }
    
    return $data;
}

// Book Report
function getBookReport($conn, $start_date, $end_date) {
    $data = [];
    
    // Books by category
    $category_stats_query = "SELECT 
        category,
        COUNT(*) as total_books,
        SUM(copies_available) as available_copies,
        SUM(total_copies) as total_copies,
        ROUND(SUM(copies_available) * 100.0 / SUM(total_copies), 2) as availability_rate
    FROM books
    GROUP BY category
    ORDER BY total_books DESC";
    
    $result = $conn->query($category_stats_query);
    $data['category_stats'] = [];
    while($row = $result->fetch_assoc()) {
        $data['category_stats'][] = $row;
    }
    
    // Book acquisition over time
    $acquisition_query = "SELECT 
        YEAR(added_date) as year,
        MONTH(added_date) as month,
        COUNT(*) as books_added,
        GROUP_CONCAT(title SEPARATOR ', ') as titles
    FROM books
    WHERE added_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY YEAR(added_date), MONTH(added_date)
    ORDER BY year, month";
    
    $result = $conn->query($acquisition_query);
    $data['acquisition'] = [];
    while($row = $result->fetch_assoc()) {
        $data['acquisition'][] = $row;
    }
    
    // Books with low availability
    $low_availability_query = "SELECT 
        title,
        author,
        category,
        copies_available,
        total_copies,
        ROUND((copies_available * 100.0 / total_copies), 2) as availability_percentage
    FROM books
    WHERE copies_available <= 2 AND total_copies > 0
    ORDER BY availability_percentage ASC
    LIMIT 10";
    
    $result = $conn->query($low_availability_query);
    $data['low_availability'] = [];
    while($row = $result->fetch_assoc()) {
        $data['low_availability'][] = $row;
    }
    
    return $data;
}

// Borrowing Report
function getBorrowingReport($conn, $start_date, $end_date) {
    $data = [];
    
    // Borrowing trends
    $trends_query = "SELECT 
        DATE(borrow_date) as date,
        COUNT(*) as borrow_count,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT book_id) as unique_books
    FROM borrowing_records
    WHERE borrow_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(borrow_date)
    ORDER BY date";
    
    $result = $conn->query($trends_query);
    $data['borrowing_trends'] = [];
    while($row = $result->fetch_assoc()) {
        $data['borrowing_trends'][] = $row;
    }
    
    // Return statistics
    $return_stats_query = "SELECT 
        status,
        COUNT(*) as count,
        AVG(DATEDIFF(COALESCE(return_date, NOW()), borrow_date)) as avg_days_held
    FROM borrowing_records
    WHERE borrow_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY status";
    
    $result = $conn->query($return_stats_query);
    $data['return_stats'] = [];
    while($row = $result->fetch_assoc()) {
        $data['return_stats'][] = $row;
    }
    
    // Overdue analysis
    $overdue_query = "SELECT 
        u.username,
        u.full_name,
        b.title,
        DATEDIFF(NOW(), br.due_date) as days_overdue,
        br.due_date,
        br.borrow_date
    FROM borrowing_records br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    WHERE br.status = 'overdue'
    ORDER BY days_overdue DESC
    LIMIT 20";
    
    $result = $conn->query($overdue_query);
    $data['overdue_details'] = [];
    while($row = $result->fetch_assoc()) {
        $data['overdue_details'][] = $row;
    }
    
    return $data;
}

// Fines Report
function getFinesReport($conn, $start_date, $end_date) {
    $data = [];
    
    // Fines summary - UPDATED: removed created_at reference
    $fines_summary_query = "SELECT 
        SUM(CASE WHEN paid = TRUE THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN paid = FALSE THEN amount ELSE 0 END) as total_unpaid,
        COUNT(CASE WHEN paid = TRUE THEN 1 END) as paid_count,
        COUNT(CASE WHEN paid = FALSE THEN 1 END) as unpaid_count,
        AVG(amount) as avg_fine_amount
    FROM fines";
    
    $result = $conn->query($fines_summary_query);
    $data['fines_summary'] = $result->fetch_assoc();
    
    // Fines over time - UPDATED: Use payment_date or any available date field
    // Since fines table doesn't have created_at, we'll use payment_date for paid fines
    // and for unpaid fines, we'll need to join with borrowing_records to get borrow_date
    $fines_trend_query = "SELECT 
        DATE(br.borrow_date) as date,
        SUM(f.amount) as daily_fines,
        SUM(CASE WHEN f.paid = TRUE THEN f.amount ELSE 0 END) as daily_paid,
        SUM(CASE WHEN f.paid = FALSE THEN f.amount ELSE 0 END) as daily_unpaid,
        COUNT(f.id) as fine_count
    FROM fines f
    LEFT JOIN borrowing_records br ON f.borrowing_id = br.id
    WHERE br.borrow_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(br.borrow_date)
    ORDER BY date";
    
    $result = $conn->query($fines_trend_query);
    $data['fines_trend'] = [];
    while($row = $result->fetch_assoc()) {
        $data['fines_trend'][] = $row;
    }
    
    // Top users with fines - UPDATED: removed created_at reference
    $top_fines_query = "SELECT 
        u.id,
        u.username,
        u.full_name,
        COUNT(f.id) as fine_count,
        SUM(f.amount) as total_fines,
        SUM(CASE WHEN f.paid = TRUE THEN f.amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN f.paid = FALSE THEN f.amount ELSE 0 END) as unpaid_amount
    FROM users u
    JOIN fines f ON u.id = f.user_id
    GROUP BY u.id
    ORDER BY total_fines DESC
    LIMIT 10";
    
    $result = $conn->query($top_fines_query);
    $data['top_fines'] = [];
    while($row = $result->fetch_assoc()) {
        $data['top_fines'][] = $row;
    }
    
    return $data;
}

// Get report data
$report_data = getReportData($conn, $report_type, $start_date, $end_date);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="/smart-library/assets/css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-chart-bar"></i> Library Analytics
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="role-badge staff-badge">Staff</span>
                <a href="staff.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Report Header -->
        <div class="report-header">
            <h1><i class="fas fa-chart-pie"></i> System Reports & Analytics</h1>
            <p>Comprehensive insights into library operations and performance</p>
        </div>
        
        <!-- Report Filters -->
        <div class="report-filters card">
            <h3><i class="fas fa-filter"></i> Filter Reports</h3>
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="report_type"><i class="fas fa-file-alt"></i> Report Type</label>
                        <select id="report_type" name="report_type" onchange="this.form.submit()">
                            <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview Dashboard</option>
                            <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>>User Analytics</option>
                            <option value="books" <?php echo $report_type == 'books' ? 'selected' : ''; ?>>Book Inventory</option>
                            <option value="borrowing" <?php echo $report_type == 'borrowing' ? 'selected' : ''; ?>>Borrowing Patterns</option>
                            <option value="fines" <?php echo $report_type == 'fines' ? 'selected' : ''; ?>>Fines & Revenue</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Apply Filters
                        </button>
                        <button type="button" onclick="resetFilters()" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="button" onclick="exportReport()" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="date-range-info">
                <span class="badge info">
                    <i class="fas fa-clock"></i> Showing data from 
                    <?php echo date('M d, Y', strtotime($start_date)); ?> to 
                    <?php echo date('M d, Y', strtotime($end_date)); ?>
                </span>
                <span class="badge success">
                    <i class="fas fa-database"></i> Report Type: <?php echo ucfirst($report_type); ?>
                </span>
            </div>
        </div>
        
        <!-- Report Content -->
        <div class="report-content">
            <?php if ($report_type == 'overview'): ?>
                <!-- Overview Dashboard -->
                <?php include 'report_sections/overview.php'; ?>
                
            <?php elseif ($report_type == 'users'): ?>
                <!-- User Analytics -->
                <?php include 'report_sections/users.php'; ?>
                
            <?php elseif ($report_type == 'books'): ?>
                <!-- Book Inventory -->
                <?php include 'report_sections/books.php'; ?>
                
            <?php elseif ($report_type == 'borrowing'): ?>
                <!-- Borrowing Patterns -->
                <?php include 'report_sections/borrowing.php'; ?>
                
            <?php elseif ($report_type == 'fines'): ?>
                <!-- Fines & Revenue -->
                <?php include 'report_sections/fines.php'; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $report_data['overview']['total_users'] ?? '0'; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $report_data['overview']['total_books'] ?? '0'; ?></h3>
                    <p>Total Books</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $report_data['overview']['borrowed_books'] ?? '0'; ?></h3>
                    <p>Active Borrowings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $report_data['overview']['overdue_books'] ?? '0'; ?></h3>
                    <p>Overdue Books</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>₱<?php echo number_format($report_data['overview']['pending_fines'] ?? 0, 2); ?></h3>
                    <p>Pending Fines</p>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?> | Reports & Analytics Dashboard</p>
            <p>Report generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/reports.js"></script>
    <script>
    
    document.addEventListener('DOMContentLoaded', function() {
        const reportType = '<?php echo $report_type; ?>';
        
        switch(reportType) {
            case 'overview':
                initOverviewCharts();
                break;
            case 'users':
                initUserCharts();
                break;
            case 'books':
                initBookCharts();
                break;
            case 'borrowing':
                initBorrowingCharts();
                break;
            case 'fines':
                initFinesCharts();
                break;
        }
    });
    
    function resetFilters() {
        window.location.href = 'reports.php';
    }
    
    function exportReport() {
        const reportType = '<?php echo $report_type; ?>';
        const startDate = '<?php echo $start_date; ?>';
        const endDate = '<?php echo $end_date; ?>';
        
        
        alert(`Exporting ${reportType} report from ${startDate} to ${endDate}\n\nThis would generate a downloadable file in production.`);
        
        
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(<?php echo json_encode($report_data); ?>, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute("href", dataStr);
        downloadAnchor.setAttribute("download", `library_report_${reportType}_${startDate}_to_${endDate}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();
    }
    
    function printReport() {
        window.print();
    }
    </script>
</body>
</html>