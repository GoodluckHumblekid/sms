<?php
session_start();
$user = $_SESSION['loggedInUser'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Account - Humblekid Pre & Primary School</title>
    <link rel="stylesheet" href="style-login2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Page Loader -->
    <div id="page-loader" class="page-loader">
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
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
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
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="login-main">
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <h2>Recover Account</h2>
                    <p>Get your registration details back</p>
                </div>

                <form action="recover.php" method="post" class="login-form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                placeholder="Enter your email"
                                required
                                autocomplete="email"
                            >
                        </div>
                        <label for="email" class="floating-label">Email Address</label>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input 
                                type="tel" 
                                name="phone" 
                                id="phone" 
                                placeholder="Enter your phone number"
                                required
                                autocomplete="tel"
                            >
                        </div>
                        <label for="phone" class="floating-label">Phone Number</label>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Recover Account</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <div class="login-links">
                    <p>Remember your details? <a href="login.php" class="link-primary">Login here</a></p>
                    <p>Don't have an account? <a href="register.php" class="link-primary">Register Now</a></p>
                </div>
            </div>

            <!-- Decorative Animation -->
            <div class="login-decoration">
                <div class="decoration-blob blob-1"></div>
                <div class="decoration-blob blob-2"></div>
                <div class="decoration-blob blob-3"></div>
            </div>
        </div>
    </main>

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

            // Input focus animations
            document.querySelectorAll('.form-group input').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>
