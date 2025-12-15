<!-- Fines & Revenue Report -->
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-label">Total Revenue Collected</div>
        <div class="kpi-value">
            ₱<?php echo number_format($report_data['fines_summary']['total_paid'] ?? 0, 2); ?>
        </div>
        <div class="kpi-trend trend-up">+0% from last month</div>
    </div>
    
    <div class="kpi-card success">
        <div class="kpi-label">Collection Rate</div>
        <div class="kpi-value">
            <?php 
            $total_fines = ($report_data['fines_summary']['total_paid'] ?? 0) + ($report_data['fines_summary']['total_unpaid'] ?? 0);
            $collection_rate = $total_fines > 0 
                ? round(($report_data['fines_summary']['total_paid'] / $total_fines) * 100, 1)
                : 0;
            echo $collection_rate . '%';
            ?>
        </div>
        <div class="kpi-trend trend-up">+0% from last period</div>
    </div>
    
    <div class="kpi-card warning">
        <div class="kpi-label">Average Fine Amount</div>
        <div class="kpi-value">
            $<?php echo number_format($report_data['fines_summary']['avg_fine_amount'] ?? 0, 2); ?>
        </div>
        <div class="kpi-trend trend-up">+0% from average</div>
    </div>
    
    <div class="kpi-card danger">
        <div class="kpi-label">Outstanding Fines</div>
        <div class="kpi-value">
            ₱<?php echo number_format($report_data['fines_summary']['total_unpaid'] ?? 0, 2); ?>
        </div>
        <div class="kpi-trend trend-up">+0% from last month</div>
    </div>
</div>

<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-money-bill-wave"></i> Fines Revenue Trends</h3>
        <div class="chart-actions">
            <button class="btn-small" onclick="exportChart('finesTrendChart', 'fines_trends')">
                <i class="fas fa-download"></i> Export
            </button>
            <button class="btn-small" onclick="toggleChartData('finesTrendChart')">
                <i class="fas fa-eye"></i> Toggle
            </button>
        </div>
    </div>
    <canvas id="finesTrendChart"></canvas>
</div>

<div class="summary-cards">
    <div class="summary-card">
        <h4><i class="fas fa-chart-pie"></i> Fine Distribution by Reason</h4>
        <ul class="summary-list">
            <?php 
            $fine_reasons = [
                ['reason' => 'Overdue Return', 'amount' => rand(500, 800), 'percentage' => rand(60, 75)],
                ['reason' => 'Book Damage', 'amount' => rand(100, 200), 'percentage' => rand(15, 25)],
                ['reason' => 'Lost Book', 'amount' => rand(50, 150), 'percentage' => rand(5, 15)],
                ['reason' => 'Late Renewal', 'amount' => rand(20, 80), 'percentage' => rand(2, 8)],
                ['reason' => 'Other', 'amount' => rand(10, 50), 'percentage' => rand(1, 5)],
            ];
            usort($fine_reasons, function($a, $b) {
                return $b['amount'] - $a['amount'];
            });
            
            foreach($fine_reasons as $reason):
            ?>
            <li>
                <span><?php echo $reason['reason']; ?></span>
                <div style="flex-grow: 1; margin: 0 15px;">
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $reason['percentage']; ?>%"></div>
                    </div>
                </div>
                <strong>₱<?php echo number_format($reason['amount'], 2); ?></strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-bullseye"></i> Collection Targets</h4>
        <ul class="summary-list">
            <li>
                <span>Monthly Collection Goal</span>
                <strong>₱<?php echo number_format(($report_data['fines_summary']['total_paid'] ?? 0) * 1.3, 2); ?> / ₱1,000</strong>
            </li>
            <li>
                <span>Outstanding Reduction Target</span>
                <strong>-₱<?php echo number_format(($report_data['fines_summary']['total_unpaid'] ?? 0) * 0.3, 2); ?> / -₱500</strong>
            </li>
            <li>
                <span>Collection Efficiency</span>
                <strong><?php echo $collection_rate; ?>% / 85%</strong>
            </li>
            <li>
                <span>Dispute Resolution Rate</span>
                <strong>92% / 90%</strong>
            </li>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-calendar-alt"></i> Upcoming Fine Actions</h4>
        <ul class="summary-list">
            <?php 
            $upcoming_actions = [
                ['action' => 'Send Payment Reminders', 'date' => date('M d', strtotime('+1 day')), 'count' => rand(5, 15)],
                ['action' => 'Process Refund Requests', 'date' => date('M d', strtotime('+2 days')), 'count' => rand(1, 5)],
                ['action' => 'Update Fine Policies', 'date' => date('M d', strtotime('+5 days')), 'count' => 1],
                ['action' => 'Quarterly Revenue Report', 'date' => date('M d', strtotime('+7 days')), 'count' => 1],
            ];
            
            foreach($upcoming_actions as $action):
            ?>
            <li>
                <span>
                    <?php echo $action['action']; ?><br>
                    <small class="text-muted">Due: <?php echo $action['date']; ?></small>
                </span>
                <strong><?php echo $action['count']; ?> items</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-users"></i> Top Users with Outstanding Fines</h3>
        <div class="table-actions">
            <input type="text" class="search-input" placeholder="Search users..." onkeyup="searchTable('topFinesTable', this)">
            <button class="btn-small btn-success" onclick="sendBulkPaymentReminders()">
                <i class="fas fa-paper-plane"></i> Send Reminders
            </button>
        </div>
    </div>
    <div class="table-content">
        <table id="topFinesTable">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th>Total Fines</th>
                    <th>Paid Amount</th>
                    <th>Unpaid Amount</th>
                    <th>Fine Count</th>
                    <th>Last Fine Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data['top_fines'])): ?>
                    <?php $rank = 1; ?>
                    <?php foreach($report_data['top_fines'] as $user): ?>
                    <tr>
                        <td>
                            <span class="rank-badge rank-<?php echo $rank; ?>">
                                <?php echo $rank++; ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                        </td>
                        <td>
                            <strong>$<?php echo number_format($user['total_fines'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="text-success">
                                $<?php echo number_format($user['paid_amount'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-danger">
                                $<?php echo number_format($user['unpaid_amount'], 2); ?>
                            </span>
                        </td>
                        <td><?php echo $user['fine_count']; ?> fines</td>
                        <td>
                            <?php 
                            // Simulated last fine date
                            $last_date = date('M d, Y', strtotime('-'.rand(1, 30).' days'));
                            echo $last_date;
                            ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="viewUserFines(<?php echo $user['id'] ?? 0; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-small btn-success" onclick="recordPayment(<?php echo $user['id'] ?? 0; ?>)">
                                    <i class="fas fa-credit-card"></i>
                                </button>
                                <button class="btn-small btn-danger" onclick="waiveFine(<?php echo $user['id'] ?? 0; ?>)">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="no-data">
                                <i class="fas fa-smile fa-2x text-success"></i>
                                <p>No outstanding fines at the moment!</p>
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
        <h3><i class="fas fa-chart-line"></i> Daily Fines Revenue</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Fines</th>
                    <th>Paid Amount</th>
                    <th>Unpaid Amount</th>
                    <th>Fine Count</th>
                    <th>Collection Rate</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data['fines_trend'])): ?>
                    <?php 
                    $prev_paid = 0;
                    foreach($report_data['fines_trend'] as $day): 
                        $collection_rate = $day['daily_fines'] > 0 
                            ? round(($day['daily_paid'] / $day['daily_fines']) * 100, 1) 
                            : 0;
                        $trend = $prev_paid > 0 
                            ? (($day['daily_paid'] - $prev_paid) / $prev_paid * 100)
                            : 0;
                        $prev_paid = $day['daily_paid'];
                    ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                        <td><strong>$<?php echo number_format($day['daily_fines'], 2); ?></strong></td>
                        <td class="text-success">$<?php echo number_format($day['daily_paid'], 2); ?></td>
                        <td class="text-danger">$<?php echo number_format($day['daily_unpaid'], 2); ?></td>
                        <td><?php echo $day['fine_count']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $collection_rate; ?>%"></div>
                                <span><?php echo $collection_rate; ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="<?php echo $trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php echo $trend >= 0 ? '+' : ''; ?><?php echo round($trend, 1); ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="no-data">
                                <i class="fas fa-chart-line fa-2x text-muted"></i>
                                <p>No fines data for the selected period</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-calendar"></i> Monthly Revenue Forecast</h3>
    </div>
    <canvas id="revenueForecastChart"></canvas>
</div>

<style>
.rank-badge {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-weight: bold;
    color: white;
}

.rank-1 { background: #FFD700; color: #333; }
.rank-2 { background: #C0C0C0; }
.rank-3 { background: #CD7F32; }
.rank-4, .rank-5 { background: #6c757d; }
</style>

<script>
function initFinesCharts() {
    // Fines Trends Chart
    const finesCtx = document.getElementById('finesTrendChart').getContext('2d');
    const finesData = <?php echo json_encode($report_data['fines_trend'] ?? []); ?>;
    
    const dates = finesData.map(item => item.date);
    const dailyFines = finesData.map(item => parseFloat(item.daily_fines) || 0);
    const dailyPaid = finesData.map(item => parseFloat(item.daily_paid) || 0);
    const dailyUnpaid = finesData.map(item => parseFloat(item.daily_unpaid) || 0);
    
    new Chart(finesCtx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Total Fines',
                    data: dailyFines,
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderColor: '#4361ee',
                    borderWidth: 1
                },
                {
                    label: 'Paid Amount',
                    data: dailyPaid,
                    backgroundColor: 'rgba(76, 175, 80, 0.7)',
                    borderColor: '#4CAF50',
                    borderWidth: 1
                },
                {
                    label: 'Unpaid Amount',
                    data: dailyUnpaid,
                    backgroundColor: 'rgba(244, 67, 54, 0.7)',
                    borderColor: '#f44336',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Fines Revenue Breakdown'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount ($)'
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
    
    // Revenue Forecast Chart
    const forecastCtx = document.getElementById('revenueForecastChart').getContext('2d');
    
    // Generate forecast data
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const currentMonth = new Date().getMonth();
    const forecastMonths = months.slice(currentMonth - 2, currentMonth + 4);
    
    const actualData = Array(3).fill().map(() => Math.random() * 1000 + 500);
    const forecastData = Array(3).fill().map(() => Math.random() * 1000 + 600);
    
    new Chart(forecastCtx, {
        type: 'line',
        data: {
            labels: forecastMonths,
            datasets: [
                {
                    label: 'Actual Revenue',
                    data: [...actualData, null, null, null],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5
                },
                {
                    label: 'Forecast',
                    data: [null, null, null, ...forecastData],
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    borderWidth: 3,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Revenue Forecast (Next 3 Months)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                }
            }
        }
    });
}

function searchTable(tableId, input) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

function sendBulkPaymentReminders() {
    const unpaidCount = document.querySelectorAll('#topFinesTable tbody tr .text-danger').length;
    if (unpaidCount === 0) {
        alert('No users with unpaid fines.');
        return;
    }
    
    if (confirm(`Send payment reminders to ${unpaidCount} users with unpaid fines?`)) {
        // Show loading
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert(`Payment reminders sent to ${unpaidCount} users!`);
        }, 2000);
    }
}

function viewUserFines(userId) {
    // In a real app, this would open a modal or navigate to user fines page
    alert(`Viewing fines for user ID: ${userId}\nThis would show detailed fine history.`);
}

function recordPayment(userId) {
    const amount = prompt('Enter payment amount:', '10.00');
    if (amount && !isNaN(parseFloat(amount))) {
        const method = prompt('Payment method (Cash/Card/Online):', 'Cash');
        if (method) {
            // Simulate API call
            setTimeout(() => {
                alert(`Payment of $${amount} recorded via ${method}!\nUser ID: ${userId}`);
                // Refresh the row
                const row = event.target.closest('tr');
                if (row) {
                    // Update paid/unpaid amounts visually
                    const unpaidCell = row.querySelector('.text-danger');
                    if (unpaidCell) {
                        const currentUnpaid = parseFloat(unpaidCell.textContent.replace('$', ''));
                        const paidAmount = parseFloat(amount);
                        const newUnpaid = Math.max(0, currentUnpaid - paidAmount);
                        unpaidCell.textContent = '$' + newUnpaid.toFixed(2);
                        
                        // Update paid amount
                        const paidCell = row.querySelector('.text-success');
                        if (paidCell) {
                            const currentPaid = parseFloat(paidCell.textContent.replace('$', ''));
                            paidCell.textContent = '$' + (currentPaid + paidAmount).toFixed(2);
                        }
                    }
                }
            }, 500);
        }
    }
}

function waiveFine(userId) {
    if (confirm('Are you sure you want to waive fines for this user?')) {
        const reason = prompt('Reason for waiver:', 'First-time offense / Good standing');
        if (reason) {
            // Simulate API call
            setTimeout(() => {
                alert(`Fines waived for user ID: ${userId}\nReason: ${reason}`);
                // Remove or update the row
                const row = event.target.closest('tr');
                if (row) {
                    row.style.opacity = '0.5';
                    setTimeout(() => row.remove(), 500);
                }
            }, 500);
        }
    }
}
</script>