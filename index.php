<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Humblekid Pre & Primary School - Quality Education</title>
    <link rel="stylesheet" href="style-landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Page Loader -->
    <div id="page-loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>HumbleKid</span>
            </div>
            
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul class="nav-menu" id="navMenu">
                <li class="nav-item">
                    <a href="#home" class="nav-link active" data-section="home">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#about" class="nav-link" data-section="about">
                        <i class="fas fa-info-circle"></i>
                        <span>About</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#features" class="nav-link" data-section="features">
                        <i class="fas fa-star"></i>
                        <span>Features</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#contact" class="nav-link" data-section="contact">
                        <i class="fas fa-envelope"></i>
                        <span>Contact</span>
                    </a>
                </li>
                <li class="nav-divider"></li>
                <?php if (!$user): ?>
                    <li class="nav-item">
                        <a href="login.php" class="nav-link btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="register.php" class="nav-link btn-register">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="sms.php" class="nav-link btn-dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link btn-logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                Welcome to <span class="highlight">HumbleKid</span> School
            </h1>
            <p class="hero-subtitle">
                Nurturing Young Minds with Excellence in Education
            </p>
            <div class="hero-cta">
                <?php if (!$user): ?>
                    <a href="register.php" class="btn btn-primary">
                        <span>Get Started</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#about" class="btn btn-secondary">
                        <span>Learn More</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                <?php else: ?>
                    <a href="sms.php" class="btn btn-primary">
                        <span>Go to Dashboard</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-animation">
            <div class="floating-card card-1">
                <i class="fas fa-book"></i>
                <p>Quality Education</p>
            </div>
            <div class="floating-card card-2">
                <i class="fas fa-users"></i>
                <p>Expert Teachers</p>
            </div>
            <div class="floating-card card-3">
                <i class="fas fa-trophy"></i>
                <p>Excellence</p>
            </div>
        </div>

        <div class="animated-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <h2 class="section-title">About Our School</h2>
            <p class="section-subtitle">Dedicated to Excellence Since Day One</p>

            <div class="about-grid">
                <div class="about-card">
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Academic Excellence</h3>
                    <p>We provide a comprehensive curriculum designed to develop critical thinking and creative problem-solving skills in our students.</p>
                </div>

                <div class="about-card">
                    <div class="card-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Holistic Development</h3>
                    <p>Our approach emphasizes character building, leadership qualities, and social responsibility alongside academic achievement.</p>
                </div>

                <div class="about-card">
                    <div class="card-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Modern Facilities</h3>
                    <p>State-of-the-art learning resources, laboratories, and digital infrastructure support our teaching-learning process.</p>
                </div>

                <div class="about-card">
                    <div class="card-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Parent Partnership</h3>
                    <p>We believe in strong parent-teacher collaboration to ensure every child reaches their full potential.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose HumbleKid?</h2>
            <p class="section-subtitle">Excellence in Every Aspect</p>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-number">01</div>
                    <h3>Experienced Faculty</h3>
                    <p>Our teachers are highly qualified and dedicated to nurturing each child's potential with care and expertise.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-number">02</div>
                    <h3>Safe Environment</h3>
                    <p>A secure, welcoming campus designed specifically for young learners' physical and emotional well-being.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-number">03</div>
                    <h3>Extracurricular Activities</h3>
                    <p>Sports, arts, music, and clubs help students explore their interests and develop well-rounded personalities.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-number">04</div>
                    <h3>Digital Learning</h3>
                    <p>Integrated technology in education to enhance learning outcomes and prepare students for the digital age.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-number">05</div>
                    <h3>Individual Attention</h3>
                    <p>Small class sizes ensure every student receives personalized guidance and academic support.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-number">06</div>
                    <h3>Progress Tracking</h3>
                    <p>Regular assessments and parent updates keep families informed about their child's academic journey.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-subtitle">We'd Love to Hear From You</p>

            <div class="contact-grid">
                <div class="contact-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Location</h3>
                    <p>HumbleKid School<br>123 Education Lane<br>Knowledge City</p>
                </div>

                <div class="contact-card">
                    <i class="fas fa-phone"></i>
                    <h3>Phone</h3>
                    <p>+254 (0) 123 456 789<br>+254 (0) 987 654 321</p>
                </div>

                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h3>Email</h3>
                    <p>info@humblekid.com<br>support@humblekid.com</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>HumbleKid School</h4>
                    <p>Committed to providing quality education and nurturing young minds for a brighter future.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 HumbleKid Pre & Primary School. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Navbar Toggle
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        const navLinks = document.querySelectorAll('.nav-link');

        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });

        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
            });
        });

        // Active Link Highlight
        window.addEventListener('scroll', () => {
            let current = '';
            const sections = document.querySelectorAll('section');
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= sectionTop - 200) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-section') === current) {
                    link.classList.add('active');
                }
            });
        });

        // Page Loader
        window.addEventListener('DOMContentLoaded', function () {
            const loader = document.getElementById('page-loader');
            if (loader) {
                setTimeout(function () {
                    loader.style.opacity = '0';
                    setTimeout(function () {
                        loader.style.display = 'none';
                    }, 300);
                }, 2000);
            }
        });

        // Scroll to Top Button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth Scroll for Hash Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>