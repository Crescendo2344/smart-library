// Reports Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize datepickers
    initDatePickers();
    
    // Initialize search functionality
    initSearch();
    
    // Load real-time data
    loadRealTimeData();
});

function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 0.9rem;
                z-index: 1000;
                max-width: 300px;
                pointer-events: none;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
    });
}

function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set max date to today
        const today = new Date().toISOString().split('T')[0];
        input.max = today;
    });
}

function initSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const table = this.closest('.table-container')?.querySelector('table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }
        });
    });
}

function loadRealTimeData() {
    // Load real-time updates every 60 seconds
    setInterval(() => {
        fetch('../../includes/get_realtime_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRealTimeStats(data.stats);
                }
            })
            .catch(error => console.error('Error loading real-time data:', error));
    }, 60000);
}

function updateRealTimeStats(stats) {
    // Update quick stats
    const statElements = {
        'total_users': document.querySelector('.stat-card:nth-child(1) h3'),
        'total_books': document.querySelector('.stat-card:nth-child(2) h3'),
        'borrowed_books': document.querySelector('.stat-card:nth-child(3) h3'),
        'overdue_books': document.querySelector('.stat-card:nth-child(4) h3')
    };
    
    for (const [key, element] of Object.entries(statElements)) {
        if (element && stats[key] !== undefined) {
            // Animate number change
            animateNumberChange(element, stats[key]);
        }
    }
}

function animateNumberChange(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    if (currentValue === newValue) return;
    
    const duration = 500; // ms
    const startTime = Date.now();
    const difference = newValue - currentValue;
    
    function updateNumber() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(currentValue + (difference * easeOut));
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// Chart export functions
function exportChart(chartId, fileName = 'chart') {
    const chart = Chart.getChart(chartId);
    if (chart) {
        const link = document.createElement('a');
        link.download = `${fileName}.png`;
        link.href = chart.toBase64Image();
        link.click();
    }
}

function toggleChartData(chartId) {
    const chart = Chart.getChart(chartId);
    if (chart) {
        chart.data.datasets.forEach(dataset => {
            dataset.hidden = !dataset.hidden;
        });
        chart.update();
    }
}

// Print specific section
function printSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .print-header { text-align: center; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h2>Library Management System Report</h2>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>
                ${section.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Data export functions
function exportTableToCSV(tableId, filename = 'data') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const rowData = [];
        const cols = row.querySelectorAll('th, td');
        
        cols.forEach(col => {
            let text = col.textContent.trim();
            // Handle commas in data
            text = text.replace(/"/g, '""');
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = `"${text}"`;
            }
            rowData.push(text);
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.href = url;
    link.download = `${filename}_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Dashboard refresh
function refreshDashboard() {
    const event = new Event('refreshDashboard');
    document.dispatchEvent(event);
}

// Event listener for dashboard refresh
document.addEventListener('refreshDashboard', function() {
    // Reload the page or fetch updated data
    location.reload();
});

// Auto-refresh every 5 minutes
setTimeout(() => {
    if (confirm('Dashboard data is 5 minutes old. Refresh now?')) {
        refreshDashboard();
    }
}, 300000); // 5 minutes