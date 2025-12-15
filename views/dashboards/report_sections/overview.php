<!-- Overview Dashboard -->
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-label">System Health Score</div>
        <div class="kpi-value"><?php 
            $total_books = $report_data['overview']['total_books'] ?? 1;
            $available_books = $report_data['overview']['available_books'] ?? 0;
            $overdue_books = $report_data['overview']['overdue_books'] ?? 0;
            $score = round(($available_books/$total_books) * 100 - ($overdue_books * 5));
            echo max(0, $score) . '%';
        ?></div>
        <div class="kpi-trend trend-up">+5% from last month</div>
    </div>
    
    <div class="kpi-card success">
        <div class="kpi-label">User Engagement Rate</div>
        <div class="kpi-value"><?php 
            $total_users = $report_data['overview']['total_users'] ?? 1;
            $recent_borrowings = $report_data['overview']['recent_borrowings'] ?? 0;
            echo round(($recent_borrowings/$total_users) * 100) . '%';
        ?></div>
        <div class="kpi-trend trend-up">+12% from last month</div>
    </div>
    
    <div class="kpi-card warning">
        <div class="kpi-label">Book Utilization</div>
        <div class="kpi-value"><?php 
            $total_books = $report_data['overview']['total_books'] ?? 1;
            $borrowed_books = $report_data['overview']['borrowed_books'] ?? 0;
            echo round(($borrowed_books/$total_books) * 100) . '%';
        ?></div>
        <div class="kpi-trend trend-up">0% from last month</div>
    </div>
</div>

<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-chart-line"></i> Daily Activity (Last 30 Days)</h3>
        <div class="chart-actions">
            <button class="btn-small" onclick="toggleChart('activityChart')">Toggle</button>
        </div>
    </div>
    <canvas id="activityChart"></canvas>
</div>

<div class="summary-cards">
    <div class="summary-card">
        <h4><i class="fas fa-book"></i> Top 5 Most Borrowed Books</h4>
        <ul class="summary-list">
            <?php foreach(array_slice($report_data['top_books'], 0, 5) as $book): ?>
            <li>
                <span><?php echo htmlspecialchars($book['title']); ?></span>
                <strong><?php echo $book['borrow_count']; ?> borrows</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-exclamation-triangle"></i> System Alerts</h4>
        <ul class="summary-list">
            <li>
                <span>Pending User Approvals</span>
                <strong class="text-warning"><?php echo $report_data['overview']['pending_users'] ?? 0; ?> users</strong>
            </li>
            <li>
                <span>Overdue Books</span>
                <strong class="text-danger"><?php echo $report_data['overview']['overdue_books'] ?? 0; ?> books</strong>
            </li>
            <li>
                <span>Low Availability Books</span>
                <strong class="text-warning"><?php 
                    
                    echo count(array_filter($report_data['top_books'] ?? [], function($book) {
                        return ($book['availability_percentage'] ?? 100) < 20;
                    }));
                ?> titles</strong>
            </li>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-bullseye"></i> Monthly Targets</h4>
        <ul class="summary-list">
            <li>
                <span>New User Registrations</span>
                <strong><?php echo $report_data['overview']['new_users'] ?? 0; ?> / 50</strong>
            </li>
            <li>
                <span>Books Borrowed</span>
                <strong><?php echo $report_data['overview']['recent_borrowings'] ?? 0; ?> / 200</strong>
            </li>
            <li>
                <span>Fines Collected</span>
                <strong>₱<?php echo number_format(($report_data['overview']['pending_fines'] ?? 0) * 0.3, 2); ?> / ₱500</strong>
            </li>
        </ul>
    </div>
</div>

<script>
function initOverviewCharts() {
    // Daily Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityData = <?php echo json_encode($report_data['daily_activity'] ?? []); ?>;
    
    const dates = activityData.map(item => item.date);
    const borrowCounts = activityData.map(item => parseInt(item.borrow_count));
    
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Borrowings',
                data: borrowCounts,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Library Activity'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Borrowings'
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

function toggleChart(chartId) {
    const chart = Chart.getChart(chartId);
    if (chart) {
        chart.options.plugins.legend.display = !chart.options.plugins.legend.display;
        chart.update();
    }
}
</script>