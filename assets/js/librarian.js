  
    function showAddBookModal() {
        document.getElementById('addBookModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function showBulkUploadModal() {
        document.getElementById('bulkUploadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = '';
    }
    
    
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    };
    
   
    
   
    document.getElementById('addBookForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        submitBtn.disabled = true;
        
        fetch('../../includes/add_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Book added successfully!');
                closeModal('addBookModal');
                this.reset();
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    
    document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const progressBar = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        progressBar.style.display = 'block';
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        submitBtn.disabled = true;
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = Math.round(percentComplete) + '%';
            }
        };
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        alert(`✅ Successfully uploaded ${data.added} books!`);
                        closeModal('bulkUploadModal');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                        submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
                        submitBtn.disabled = false;
                        progressBar.style.display = 'none';
                    }
                } catch (e) {
                    alert('❌ Invalid response from server');
                    submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
                    submitBtn.disabled = false;
                    progressBar.style.display = 'none';
                }
            } else {
                alert('❌ Upload failed. Please try again.');
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
                submitBtn.disabled = false;
                progressBar.style.display = 'none';
            }
        };
        
        xhr.open('POST', '../../includes/bulk_upload_books.php');
        xhr.send(formData);
    });
    
    
    function editBook(bookId) {
        fetch(`../../includes/get_book.php?id=${bookId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const formHTML = `
                        <form id="editBookForm" enctype="multipart/form-data">
                            <input type="hidden" name="book_id" value="${data.book.id}">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_title">Book Title *</label>
                                    <input type="text" id="edit_title" name="title" value="${escapeHtml(data.book.title)}" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_author">Author *</label>
                                    <input type="text" id="edit_author" name="author" value="${escapeHtml(data.book.author)}" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_isbn">ISBN *</label>
                                    <input type="text" id="edit_isbn" name="isbn" value="${escapeHtml(data.book.isbn)}" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_category">Category *</label>
                                    <select id="edit_category" name="category" required>
                                        <option value="">Select Category</option>
                                        ${getCategoryOptions(data.book.category)}
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_publisher">Publisher</label>
                                    <input type="text" id="edit_publisher" name="publisher" value="${escapeHtml(data.book.publisher || '')}">
                                </div>
                                <div class="form-group">
                                    <label for="edit_published_year">Publication Year</label>
                                    <input type="number" id="edit_published_year" name="published_year" 
                                           value="${data.book.published_year || ''}" 
                                           min="1000" max="${new Date().getFullYear()}">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_total_copies">Total Copies *</label>
                                    <input type="number" id="edit_total_copies" name="total_copies" 
                                           value="${data.book.total_copies}" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_copies_available">Available Copies *</label>
                                    <input type="number" id="edit_copies_available" name="copies_available" 
                                           value="${data.book.copies_available}" min="0" max="${data.book.total_copies}" required>
                                    <small>Cannot exceed total copies</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_location">Shelf Location</label>
                                <input type="text" id="edit_location" name="location" value="${escapeHtml(data.book.location || '')}">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea id="edit_description" name="description" rows="3">${escapeHtml(data.book.description || '')}</textarea>
                            </div>
                            
                            <div class="current-cover" style="margin-bottom: 15px;">
                                <label>Current Cover:</label>
                                ${data.book.cover_image ? 
                                    `<img src="/smart-library/assets/images/books/${data.book.cover_image}" style="max-width: 100px; border: 1px solid #ddd; border-radius: 4px;">` : 
                                    '<p><em>No cover image</em></p>'
                                }
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_cover_image">New Cover Image (optional)</label>
                                <input type="file" id="edit_cover_image" name="cover_image" accept="image/*">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('editBookModal')">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    `;
                    
                    document.getElementById('editBookFormContainer').innerHTML = formHTML;
                    
                    
                    document.getElementById('editBookForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        updateBook(bookId, new FormData(this));
                    });
                    
                    document.getElementById('editBookModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                } else {
                    alert('Error loading book details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load book details.');
            });
    }
    
    function updateBook(bookId, formData) {
        const submitBtn = document.querySelector('#editBookForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        fetch('../../includes/update_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Book updated successfully!');
                closeModal('editBookModal');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
    
    
    function archiveBook(bookId, bookTitle) {
        if (!confirm(`Archive "${bookTitle}"?\n\nThis will move the book to archive. It can be restored later.`)) {
            return;
        }
        
        window.location.href = `?delete=${bookId}`;
    }
    
    
    function restoreBook(bookId, bookTitle) {
        if (!confirm(`Restore "${bookTitle}" from archive?`)) {
            return;
        }
        
        window.location.href = `?restore=${bookId}`;
    }
    
    
    function permanentDelete(bookId, bookTitle) {
        if (!confirm(`Permanently delete "${bookTitle}"?\n\nThis action cannot be undone!`)) {
            return;
        }
        
        if (confirm('⚠️ WARNING: This will permanently delete the book and all its records.\n\nAre you absolutely sure?')) {
            window.location.href = `?permanent_delete=${bookId}`;
        }
    }
    
    function showCannotArchive(bookTitle) {
        alert(`Cannot archive "${bookTitle}" because it has active borrowings.\n\nPlease wait until all copies are returned.`);
    }
    
    
    
    function markReturned(recordId, bookTitle, isOverdue = false) {
        const message = isOverdue ? 
            `Mark "${bookTitle}" as returned and apply overdue fine?` : 
            `Mark "${bookTitle}" as returned?`;
        
        if (!confirm(message)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        
        fetch('../../includes/mark_returned.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.fine_applied) {
                    alert(`✅ Book returned successfully!\n\nA fine of ₱${data.fine_amount} has been applied.`);
                } else {
                    alert('✅ Book returned successfully!');
                }
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
        });
    }
    
    function extendDueDate(recordId) {
        const days = prompt('Extend due date by how many days?', '7');
        if (!days || isNaN(days) || days < 1) {
            alert('Please enter a valid number of days.');
            return;
        }
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        formData.append('days', days);
        
        fetch('../../includes/extend_due_date.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Due date extended by ${days} days!\nNew due date: ${data.new_due_date}`);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
        });
    }
    
    function sendReminder(userId, recordId) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('record_id', recordId);
        
        fetch('../../includes/send_reminder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Reminder sent successfully!');
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
        });
    }
    
    function applyFine(recordId, amount) {
        const confirmed = confirm(`Apply fine of ₱${amount.toFixed(2)} for this overdue book?`);
        if (!confirmed) return;
        
        const formData = new FormData();
        formData.append('record_id', recordId);
        formData.append('amount', amount);
        
        fetch('../../includes/apply_fine.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Fine of ₱${amount.toFixed(2)} applied successfully!`);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again.');
        });
    }
    
   
    
    function viewBookDetails(bookId) {
        window.open(`book_details.php?id=${bookId}`, 'Book Details', 'width=1000,height=800');
    }
    
    function viewBorrowerDetails(userId) {
        window.open(`user_details.php?id=${userId}`, 'User Details', 'width=800,height=600');
    }
    
    function contactBorrower(email, name) {
        window.open(`mailto:${email}?subject=Library Book Overdue Reminder&body=Dear ${encodeURIComponent(name)},%0D%0A%0D%0AThis is a reminder about your overdue library book.`, '_blank');
    }
    
    function callBorrower(name) {
        alert(`Call ${name} about their overdue book.`);
    }
    
    function exportTable() {
        alert('Export functionality coming soon!');
    }
    
    function exportBooks() {
        window.location.href = '../../includes/export_books.php';
    }
    
    function printInventory() {
        window.open('print_inventory.php', 'Print Inventory', 'width=800,height=600');
    }
    
    function showReports() {
        window.open('reports.php', 'Reports', 'width=1200,height=800');
    }
    
    function showQRGenerator() {
        alert('QR Code generator coming soon!');
    }
    
    
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getCategoryOptions(selected) {
        const categories = ['Fiction', 'Non-Fiction', 'Science', 'Technology', 'History', 
                          'Biography', 'Literature', 'Academic', 'Children', 'Other'];
        
        return categories.map(cat => 
            `<option value="${cat}" ${cat === selected ? 'selected' : ''}>${cat}</option>`
        ).join('');
    }
    
    
    function calculateFine(days_overdue) {
        const grace_period = 2;
        const daily_rate = 0.50;
        const max_fine_per_book = 25.00;
        
        if (days_overdue <= grace_period) {
            return 0;
        }
        
        const fine_days = days_overdue - grace_period;
        const fine = fine_days * daily_rate;
        
        return Math.min(fine, max_fine_per_book);
    }
    
    
    setTimeout(() => {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(msg => {
            if (msg.id !== 'successMessage' && msg.id !== 'errorMessage') {
                msg.remove();
            }
        });
    }, 5000);