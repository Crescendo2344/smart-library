<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <i class="fas fa-book"></i>
                <span>Library<span class="highlight">Pro</span></span>
            </div>
            <ul class="nav-menu">
                <li><a href="#features">Features</a></li>
                <li><a href="#roles">Roles</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="views/auth/login.php" class="nav-btn">Login</a></li>
                <li><a href="views/auth/register.php" class="nav-btn secondary">Register</a></li>
            </ul>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Modern Library Management <span class="highlight">System</span></h1>
                <p class="subtitle">Streamline your library operations with our comprehensive management solution. Manage books, users, and borrowing processes efficiently.</p>
                <div class="hero-buttons">
                    <a href="views/auth/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </a>
                    <a href="views/auth/register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>1,000+</h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="fas fa-book"></i>
                        <div>
                            <h3>10,000+</h3>
                            <p>Books Catalog</p>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="fas fa-exchange-alt"></i>
                        <div>
                            <h3>500+</h3>
                            <p>Daily Transactions</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://cdn.pixabay.com/photo/2018/09/27/09/22/artificial-intelligence-3706562_1280.jpg" alt="Library Management System">
                <div class="image-overlay">
                    <div class="floating-card student">
                        <i class="fas fa-user-graduate"></i>
                        <p>Student Portal</p>
                    </div>
                    <div class="floating-card librarian">
                        <i class="fas fa-book-reader"></i>
                        <p>Librarian Tools</p>
                    </div>
                    <div class="floating-card staff">
                        <i class="fas fa-user-tie"></i>
                        <p>Staff Admin</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful <span class="highlight">Features</span></h2>
                <p>Everything you need to manage your library efficiently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Book Management</h3>
                    <p>Easy cataloging, tracking, and management of your entire book collection with real-time availability status.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3>User Approval System</h3>
                    <p>Secure registration with staff approval workflow to maintain control over user access and privileges.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>Borrowing System</h3>
                    <p>Streamlined borrowing and returning process with automatic due date calculation and reminders.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reports & Analytics</h3>
                    <p>Comprehensive reports on library usage, popular books, overdue items, and user statistics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notifications</h3>
                    <p>Automatic notifications for due dates, approvals, and system updates via multiple channels.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Role-Based Access</h3>
                    <p>Secure multi-role access control with customized dashboards for students, teachers, librarians, and staff.</p>
                </div>
            </div>
        </div>
    </section>

    
    <section id="roles" class="roles">
        <div class="container">
            <div class="section-header">
                <h2>Role-Based <span class="highlight">Dashboards</span></h2>
                <p>Customized interfaces for different user types</p>
            </div>
            <div class="roles-grid">
                <div class="role-card student-role">
                    <div class="role-header">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Student</h3>
                    </div>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Browse and borrow books</li>
                        <li><i class="fas fa-check"></i> View borrowing history</li>
                        <li><i class="fas fa-check"></i> Check due dates and fines</li>
                        <li><i class="fas fa-check"></i> Reserve unavailable books</li>
                    </ul>
                    
                </div>
                
                <div class="role-card teacher-role">
                    <div class="role-header">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3>Teacher</h3>
                    </div>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Extended borrowing period (30 days)</li>
                        <li><i class="fas fa-check"></i> Access to academic resources</li>
                        <li><i class="fas fa-check"></i> Request books for courses</li>
                        <li><i class="fas fa-check"></i> Priority reservations</li>
                    </ul>
                    
                </div>
                
                <div class="role-card librarian-role">
                    <div class="role-header">
                        <i class="fas fa-book-reader"></i>
                        <h3>Librarian</h3>
                    </div>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Manage book catalog</li>
                        <li><i class="fas fa-check"></i> Process borrow/return transactions</li>
                        <li><i class="fas fa-check"></i> Handle overdue items</li>
                        <li><i class="fas fa-check"></i> Generate reports</li>
                    </ul>
                    
                </div>
                
                <div class="role-card staff-role">
                    <div class="role-header">
                        <i class="fas fa-user-tie"></i>
                        <h3>Staff</h3>
                    </div>
                    <ul class="role-features">
                        <li><i class="fas fa-check"></i> Approve new user registrations</li>
                        <li><i class="fas fa-check"></i> Manage user accounts</li>
                        <li><i class="fas fa-check"></i> System configuration</li>
                        <li><i class="fas fa-check"></i> Full administrative access</li>
                    </ul>
                    <div class="role-footer">
                        <span class="role-note">Staff accounts require existing admin approval</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It <span class="highlight">Works</span></h2>
                <p>Get started with our library system in 4 simple steps</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Register Account</h3>
                        <p>Create your account by providing basic information and selecting your role (Student, Teacher, or Librarian).</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Wait for Approval</h3>
                        <p>Our staff team will review and approve your registration. You'll receive notification once approved.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Access Dashboard</h3>
                        <p>Login to your personalized dashboard based on your role with customized features and tools.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Start Using</h3>
                        <p>Borrow books, manage your account, and enjoy all the features of our modern library system.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your Library Experience?</h2>
                <p>Join thousands of users who have streamlined their library operations with our system.</p>
                <div class="cta-buttons">
                    <a href="views/auth/register.php" class="btn btn-primary">Get Started Now</a>
                    <a href="views/auth/login.php" class="btn btn-primary">Login to Your Account</a>
                </div>
                
            </div>
        </div>
    </section>

    
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand">
                        <i class="fas fa-book"></i>
                        <span>Library<span class="highlight">Pro</span></span>
                    </div>
                    <p>Modern library management system designed to streamline operations and enhance user experience.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="views/auth/login.php">Login</a></li>
                        <li><a href="views/auth/register.php">Register</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#roles">User Roles</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 234 M.J. Cuenco, Cebu City, Philippines</li>
                        <li><i class="fas fa-phone"></i> +63 (930) 567-8900</li>
                        <li><i class="fas fa-envelope"></i> support@librarypro.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="http://localhost/smart-library/assets/js/landing.js"></script>
</body>
</html>