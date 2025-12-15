<!-- Book Inventory Report -->
<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-book"></i> Books by Category</h3>
    </div>
    <canvas id="categoryChart"></canvas>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-list-alt"></i> Category Statistics</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Books</th>
                    <th>Available Copies</th>
                    <th>Total Copies</th>
                    <th>Availability Rate</th>
                    <th>Utilization</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report_data['category_stats'] as $category): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($category['category'] ?: 'Uncategorized'); ?></strong></td>
                    <td><?php echo $category['total_books']; ?></td>
                    <td><?php echo $category['available_copies']; ?></td>
                    <td><?php echo $category['total_copies']; ?></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $category['availability_rate']; ?>%"></div>
                            <span><?php echo $category['availability_rate']; ?>%</span>
                        </div>
                    </td>
                    <td><?php echo 100 - $category['availability_rate']; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card">
        <h4><i class="fas fa-exclamation-circle"></i> Low Availability Books (< 20%)</h4>
        <ul class="summary-list">
            <?php foreach(array_slice($report_data['low_availability'], 0, 5) as $book): ?>
            <li>
                <span><?php echo htmlspecialchars($book['title']); ?></span>
                <strong class="text-danger"><?php echo $book['availability_percentage']; ?>%</strong>
            </li>
            <?php endforeach; ?>
            <?php if (empty($report_data['low_availability'])): ?>
            <li>No books with low availability</li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-calendar-plus"></i> Recent Acquisitions</h4>
        <ul class="summary-list">
            <?php 
            $recent_acquisitions = array_slice($report_data['acquisition'], -5);
            foreach(array_reverse($recent_acquisitions) as $acq): 
            ?>
            <li>
                <span><?php echo date('M Y', mktime(0, 0, 0, $acq['month'], 1, $acq['year'])); ?></span>
                <strong><?php echo $acq['books_added']; ?> books added</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-chart-line"></i> Book Acquisition Trend</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Books Added</th>
                    <th>Sample Titles</th>
                    <th>Growth</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $prev_count = 0;
                foreach($report_data['acquisition'] as $acq): 
                    $growth = $prev_count > 0 ? (($acq['books_added'] - $prev_count) / $prev_count * 100) : 0;
                    $prev_count = $acq['books_added'];
                ?>
                <tr>
                    <td><?php echo date('M Y', mktime(0, 0, 0, $acq['month'], 1, $acq['year'])); ?></td>
                    <td><?php echo $acq['books_added']; ?></td>
                    <td class="text-muted"><?php 
                        $titles = explode(', ', $acq['titles']);
                        echo htmlspecialchars(implode(', ', array_slice($titles, 0, 3))) . (count($titles) > 3 ? '...' : '');
                    ?></td>
                    <td>
                        <span class="<?php echo $growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <?php echo $growth >= 0 ? '+' : ''; ?><?php echo round($growth, 1); ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.progress-bar .progress {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 10px;
    transition: width 0.3s;
}

.progress-bar span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.8rem;
    font-weight: bold;
    color: #333;
    text-shadow: 0 0 2px white;
}
</style>

<script>
function initBookCharts() {
    // Category Distribution Bar Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?php echo json_encode($report_data['category_stats'] ?? []); ?>;
    
    const categories = categoryData.map(item => item.category || 'Uncategorized');
    const bookCounts = categoryData.map(item => item.total_books);
    const availableRates = categoryData.map(item => parseFloat(item.availability_rate));
    
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [
                {
                    label: 'Total Books',
                    data: bookCounts,
                    backgroundColor: '#4361ee',
                    borderColor: '#3a0ca3',
                    borderWidth: 1
                },
                {
                    label: 'Availability Rate (%)',
                    data: availableRates,
                    backgroundColor: 'rgba(76, 175, 80, 0.3)',
                    borderColor: '#4CAF50',
                    borderWidth: 2,
                    type: 'line',
                    yAxisID: 'y1'
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
                        text: 'Number of Books'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Availability Rate (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Book Distribution and Availability by Category'
                }
            }
        }
    });
}
</script>