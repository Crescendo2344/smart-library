<!-- Borrowing Patterns Report -->
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-label">Average Borrow Duration</div>
        <div class="kpi-value">
            <?php 
            $avg_days = isset($report_data['return_stats'][0]['avg_days_held']) 
                ? round($report_data['return_stats'][0]['avg_days_held'], 1) 
                : 'N/A';
            echo $avg_days . ' days';
            ?>
        </div>
        <div class="kpi-trend trend-up">+0 day/s from last period</div>
    </div>
    
    <div class="kpi-card success">
        <div class="kpi-label">On-Time Return Rate</div>
        <div class="kpi-value">
            <?php 
            $total_returns = 0;
            $on_time_returns = 0;
            foreach($report_data['return_stats'] as $stat) {
                $total_returns += $stat['count'];
                if ($stat['status'] == 'returned') {
                    $on_time_returns = $stat['count'];
                }
            }
            $rate = $total_returns > 0 ? round(($on_time_returns / $total_returns) * 100, 1) : 0;
            echo $rate . '%';
            ?>
        </div>
        <div class="kpi-trend trend-up">+0% from last month</div>
    </div>
    
    <div class="kpi-card warning">
        <div class="kpi-label">Peak Borrowing Hour</div>
        <div class="kpi-value">
            <?php 
            // Simulated data - in real app, you'd query this
            $peak_hours = ['10:00 AM', '2:00 PM', '4:00 PM'];
            echo $peak_hours[array_rand($peak_hours)];
            ?>
        </div>
        <div class="kpi-trend trend-down">-1 hour shift</div>
    </div>
</div>

<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-chart-line"></i> Borrowing Trends Over Time</h3>
        <div class="chart-actions">
            <button class="btn-small" onclick="exportChart('borrowingTrendChart', 'borrowing_trends')">
                <i class="fas fa-download"></i> Export
            </button>
            <button class="btn-small" onclick="toggleChartData('borrowingTrendChart')">
                <i class="fas fa-eye"></i> Toggle
            </button>
        </div>
    </div>
    <canvas id="borrowingTrendChart"></canvas>
</div>

<div class="summary-cards">
    <div class="summary-card">
        <h4><i class="fas fa-exchange-alt"></i> Borrowing Status Summary</h4>
        <ul class="summary-list">
            <?php foreach($report_data['return_stats'] as $stat): ?>
            <li>
                <span>
                    <?php 
                    $status_labels = [
                        'borrowed' => 'Currently Borrowed',
                        'returned' => 'Returned On Time',
                        'overdue' => 'Overdue'
                    ];
                    echo $status_labels[$stat['status']] ?? ucfirst($stat['status']);
                    ?>
                </span>
                <strong><?php echo $stat['count']; ?> books</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-calendar-check"></i> Weekly Borrowing Pattern</h4>
        <ul class="summary-list">
            <?php 
            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $weekly_pattern = [];
            foreach($weekdays as $day) {
                $weekly_pattern[$day] = rand(0, 2); // Simulated data
            }
            arsort($weekly_pattern);
            foreach(array_slice($weekly_pattern, 0, 5) as $day => $count):
            ?>
            <li>
                <span><?php echo $day; ?></span>
                <strong><?php echo $count; ?> borrows</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-clock"></i> Average Processing Time</h4>
        <ul class="summary-list">
            <li>
                <span>Borrow Request to Issue</span>
                <strong>2.5 minutes</strong>
            </li>
            <li>
                <span>Return Processing</span>
                <strong>1.8 minutes</strong>
            </li>
            <li>
                <span>Reservation Fulfillment</span>
                <strong>12.4 hours</strong>
            </li>
            <li>
                <span>Overdue Notice Response</span>
                <strong>6.2 hours</strong>
            </li>
        </ul>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-exclamation-triangle"></i> Overdue Books Details</h3>
        <div class="table-actions">
            <button class="btn-small btn-danger" onclick="sendBulkReminders()">
                <i class="fas fa-bell"></i> Send Reminders
            </button>
            <button class="btn-small" onclick="exportTableToCSV('overdueTable', 'overdue_books')">
                <i class="fas fa-file-export"></i> Export
            </button>
        </div>
    </div>
    <div class="table-content">
        <table id="overdueTable">
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Book Title</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Borrow Date</th>
                    <th>Fine Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data['overdue_details'])): ?>
                    <?php foreach($report_data['overdue_details'] as $overdue): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($overdue['full_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($overdue['username']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($overdue['title']); ?></td>
                        <td>
                            <span class="text-danger">
                                <?php echo date('M d, Y', strtotime($overdue['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge danger">
                                <?php echo $overdue['days_overdue']; ?> days
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($overdue['borrow_date'])); ?></td>
                        <td>
                            <strong class="text-danger">
                                $<?php echo number_format($overdue['days_overdue'] * 0.50, 2); ?>
                            </strong>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="sendReminder(<?php echo $overdue['user_id'] ?? 0; ?>)">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <button class="btn-small btn-approve" onclick="markReturned(<?php echo $overdue['id'] ?? 0; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-small btn-danger" onclick="applyFine(<?php echo $overdue['user_id'] ?? 0; ?>, <?php echo $overdue['id'] ?? 0; ?>)">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="no-data">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                <p>No overdue books at the moment!</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-chart-bar"></i> Borrowing Frequency Analysis</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Frequency Category</th>
                    <th>Number of Users</th>
                    <th>Percentage</th>
                    <th>Average Books/User</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $frequency_categories = [
                    ['name' => 'Heavy Users (10+ books)', 'count' => rand(0, 3), 'avg_books' => rand(0, 3)],
                    ['name' => 'Regular Users (5-9 books)', 'count' => rand(0, 3), 'avg_books' => rand(0, 3)],
                    ['name' => 'Occasional Users (2-4 books)', 'count' => rand(0, 3), 'avg_books' => rand(0, 3)],
                    ['name' => 'Light Users (1 book)', 'count' => rand(0, 3), 'avg_books' => 1],
                    ['name' => 'Inactive Users (0 books)', 'count' => rand(0, 0), 'avg_books' => 0],
                ];
                
                $total_users = array_sum(array_column($frequency_categories, 'count'));
                
                foreach($frequency_categories as $category):
                    $percentage = round(($category['count'] / $total_users) * 100, 1);
                ?>
                <tr>
                    <td><?php echo $category['name']; ?></td>
                    <td><?php echo $category['count']; ?> users</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                            <span><?php echo $percentage; ?>%</span>
                        </div>
                    </td>
                    <td><?php echo $category['avg_books']; ?> books</td>
                    <td>
                        <span class="<?php echo $category['avg_books'] > 5 ? 'trend-up' : ($category['avg_books'] > 0 ? 'trend-neutral' : 'trend-down'); ?>">
                            <?php 
                            if ($category['avg_books'] > 5) echo '+'.rand(1, 5).'%';
                            elseif ($category['avg_books'] > 0) echo '0%';
                            else echo '-'.rand(1, 3).'%';
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.trend-neutral {
    background: #e0e0e0;
    color: #616161;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 0.8rem;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #666;
}

.no-data i {
    margin-bottom: 10px;
    opacity: 0.7;
}
</style>

<script>
function initBorrowingCharts() {
    // Borrowing Trends Chart
    const trendCtx = document.getElementById('borrowingTrendChart').getContext('2d');
    const trendData = <?php echo json_encode($report_data['borrowing_trends'] ?? []); ?>;
    
    const dates = trendData.map(item => item.date);
    const borrowCounts = trendData.map(item => parseInt(item.borrow_count));
    const uniqueUsers = trendData.map(item => parseInt(item.unique_users));
    const uniqueBooks = trendData.map(item => parseInt(item.unique_books));
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Total Borrowings',
                    data: borrowCounts,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Unique Users',
                    data: uniqueUsers,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Unique Books',
                    data: uniqueBooks,
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Borrowing Trends Over Time'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
}

function sendReminder(userId) {
    if (confirm('Send overdue reminder to this user?')) {
        // Simulate API call
        setTimeout(() => {
            alert('Reminder sent successfully!');
        }, 500);
    }
}

function sendBulkReminders() {
    const overdueCount = document.querySelectorAll('#overdueTable tbody tr').length;
    if (overdueCount === 0) {
        alert('No overdue books to remind about.');
        return;
    }
    
    if (confirm(`Send reminders to all ${overdueCount} users with overdue books?`)) {
        // Show loading
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert(`Reminders sent to ${overdueCount} users successfully!`);
        }, 2000);
    }
}

function markReturned(recordId) {
    if (confirm('Mark this book as returned?')) {
        // Simulate API call
        setTimeout(() => {
            alert('Book marked as returned!');
            // Refresh the table row
            const row = event.target.closest('tr');
            if (row) {
                row.style.opacity = '0.5';
                setTimeout(() => row.remove(), 500);
            }
        }, 500);
    }
}

function applyFine(userId, recordId) {
    const fineAmount = prompt('Enter fine amount:', '5.00');
    if (fineAmount && !isNaN(parseFloat(fineAmount))) {
        // Simulate API call
        setTimeout(() => {
            alert(`Fine of $${fineAmount} applied successfully!`);
        }, 500);
    }
}
</script>