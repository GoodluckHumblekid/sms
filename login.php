<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$errorType = '';
$messageText = '';
$registrationNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrationNumber = trim($_POST['registration_number'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($registrationNumber === '' || $password === '') {
        $error = 'Please enter both registration number and password.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE registration_number = :reg LIMIT 1');
            $stmt->execute([':reg' => $registrationNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $error = 'Credentials not found.';
                $errorType = 'not_found';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Credentials are incorrect.';
                $errorType = 'invalid_credentials';
            } else {
                $_SESSION['loggedIn'] = true;
                $_SESSION['loggedInUser'] = htmlspecialchars($registrationNumber, ENT_QUOTES, 'UTF-8');
                $_SESSION['userId'] = $user['id'];
                header('Location: sms.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Credentials are incorrect.';
            $errorType = 'invalid_credentials';
        }
    }
}

if (!$error && !empty($_GET['message'])) {
    $messageText = trim($_GET['message']);
    $error = $messageText;
    $errorType = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Humblekid Pre & Primary School - Login</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="style-login2.css?v=20260716">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Error Modal -->
    <div id="errorModal" class="error-modal">
        <div class="error-modal-content">
            <h3 id="errorModalTitle">Error</h3>
            <p id="errorModalMessage"></p>
            <button id="errorModalClose" class="modal-btn">OK</button>
        </div>
    </div>

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
                    <div class="login-badge">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student Portal</span>
                    </div>
                    <h2>Welcome Back</h2>
                    <p>Access your student portal safely</p>
                    <div class="login-highlights">
                        <span><i class="fas fa-shield-alt"></i> Secure</span>
                        <span><i class="fas fa-bolt"></i> Fast</span>
                        <span><i class="fas fa-smile"></i> Friendly</span>
                    </div>
                    <div class="header-accent"></div>
                </div>

                <?php if ($error): ?>
                    <div class="login-alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="login.php" method="post" class="login-form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <input 
                                type="text" 
                                name="registration_number" 
                                id="registration_number" 
                                placeholder=" "
                                value="<?php echo htmlspecialchars($registrationNumber, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                autocomplete="off"
                                autocapitalize="off"
                            >
                        </div>
                        <label for="registration_number" class="floating-label">Registration Number</label>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                placeholder=" "
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
                    <p>Forgot Password? <a href="recover.php" class="link-primary">Recover Account</a></p>
                    <p>Don't have an account? <a href="register.php" class="link-primary">Register Here</a></p>
                </div>
            </div>

            <!-- Decorative Animation -->
            <div class="login-decoration" aria-hidden="true">
                <div class="portal-card">
                    <div class="portal-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Welcome to your school portal</h3>
                    <p>Stay connected with classes, updates, and everything you need in one secure place.</p>
                    <div class="portal-badges">
                        <span><i class="fas fa-check-circle"></i> Easy access</span>
                        <span><i class="fas fa-shield-alt"></i> Safe & secure</span>
                    </div>
                </div>
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

        // Toggle Password
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

            // Show modal if server set an error
            var error = <?php echo json_encode($error); ?>;
            var errorType = <?php echo json_encode($errorType); ?>;
            if (error && errorType !== 'info') {
                var modal = document.getElementById('errorModal');
                var msg = document.getElementById('errorModalMessage');
                var title = document.getElementById('errorModalTitle');
                if (errorType === 'not_found') {
                    title.textContent = 'Credentials Not Found';
                    msg.textContent = 'Credentials not found. Please check your registration number.';
                } else if (errorType === 'invalid_credentials') {
                    title.textContent = 'Invalid Credentials';
                    msg.textContent = 'The credentials you entered are incorrect. Please try again.';
                } else {
                    title.textContent = 'Error';
                    msg.textContent = error;
                }
                modal.style.display = 'flex';
                document.getElementById('errorModalClose').addEventListener('click', function(){ modal.style.display = 'none'; });
            }

            // Input focus animations
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
                input.addEventListener('blur', function() {
                    formGroup.classList.remove('focused');
                    updateLabel();
                });
                input.addEventListener('change', updateLabel);
                updateLabel();
            });
        });
    </script>
</body>
</html>
