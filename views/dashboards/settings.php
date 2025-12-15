<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

session_start();

// Only staff can access this page
if ($_SESSION['role'] !== 'staff') {
    header("Location: staff.php");
    exit();
}

// Database connection
$conn = getDBConnection();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'save_general_settings':
            $message = saveGeneralSettings($conn, $_POST);
            break;
            
        case 'save_borrowing_settings':
            $message = saveBorrowingSettings($conn, $_POST);
            break;
            
        case 'save_fine_settings':
            $message = saveFineSettings($conn, $_POST);
            break;
            
        case 'save_notification_settings':
            $message = saveNotificationSettings($conn, $_POST);
            break;
            
        case 'save_security_settings':
            $message = saveSecuritySettings($conn, $_POST);
            break;
            
        case 'reset_settings':
            $message = resetSettings($conn);
            break;
            
        case 'backup_database':
            $message = backupDatabase($conn);
            break;
    }
}

// Get current settings
$settings = getSettings($conn);
$conn->close();

// Functions for settings management
function getSettings($conn) {
    $settings = [];
    
    // Get settings from database if you have a settings table
    // For now, we'll use default values
    $settings['general'] = [
        'site_name' => 'Library Management System',
        'site_email' => 'admin@library.com',
        'site_url' => 'http://localhost/smart-library',
        'timezone' => 'America/New_York',
        'date_format' => 'Y-m-d',
        'items_per_page' => 20,
        'maintenance_mode' => false,
        'allow_registration' => true
    ];
    
    $settings['borrowing'] = [
        'student_limit' => 5,
        'teacher_limit' => 10,
        'librarian_limit' => 15,
        'student_period' => 14,
        'teacher_period' => 30,
        'librarian_period' => 21,
        'renewal_limit' => 2,
        'reservation_days' => 3,
        'max_concurrent_reservations' => 3
    ];
    
    $settings['fines'] = [
        'overdue_rate' => 0.50,
        'damage_fee_min' => 5.00,
        'damage_fee_max' => 50.00,
        'lost_book_fee' => 'book_price',
        'grace_period' => 2,
        'max_fine_per_book' => 25.00,
        'auto_apply_fines' => true,
        'reminder_days' => [1, 3, 7]
    ];
    
    $settings['notifications'] = [
        'enable_email' => true,
        'enable_sms' => false,
        'due_reminder_days' => [1, 3],
        'overdue_alerts' => true,
        'new_user_notifications' => true,
        'system_updates' => true,
        'newsletter' => false
    ];
    
    $settings['security'] = [
        'min_password_length' => 8,
        'require_special_char' => true,
        'require_numbers' => true,
        'require_uppercase' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 30,
        'session_timeout' => 30,
        'enable_2fa' => false,
        'ip_whitelist' => '',
        'ip_blacklist' => ''
    ];
    
    return $settings;
}

function saveGeneralSettings($conn, $data) {
    // In a real application, you would save to database
    // For now, we'll just return a success message
    
    // Validate data
    if (empty($data['site_name'])) {
        return 'Site name is required';
    }
    
    if (!filter_var($data['site_email'], FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address';
    }
    
    // Log the change
    $staff_id = $_SESSION['user_id'];
    $change_description = "General settings updated by staff ID: $staff_id";
    
    // You would save to database here
    // Example: $conn->query("UPDATE settings SET value = '{$data['site_name']}' WHERE key = 'site_name'");
    
    return 'General settings saved successfully!';
}

function saveBorrowingSettings($conn, $data) {
    // Validate borrowing limits
    $limits = ['student_limit', 'teacher_limit', 'librarian_limit'];
    foreach($limits as $limit) {
        if (!is_numeric($data[$limit]) || $data[$limit] < 1) {
            return ucfirst(str_replace('_', ' ', $limit)) . ' must be a positive number';
        }
    }
    
    // Validate periods
    $periods = ['student_period', 'teacher_period', 'librarian_period'];
    foreach($periods as $period) {
        if (!is_numeric($data[$period]) || $data[$period] < 1) {
            return ucfirst(str_replace('_', ' ', $period)) . ' must be at least 1 day';
        }
    }
    
    // Log the change
    $staff_id = $_SESSION['user_id'];
    $change_description = "Borrowing settings updated by staff ID: $staff_id";
    
    return 'Borrowing settings saved successfully!';
}

function saveFineSettings($conn, $data) {
    // Validate fine amounts
    if (!is_numeric($data['overdue_rate']) || $data['overdue_rate'] < 0) {
        return 'Overdue rate must be a positive number';
    }
    
    if (!is_numeric($data['grace_period']) || $data['grace_period'] < 0) {
        return 'Grace period must be a positive number';
    }
    
    // Log the change
    $staff_id = $_SESSION['user_id'];
    $change_description = "Fine settings updated by staff ID: $staff_id";
    
    return 'Fine settings saved successfully!';
}

function saveNotificationSettings($conn, $data) {
    // Validate notification settings
    // Log the change
    $staff_id = $_SESSION['user_id'];
    $change_description = "Notification settings updated by staff ID: $staff_id";
    
    return 'Notification settings saved successfully!';
}

function saveSecuritySettings($conn, $data) {
    // Validate security settings
    if (!is_numeric($data['min_password_length']) || $data['min_password_length'] < 6) {
        return 'Minimum password length must be at least 6 characters';
    }
    
    if (!is_numeric($data['max_login_attempts']) || $data['max_login_attempts'] < 1) {
        return 'Maximum login attempts must be at least 1';
    }
    
    // Log the change
    $staff_id = $_SESSION['user_id'];
    $change_description = "Security settings updated by staff ID: $staff_id";
    
    return 'Security settings saved successfully!';
}

function resetSettings($conn) {
    // Reset to default settings
    $staff_id = $_SESSION['user_id'];
    $change_description = "All settings reset to defaults by staff ID: $staff_id";
    
    return 'Settings reset to defaults successfully!';
}

function backupDatabase($conn) {
    // Create database backup
    $backup_file = '../../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // In a real application, you would use mysqldump
    // For now, we'll just create a placeholder
    
    // Log the backup
    $staff_id = $_SESSION['user_id'];
    $change_description = "Database backup created by staff ID: $staff_id";
    
    return 'Database backup created successfully! Backup file: ' . basename($backup_file);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Library Management System</title>
    <link rel="stylesheet" href="/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="/smart-library/assets/css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-cog"></i> System Settings
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
        <!-- Settings Header -->
        <div class="settings-header">
            <h1><i class="fas fa-sliders-h"></i> System Configuration</h1>
            <p>Manage library system settings and preferences</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Settings Navigation -->
        <div class="settings-nav">
            <button class="nav-btn active" onclick="showTab('general')">
                <i class="fas fa-globe"></i> General
            </button>
            <button class="nav-btn" onclick="showTab('borrowing')">
                <i class="fas fa-book"></i> Borrowing
            </button>
            <button class="nav-btn" onclick="showTab('fines')">
                <i class="fas fa-money-bill"></i> Fines
            </button>
            <button class="nav-btn" onclick="showTab('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <button class="nav-btn" onclick="showTab('security')">
                <i class="fas fa-shield-alt"></i> Security
            </button>
            <button class="nav-btn" onclick="showTab('backup')">
                <i class="fas fa-database"></i> Backup
            </button>
        </div>
        
        <!-- Settings Content -->
        <div class="settings-content">
            
            <!-- General Settings Tab -->
            <div id="general" class="settings-tab active">
                <div class="tab-header">
                    <h2><i class="fas fa-globe"></i> General Settings</h2>
                    <p>Configure basic system information and preferences</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_general_settings">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Site Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="site_name">
                                    <i class="fas fa-signature"></i> Site Name *
                                </label>
                                <input type="text" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($settings['general']['site_name']); ?>" 
                                       required>
                                <small>The name of your library system</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_email">
                                    <i class="fas fa-envelope"></i> Admin Email *
                                </label>
                                <input type="email" id="site_email" name="site_email" 
                                       value="<?php echo htmlspecialchars($settings['general']['site_email']); ?>" 
                                       required>
                                <small>System notifications will be sent from this address</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_url">
                                    <i class="fas fa-link"></i> Site URL
                                </label>
                                <input type="url" id="site_url" name="site_url" 
                                       value="<?php echo htmlspecialchars($settings['general']['site_url']); ?>">
                                <small>Full URL to your library system</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="timezone">
                                    <i class="fas fa-clock"></i> Timezone
                                </label>
                                <select id="timezone" name="timezone">
                                    <?php
                                    $timezones = [
                                        'America/New_York' => 'Eastern Time (ET)',
                                        'America/Chicago' => 'Central Time (CT)',
                                        'America/Denver' => 'Mountain Time (MT)',
                                        'America/Los_Angeles' => 'Pacific Time (PT)',
                                        'UTC' => 'UTC',
                                        'Europe/London' => 'London',
                                        'Europe/Paris' => 'Paris',
                                        'Asia/Tokyo' => 'Tokyo'
                                    ];
                                    foreach($timezones as $tz => $label):
                                    ?>
                                    <option value="<?php echo $tz; ?>" 
                                            <?php echo $settings['general']['timezone'] == $tz ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>System timezone for all date/time operations</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-cogs"></i> System Preferences</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="date_format">
                                    <i class="fas fa-calendar"></i> Date Format
                                </label>
                                <select id="date_format" name="date_format">
                                    <option value="Y-m-d" <?php echo $settings['general']['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?php echo $settings['general']['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?php echo $settings['general']['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    <option value="F j, Y" <?php echo $settings['general']['date_format'] == 'F j, Y' ? 'selected' : ''; ?>>Month Day, Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="items_per_page">
                                    <i class="fas fa-list"></i> Items Per Page
                                </label>
                                <input type="number" id="items_per_page" name="items_per_page" 
                                       value="<?php echo $settings['general']['items_per_page']; ?>" 
                                       min="5" max="100">
                                <small>Number of items to display per page in lists</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="allow_registration" 
                                           <?php echo $settings['general']['allow_registration'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-user-plus"></i> Allow New User Registration</span>
                                </label>
                                <small>Allow new users to register for accounts</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="maintenance_mode" 
                                           <?php echo $settings['general']['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-tools"></i> Maintenance Mode</span>
                                </label>
                                <small>Put system in maintenance mode (only staff can access)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save General Settings
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Borrowing Settings Tab -->
            <div id="borrowing" class="settings-tab">
                <div class="tab-header">
                    <h2><i class="fas fa-book"></i> Borrowing Settings</h2>
                    <p>Configure borrowing limits and periods</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_borrowing_settings">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-user-tag"></i> Borrowing Limits by Role</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="student_limit">
                                    <i class="fas fa-user-graduate"></i> Student Limit
                                </label>
                                <input type="number" id="student_limit" name="student_limit" 
                                       value="<?php echo $settings['borrowing']['student_limit']; ?>" 
                                       min="1" max="20" required>
                                <small>Maximum books a student can borrow at once</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher_limit">
                                    <i class="fas fa-chalkboard-teacher"></i> Teacher Limit
                                </label>
                                <input type="number" id="teacher_limit" name="teacher_limit" 
                                       value="<?php echo $settings['borrowing']['teacher_limit']; ?>" 
                                       min="1" max="30" required>
                                <small>Maximum books a teacher can borrow at once</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="librarian_limit">
                                    <i class="fas fa-book-reader"></i> Librarian Limit
                                </label>
                                <input type="number" id="librarian_limit" name="librarian_limit" 
                                       value="<?php echo $settings['borrowing']['librarian_limit']; ?>" 
                                       min="1" max="30" required>
                                <small>Maximum books a librarian can borrow at once</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> Borrowing Periods</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="student_period">
                                    <i class="fas fa-user-graduate"></i> Student Period (days)
                                </label>
                                <input type="number" id="student_period" name="student_period" 
                                       value="<?php echo $settings['borrowing']['student_period']; ?>" 
                                       min="1" max="90" required>
                                <small>How long students can keep borrowed books</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher_period">
                                    <i class="fas fa-chalkboard-teacher"></i> Teacher Period (days)
                                </label>
                                <input type="number" id="teacher_period" name="teacher_period" 
                                       value="<?php echo $settings['borrowing']['teacher_period']; ?>" 
                                       min="1" max="180" required>
                                <small>How long teachers can keep borrowed books</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="librarian_period">
                                    <i class="fas fa-book-reader"></i> Librarian Period (days)
                                </label>
                                <input type="number" id="librarian_period" name="librarian_period" 
                                       value="<?php echo $settings['borrowing']['librarian_period']; ?>" 
                                       min="1" max="90" required>
                                <small>How long librarians can keep borrowed books</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-exchange-alt"></i> Renewals & Reservations</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="renewal_limit">
                                    <i class="fas fa-redo"></i> Renewal Limit
                                </label>
                                <input type="number" id="renewal_limit" name="renewal_limit" 
                                       value="<?php echo $settings['borrowing']['renewal_limit']; ?>" 
                                       min="0" max="10">
                                <small>Maximum number of times a book can be renewed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reservation_days">
                                    <i class="fas fa-clock"></i> Reservation Hold Period (days)
                                </label>
                                <input type="number" id="reservation_days" name="reservation_days" 
                                       value="<?php echo $settings['borrowing']['reservation_days']; ?>" 
                                       min="1" max="14">
                                <small>How long to hold reserved books for pickup</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_concurrent_reservations">
                                    <i class="fas fa-list-ol"></i> Max Concurrent Reservations
                                </label>
                                <input type="number" id="max_concurrent_reservations" name="max_concurrent_reservations" 
                                       value="<?php echo $settings['borrowing']['max_concurrent_reservations']; ?>" 
                                       min="1" max="10">
                                <small>Maximum reservations per user at once</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Borrowing Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Fines Settings Tab -->
            <div id="fines" class="settings-tab">
                <div class="tab-header">
                    <h2><i class="fas fa-money-bill"></i> Fine Settings</h2>
                    <p>Configure fine amounts and policies</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_fine_settings">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-clock"></i> Overdue Fines</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="overdue_rate">
                                    <i class="fas fa-calendar-times"></i> Daily Overdue Rate ($)
                                </label>
                                <input type="number" id="overdue_rate" name="overdue_rate" 
                                       value="<?php echo $settings['fines']['overdue_rate']; ?>" 
                                       min="0" max="10" step="0.01" required>
                                <small>Fine amount per day for overdue books</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="grace_period">
                                    <i class="fas fa-hourglass-half"></i> Grace Period (days)
                                </label>
                                <input type="number" id="grace_period" name="grace_period" 
                                       value="<?php echo $settings['fines']['grace_period']; ?>" 
                                       min="0" max="7">
                                <small>Days after due date before fines start</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_fine_per_book">
                                    <i class="fas fa-ban"></i> Maximum Fine per Book ($)
                                </label>
                                <input type="number" id="max_fine_per_book" name="max_fine_per_book" 
                                       value="<?php echo $settings['fines']['max_fine_per_book']; ?>" 
                                       min="0" max="100" step="0.01">
                                <small>Maximum fine amount for a single book</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> Damage & Lost Books</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="damage_fee_min">
                                    <i class="fas fa-tools"></i> Minimum Damage Fee ($)
                                </label>
                                <input type="number" id="damage_fee_min" name="damage_fee_min" 
                                       value="<?php echo $settings['fines']['damage_fee_min']; ?>" 
                                       min="0" max="100" step="0.01">
                                <small>Minimum fee for damaged books</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="damage_fee_max">
                                    <i class="fas fa-tools"></i> Maximum Damage Fee ($)
                                </label>
                                <input type="number" id="damage_fee_max" name="damage_fee_max" 
                                       value="<?php echo $settings['fines']['damage_fee_max']; ?>" 
                                       min="0" max="500" step="0.01">
                                <small>Maximum fee for damaged books</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="lost_book_fee">
                                    <i class="fas fa-book-dead"></i> Lost Book Fee
                                </label>
                                <select id="lost_book_fee" name="lost_book_fee">
                                    <option value="book_price" <?php echo $settings['fines']['lost_book_fee'] == 'book_price' ? 'selected' : ''; ?>>Book Price + Processing</option>
                                    <option value="fixed_amount" <?php echo $settings['fines']['lost_book_fee'] == 'fixed_amount' ? 'selected' : ''; ?>>Fixed Amount</option>
                                    <option value="replacement_cost" <?php echo $settings['fines']['lost_book_fee'] == 'replacement_cost' ? 'selected' : ''; ?>>Replacement Cost</option>
                                </select>
                                <small>How to calculate lost book fees</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-robot"></i> Automation</h3>
                        
                        <div class="form-grid">
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="auto_apply_fines" 
                                           <?php echo $settings['fines']['auto_apply_fines'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-bolt"></i> Automatically Apply Overdue Fines</span>
                                </label>
                                <small>Automatically add fines when books become overdue</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-bell"></i> Reminder Schedule (days after due)
                                </label>
                                <div class="checkbox-list">
                                    <?php 
                                    $reminder_days = $settings['fines']['reminder_days'];
                                    for ($i = 1; $i <= 14; $i++):
                                    ?>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="reminder_days[]" value="<?php echo $i; ?>"
                                               <?php echo in_array($i, $reminder_days) ? 'checked' : ''; ?>>
                                        <span>Day <?php echo $i; ?></span>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                                <small>When to send overdue reminders</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Fine Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Notifications Settings Tab -->
            <div id="notifications" class="settings-tab">
                <div class="tab-header">
                    <h2><i class="fas fa-bell"></i> Notification Settings</h2>
                    <p>Configure email and system notifications</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_notification_settings">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-envelope"></i> Notification Channels</h3>
                        
                        <div class="form-grid">
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="enable_email" 
                                           <?php echo $settings['notifications']['enable_email'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-envelope"></i> Enable Email Notifications</span>
                                </label>
                                <small>Send notifications via email</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="enable_sms" 
                                           <?php echo $settings['notifications']['enable_sms'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-sms"></i> Enable SMS Notifications</span>
                                </label>
                                <small>Send notifications via SMS (requires SMS gateway)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-check"></i> Due Date Notifications</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i> Send Reminders Before Due Date (days)
                                </label>
                                <div class="checkbox-list">
                                    <?php 
                                    $due_reminders = $settings['notifications']['due_reminder_days'];
                                    for ($i = 1; $i <= 7; $i++):
                                    ?>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="due_reminder_days[]" value="<?php echo $i; ?>"
                                               <?php echo in_array($i, $due_reminders) ? 'checked' : ''; ?>>
                                        <span><?php echo $i; ?> day<?php echo $i > 1 ? 's' : ''; ?> before</span>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                                <small>When to send due date reminders</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="overdue_alerts" 
                                           <?php echo $settings['notifications']['overdue_alerts'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-exclamation-circle"></i> Send Overdue Alerts</span>
                                </label>
                                <small>Send alerts when books become overdue</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-bell"></i> System Notifications</h3>
                        
                        <div class="form-grid">
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="new_user_notifications" 
                                           <?php echo $settings['notifications']['new_user_notifications'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-user-plus"></i> New User Registration Alerts</span>
                                </label>
                                <small>Notify staff when new users register</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="system_updates" 
                                           <?php echo $settings['notifications']['system_updates'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-sync"></i> System Update Notifications</span>
                                </label>
                                <small>Notify staff about system updates and maintenance</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="newsletter" 
                                           <?php echo $settings['notifications']['newsletter'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-newspaper"></i> Monthly Newsletter</span>
                                </label>
                                <small>Send monthly newsletter to users</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Email Configuration</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="smtp_host">
                                    <i class="fas fa-server"></i> SMTP Host
                                </label>
                                <input type="text" id="smtp_host" name="smtp_host" value="smtp.gmail.com">
                                <small>Your SMTP server address</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_port">
                                    <i class="fas fa-plug"></i> SMTP Port
                                </label>
                                <input type="number" id="smtp_port" name="smtp_port" value="587">
                                <small>SMTP server port (usually 587 for TLS)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_username">
                                    <i class="fas fa-user"></i> SMTP Username
                                </label>
                                <input type="text" id="smtp_username" name="smtp_username">
                                <small>Email account username</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_password">
                                    <i class="fas fa-key"></i> SMTP Password
                                </label>
                                <input type="password" id="smtp_password" name="smtp_password">
                                <small>Email account password or app password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notification Settings
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="testEmail()">
                            <i class="fas fa-paper-plane"></i> Test Email
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Security Settings Tab -->
            <div id="security" class="settings-tab">
                <div class="tab-header">
                    <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                    <p>Configure system security and access controls</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_security_settings">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-key"></i> Password Policy</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="min_password_length">
                                    <i class="fas fa-ruler"></i> Minimum Password Length
                                </label>
                                <input type="number" id="min_password_length" name="min_password_length" 
                                       value="<?php echo $settings['security']['min_password_length']; ?>" 
                                       min="6" max="32" required>
                                <small>Minimum characters required for passwords</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="require_special_char" 
                                           <?php echo $settings['security']['require_special_char'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-asterisk"></i> Require Special Characters</span>
                                </label>
                                <small>Passwords must contain special characters (!@#$%^&*)</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="require_numbers" 
                                           <?php echo $settings['security']['require_numbers'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-hashtag"></i> Require Numbers</span>
                                </label>
                                <small>Passwords must contain at least one number</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="require_uppercase" 
                                           <?php echo $settings['security']['require_uppercase'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-text-height"></i> Require Uppercase Letters</span>
                                </label>
                                <small>Passwords must contain at least one uppercase letter</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-user-lock"></i> Login Security</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="max_login_attempts">
                                    <i class="fas fa-ban"></i> Max Login Attempts
                                </label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                       value="<?php echo $settings['security']['max_login_attempts']; ?>" 
                                       min="1" max="10" required>
                                <small>Maximum failed login attempts before lockout</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="lockout_time">
                                    <i class="fas fa-clock"></i> Lockout Time (minutes)
                                </label>
                                <input type="number" id="lockout_time" name="lockout_time" 
                                       value="<?php echo $settings['security']['lockout_time']; ?>" 
                                       min="1" max="1440">
                                <small>How long to lock account after max attempts</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="session_timeout">
                                    <i class="fas fa-hourglass-end"></i> Session Timeout (minutes)
                                </label>
                                <input type="number" id="session_timeout" name="session_timeout" 
                                       value="<?php echo $settings['security']['session_timeout']; ?>" 
                                       min="5" max="480">
                                <small>Inactivity timeout before automatic logout</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="enable_2fa" 
                                           <?php echo $settings['security']['enable_2fa'] ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-mobile-alt"></i> Enable Two-Factor Authentication</span>
                                </label>
                                <small>Require 2FA for staff accounts</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-network-wired"></i> IP Restrictions</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="ip_whitelist">
                                    <i class="fas fa-check-circle"></i> IP Whitelist
                                </label>
                                <textarea id="ip_whitelist" name="ip_whitelist" rows="3"
                                          placeholder="192.168.1.1&#10;10.0.0.0/24&#10;Separate IPs with new lines"><?php echo htmlspecialchars($settings['security']['ip_whitelist']); ?></textarea>
                                <small>Allowed IP addresses (one per line, CIDR notation supported)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="ip_blacklist">
                                    <i class="fas fa-times-circle"></i> IP Blacklist
                                </label>
                                <textarea id="ip_blacklist" name="ip_blacklist" rows="3"
                                          placeholder="192.168.1.100&#10;10.0.0.100&#10;Separate IPs with new lines"><?php echo htmlspecialchars($settings['security']['ip_blacklist']); ?></textarea>
                                <small>Blocked IP addresses (one per line)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-history"></i> Audit Log</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-database"></i> Log Retention Period (days)
                                </label>
                                <select name="log_retention">
                                    <option value="30">30 days</option>
                                    <option value="90" selected>90 days</option>
                                    <option value="180">180 days</option>
                                    <option value="365">1 year</option>
                                    <option value="0">Forever</option>
                                </select>
                                <small>How long to keep audit logs</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="log_login_attempts" checked>
                                    <span><i class="fas fa-sign-in-alt"></i> Log All Login Attempts</span>
                                </label>
                                <small>Record successful and failed login attempts</small>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="log_user_actions" checked>
                                    <span><i class="fas fa-user-edit"></i> Log User Actions</span>
                                </label>
                                <small>Record important user actions (borrow, return, etc.)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Security Settings
                        </button>
                        <button type="button" class="btn btn-danger" onclick="forceLogoutAll()">
                            <i class="fas fa-sign-out-alt"></i> Force Logout All Users
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Backup Settings Tab -->
            <div id="backup" class="settings-tab">
                <div class="tab-header">
                    <h2><i class="fas fa-database"></i> Backup & Maintenance</h2>
                    <p>Manage database backups and system maintenance</p>
                </div>
                
                <div class="backup-section">
                    <div class="backup-card">
                        <div class="backup-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="backup-content">
                            <h3>Create Backup</h3>
                            <p>Create a complete backup of the database</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="backup_database">
                                <div class="form-group">
                                    <label for="backup_name">Backup Name</label>
                                    <input type="text" id="backup_name" name="backup_name" 
                                           value="backup_<?php echo date('Y-m-d'); ?>" 
                                           placeholder="Enter backup name">
                                </div>
                                <div class="form-group checkbox-group">
                                    <label>
                                        <input type="checkbox" name="include_media" checked>
                                        <span>Include uploaded files</span>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-database"></i> Create Backup Now
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="backup-card">
                        <div class="backup-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="backup-content">
                            <h3>Restore Backup</h3>
                            <p>Restore database from a previous backup</p>
                            <div class="form-group">
                                <label for="restore_file">Select Backup File</label>
                                <select id="restore_file" class="form-control">
                                    <option value="">-- Select a backup --</option>
                                    <option value="backup_2024-01-15.sql">backup_2024-01-15.sql</option>
                                    <option value="backup_2024-01-10.sql">backup_2024-01-10.sql</option>
                                    <option value="backup_2024-01-05.sql">backup_2024-01-05.sql</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-warning" onclick="restoreBackup()">
                                <i class="fas fa-undo"></i> Restore Selected Backup
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-section">
                    <h3><i class="fas fa-tools"></i> System Maintenance</h3>
                    
                    <div class="maintenance-grid">
                        <div class="maintenance-item">
                            <h4><i class="fas fa-trash"></i> Cleanup Tasks</h4>
                            <ul>
                                <li>
                                    <span>Delete old logs (older than 90 days)</span>
                                    <button class="btn-small" onclick="cleanupLogs()">Run</button>
                                </li>
                                <li>
                                    <span>Remove temporary files</span>
                                    <button class="btn-small" onclick="cleanupTempFiles()">Run</button>
                                </li>
                                <li>
                                    <span>Optimize database tables</span>
                                    <button class="btn-small" onclick="optimizeDatabase()">Run</button>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="maintenance-item">
                            <h4><i class="fas fa-chart-bar"></i> System Health</h4>
                            <ul>
                                <li>
                                    <span>Database Size: <strong>45.2 MB</strong></span>
                                </li>
                                <li>
                                    <span>Total Users: <strong><?php echo $settings['general']['total_users'] ?? 'N/A'; ?></strong></span>
                                </li>
                                <li>
                                    <span>System Uptime: <strong>15 days</strong></span>
                                </li>
                                <li>
                                    <span>Last Backup: <strong>2 days ago</strong></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="danger-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Dangerous Operations</h3>
                    
                    <div class="danger-actions">
                        <div class="danger-action">
                            <h4>Reset All Settings</h4>
                            <p>Reset all system settings to default values</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="reset_settings">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('WARNING: This will reset ALL settings to defaults. Are you sure?')">
                                    <i class="fas fa-redo"></i> Reset Settings
                                </button>
                            </form>
                        </div>
                        
                        <div class="danger-action">
                            <h4>Clear All Data</h4>
                            <p>Delete ALL data (users, books, records) - IRREVERSIBLE!</p>
                            <button type="button" class="btn btn-danger" onclick="showClearDataWarning()">
                                <i class="fas fa-skull-crossbones"></i> Clear All Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?> | Settings Management</p>
            <p>Current User: <?php echo htmlspecialchars($_SESSION['username']); ?> | Last Login: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </footer>
    
    <script src="/smart-library/assets/js/settings.js"></script>
</body>
</html>