// Landing Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });
    }
    
    // Close mobile menu when clicking a link
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            menuToggle.querySelector('i').classList.add('fa-bars');
            menuToggle.querySelector('i').classList.remove('fa-times');
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Animate elements on scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.feature-card, .role-card, .step');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;
            
            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial state for animation
    document.querySelectorAll('.feature-card, .role-card, .step').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    
    // Run animation on load and scroll
    window.addEventListener('load', animateOnScroll);
    window.addEventListener('scroll', animateOnScroll);
    
    // Counter animation for stats
    const stats = document.querySelectorAll('.stat h3');
    const animatedStats = new Set();
    
    const animateCounter = function() {
        stats.forEach(stat => {
            const statPosition = stat.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;
            
            if (statPosition < screenPosition && !animatedStats.has(stat)) {
                const target = parseInt(stat.textContent);
                const increment = target / 100;
                let current = 0;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.ceil(current) + '+';
                }, 20);
                
                animatedStats.add(stat);
            }
        });
    };
    
    window.addEventListener('scroll', animateCounter);
    
    // Role-based registration links
    const roleButtons = document.querySelectorAll('.role-btn');
    roleButtons.forEach(button => {
        if (button.href.includes('register.php')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const role = this.textContent.includes('Student') ? 'student' :
                            this.textContent.includes('Teacher') ? 'teacher' :
                            this.textContent.includes('Librarian') ? 'librarian' : '';
                
                if (role) {
                    localStorage.setItem('preferredRole', role);
                    window.location.href = 'register.php';
                }
            });
        }
    });
    
    // Parallax effect for hero image
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const heroImage = document.querySelector('.hero-image img');
        
        if (heroImage) {
            heroImage.style.transform = `perspective(1000px) rotateY(-10deg) translateY(${scrolled * 0.05}px)`;
        }
    });
    
    // Newsletter form (placeholder)
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // In a real application, you would send this to your server
            alert(`Thank you for subscribing with ${email}! You'll hear from us soon.`);
            this.reset();
        });
    }
    
    // Demo video modal
    const demoButtons = document.querySelectorAll('[href="#features"]');
    demoButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.textContent.includes('Watch Demo') || this.querySelector('.fa-play-circle')) {
                e.preventDefault();
                alert('Demo video would play here. In a real implementation, this would open a modal with a video player.');
            }
        });
    });
});