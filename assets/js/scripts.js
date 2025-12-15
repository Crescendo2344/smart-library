document.addEventListener('DOMContentLoaded', function() {
  
    const menuItems = document.querySelectorAll('.menu-item');
    if (menuItems.length > 0) {
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
  
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
   
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = this.closest('.table-container').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });
    
   
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (!input.value) {
            
            const twoWeeksLater = new Date();
            twoWeeksLater.setDate(twoWeeksLater.getDate() + 14);
            input.value = twoWeeksLater.toISOString().split('T')[0];
        }
       
        input.min = today;
    });
    
    
    updateDashboardStats();
});

function updateDashboardStats() {
  
    const statElements = document.querySelectorAll('[data-stat]');
    
    statElements.forEach(element => {
        const statType = element.getAttribute('data-stat');
        
        const mockData = {
            'total-books': Math.floor(Math.random() * 1000) + 500,
            'available-books': Math.floor(Math.random() * 500) + 100,
            'borrowed-books': Math.floor(Math.random() * 300) + 50,
            'pending-approvals': Math.floor(Math.random() * 10) + 1,
            'overdue-books': Math.floor(Math.random() * 20) + 1,
            'total-fines': '$' + (Math.random() * 100).toFixed(2)
        };
        
        if (mockData[statType]) {
            element.textContent = mockData[statType];
        }
    });
}


function handleUserAction(userId, action) {
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    
    fetch('includes/approve_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`User ${action}ed successfully!`);
            
            const row = document.getElementById(`user-row-${userId}`);
            if (row) {
                row.remove();
            }
        } else {
            alert(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}


function borrowBook(bookId) {
    if (!confirm('Are you sure you want to borrow this book?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('book_id', bookId);
    
    fetch('includes/borrow_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Book borrowed successfully!');
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


function returnBook(recordId) {
    if (!confirm('Are you sure you want to return this book?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('record_id', recordId);
    
    fetch('includes/return_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Book returned successfully!');
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