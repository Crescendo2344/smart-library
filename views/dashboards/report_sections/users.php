
<div class="chart-container">
    <div class="chart-header">
        <h3><i class="fas fa-users"></i> Users by Role</h3>
    </div>
    <canvas id="roleChart"></canvas>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-chart-bar"></i> User Role Statistics</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Total Users</th>
                    <th>Approved</th>
                    <th>Pending</th>
                    <th>Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report_data['role_stats'] as $stat): ?>
                <tr>
                    <td><span class="role-badge <?php echo $stat['role']; ?>-badge"><?php echo ucfirst($stat['role']); ?></span></td>
                    <td><?php echo $stat['count']; ?></td>
                    <td><span class="status-badge status-approved"><?php echo $stat['approved_count']; ?></span></td>
                    <td><span class="status-badge status-pending"><?php echo $stat['pending_count']; ?></span></td>
                    <td><?php echo round(($stat['approved_count'] / $stat['count']) * 100, 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card">
        <h4><i class="fas fa-user-plus"></i> New User Registrations</h4>
        <ul class="summary-list">
            <?php foreach(array_slice($report_data['new_users'], 0, 5) as $user): ?>
            <li>
                <span><?php echo date('M d', strtotime($user['date'])); ?></span>
                <strong><?php echo $user['new_users']; ?> new users</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="summary-card">
        <h4><i class="fas fa-star"></i> Most Active Users</h4>
        <ul class="summary-list">
            <?php foreach(array_slice($report_data['active_users'], 0, 5) as $user): ?>
            <li>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                <strong><?php echo $user['borrow_count']; ?> borrows</strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-user-clock"></i> Detailed User Activity</h3>
    </div>
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Total Borrows</th>
                    <th>Unique Books</th>
                    <th>Avg. Days/Borrow</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report_data['active_users'] as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><span class="role-badge <?php echo $user['role']; ?>-badge"><?php echo ucfirst($user['role']); ?></span></td>
                    <td><?php echo $user['borrow_count']; ?></td>
                    <td><?php echo $user['unique_books']; ?></td>
                    <td><?php echo round($user['borrow_count'] > 0 ? 14 * $user['unique_books'] / $user['borrow_count'] : 0, 1); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function initUserCharts() {
    
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    const roleData = <?php echo json_encode($report_data['role_stats'] ?? []); ?>;
    
    const roles = roleData.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1));
    const counts = roleData.map(item => item.count);
    
    const colors = {
        'student': '#4CAF50',
        'teacher': '#2196F3',
        'librarian': '#FF9800',
        'staff': '#9C27B0'
    };
    
    const backgroundColors = roleData.map(item => colors[item.role] || '#6c757d');
    
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: roles,
            datasets: [{
                data: counts,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'User Distribution by Role'
                }
            }
        }
    });
}
</script>