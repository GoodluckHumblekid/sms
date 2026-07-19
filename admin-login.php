<?php
session_start();
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Humblekid School</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="style-login2.css?v=20260716">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(255, 90, 31, 0.16), rgba(79, 124, 255, 0.16));
            color: var(--primary-color);
            font-size: 0.86rem;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .login-box.admin-login-box {
            min-height: 540px;
            padding: 44px 38px;
            background: linear-gradient(135deg, rgba(255,255,255,0.99), rgba(255,245,238,0.95));
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.14);
            border-radius: 30px;
            position: relative;
            overflow: hidden;
        }

        .login-box.admin-login-box .login-header h2 {
            font-size: 1.9rem;
            margin-top: 4px;
        }

        .login-box.admin-login-box .login-header p {
            margin-top: 8px;
            color: #6b7a90;
        }

        .admin-highlight {
            position: relative;
            z-index: 2;
            padding: 28px;
            border-radius: 28px;
            background: linear-gradient(135deg, #ff5a1f 0%, #ff7a3d 35%, #4f7cff 100%);
            color: white;
            box-shadow: 0 24px 70px rgba(79, 124, 255, 0.22);
            max-width: 340px;
            animation: floatCard 4s ease-in-out infinite;
            overflow: hidden;
        }

        .admin-highlight::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255,255,255,0.2), transparent 40%, rgba(255,255,255,0.12));
            pointer-events: none;
        }

        .header-accent {
            width: 90px;
            height: 5px;
            margin: 14px auto 0;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-blue), var(--accent-pink));
            animation: pulseLine 2.2s ease-in-out infinite;
        }

        .admin-highlight .highlight-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            font-size: 1.3rem;
            margin-bottom: 14px;
        }

        .admin-highlight h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .admin-highlight p {
            line-height: 1.6;
            opacity: 0.95;
        }

        @media (max-width: 900px) {
            .login-box.admin-login-box {
                min-height: auto;
            }

            .admin-highlight {
                margin-top: 20px;
                max-width: 100%;
            }
        }

        @keyframes pulseLine {
            0%, 100% { opacity: 0.7; transform: scaleX(0.9); }
            50% { opacity: 1; transform: scaleX(1.05); }
        }
    </style>
</head>
<body>
    <div id="errorModal" class="error-modal">
        <div class="error-modal-content">
            <h3 id="errorModalTitle">Error</h3>
            <p id="errorModalMessage"></p>
            <button id="errorModalClose" class="modal-btn">OK</button>
        </div>
    </div>

    <div id="page-loader" class="page-loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

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
                    <a href="index.php#about" class="nav-link">
                        <i class="fas fa-info-circle"></i>
                        <span>About</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php#contact" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Contact</span>
                    </a>
                </li>
                <li class="nav-divider"></li>
                <li class="nav-item">
                    <a href="login.php" class="nav-link btn-register">
                        <i class="fas fa-user"></i>
                        <span>Student Login</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="login-main">
        <div class="login-container">
            <div class="login-box admin-login-box">
                <div class="login-header">
                    <div class="admin-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin Portal</span>
                    </div>
                    <h2>Welcome Back</h2>
                    <p>Access the school dashboard safely</p>
                    <div class="header-accent"></div>
                </div>

                <?php if ($error): ?>
                    <div class="login-alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="admin.php" method="post" class="login-form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                placeholder="Admin email address"
                                required
                                autocomplete="email"
                            >
                        </div>
                        <label for="email" class="floating-label">Email Address</label>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Enter your password"
                                minlength="8"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <label for="password" class="floating-label">Password</label>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="recover.php" class="link-primary">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="login-links">
                    <p>Need help? <a href="index.php" class="link-primary">Contact Support</a></p>
                </div>
            </div>

            <div class="login-decoration" aria-hidden="true">
                <div class="portal-card">
                    <div class="portal-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Manage everything in one place</h3>
                    <p>Students, staff, classes, and reports are all available from a single secure dashboard.</p>
                    <div class="portal-badges">
                        <span><i class="fas fa-check-circle"></i> Secure</span>
                        <span><i class="fas fa-bolt"></i> Fast</span>
                    </div>
                </div>
                <div class="decoration-blob blob-1"></div>
                <div class="decoration-blob blob-2"></div>
                <div class="decoration-blob blob-3"></div>
            </div>
        </div>
    </main>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        const navLinks = document.querySelectorAll('.nav-link');

        if (navToggle && navMenu) {
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
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

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

            document.querySelectorAll('.form-group input').forEach(input => {
                const formGroup = input.closest('.form-group');

                const updateLabel = () => {
                    const hasValue = input.value.trim().length > 0;
                    input.classList.toggle('has-value', hasValue);
                    formGroup.classList.toggle('has-value', hasValue);
                    formGroup.classList.toggle('focused', document.activeElement === input);
                };

                input.addEventListener('input', updateLabel);
                input.addEventListener('focus', updateLabel);
                input.addEventListener('blur', function () {
                    formGroup.classList.remove('focused');
                    updateLabel();
                });
                input.addEventListener('change', updateLabel);
                updateLabel();
            });

            var error = <?php echo json_encode($error); ?>;
            if (error) {
                var modal = document.getElementById('errorModal');
                var msg = document.getElementById('errorModalMessage');
                var title = document.getElementById('errorModalTitle');
                title.textContent = 'Login Error';
                msg.textContent = error;
                modal.style.display = 'flex';
                document.getElementById('errorModalClose').addEventListener('click', function () {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>
