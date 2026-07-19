<?php
session_start();
require_once __DIR__ . '/db.php';

function uploadFile(array $file, string $uploadDir)
{
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['error' => 'Allowed file types: PDF, JPG, JPEG, PNG.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Each file must be 5MB or smaller.'];
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['error' => 'Unable to create upload directory.'];
    }

    $safeName = uniqid('upload_', true) . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($file['name']));
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['error' => 'Failed to move uploaded file.'];
    }

    return ['path' => 'data/uploads/' . $safeName];
}

$errors = [];
$success = false;
$registeredNumber = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $firstName = trim($_POST['firstName'] ?? '');
    $secondName = trim($_POST['secondName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $educationLevel = trim($_POST['education_level'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $parentFirstName = trim($_POST['parentFirstName'] ?? '');
    $parentLastName = trim($_POST['parentLastName'] ?? '');
    $parentPhone = trim($_POST['parentPhone'] ?? '');
    $birthCertificate = $_FILES['birth_certificate'] ?? null;
    $idCopy = $_FILES['id_copy'] ?? null;

    if ($firstName === '' || $lastName === '' || $dob === '' || $educationLevel === '' || $email === '' || $password === '') {
        $errors[] = 'Please fill in all required student fields.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDb();

            $duplicateStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM users WHERE lower(first_name) = lower(:fn) AND lower(coalesce(second_name, '')) = lower(coalesce(:sn, '')) AND lower(last_name) = lower(:ln) AND dob = :dob"
            );
            $duplicateStmt->execute([
                ':fn' => $firstName,
                ':sn' => $secondName,
                ':ln' => $lastName,
                ':dob' => $dob,
            ]);
            $duplicateCount = (int) $duplicateStmt->fetchColumn();

            if ($duplicateCount > 0) {
                header('Location: login.php?message=' . urlencode('Student name already exists with the same date of birth. Please login.'));
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();

            $birthCertificatePath = null;
            $idCopyPath = null;
            $uploadsDir = __DIR__ . '/data/uploads';

            if ($birthCertificate && $birthCertificate['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = uploadFile($birthCertificate, $uploadsDir);
                if (isset($result['error'])) {
                    throw new Exception('Birth certificate upload error: ' . $result['error']);
                }
                $birthCertificatePath = $result['path'];
            }

            if ($idCopy && $idCopy['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = uploadFile($idCopy, $uploadsDir);
                if (isset($result['error'])) {
                    throw new Exception('ID copy upload error: ' . $result['error']);
                }
                $idCopyPath = $result['path'];
            }

            $classCodes = [
                'Baby Class' => 'BAB',
                'Nursery' => 'NUR',
                'Pre-Unit' => 'PU',
                'Class 1' => 'CI',
                'Class 2' => 'CII',
                'Class 3' => 'CIII',
                'Class 4' => 'CIV',
                'Class 5' => 'CV',
                'Class 6' => 'CVI',
            ];
            $classCode = $classCodes[$educationLevel] ?? 'UNK';
            $year = date('Y');
            $prefix = 'HUM/' . $year . '/' . $classCode;
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE registration_number LIKE :prefix");
            $countStmt->execute([':prefix' => $prefix . '/%']);
            $sequence = (int) $countStmt->fetchColumn() + 1;
            $registrationNumber = $prefix . '/' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO users (registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone, password_hash, birth_certificate_path, id_copy_path, created_at) VALUES (:reg, :fn, :sn, :ln, :dob, :gender, :education_level, :email, :phone, :pass, :birthCert, :idCopy, datetime('now'))");
            $stmt->execute([
                ':reg' => $registrationNumber,
                ':fn' => $firstName,
                ':sn' => $secondName,
                ':ln' => $lastName,
                ':dob' => $dob,
                ':gender' => $gender,
                ':education_level' => $educationLevel,
                ':email' => $email,
                ':phone' => $phone,
                ':pass' => $password_hash,
                ':birthCert' => $birthCertificatePath,
                ':idCopy' => $idCopyPath,
            ]);

            $userId = $pdo->lastInsertId();

            // Insert parent without password
            $stmt2 = $pdo->prepare("INSERT INTO parents (user_id, first_name, last_name, phone) VALUES (:uid, :pfn, :pln, :pphone)");
            $stmt2->execute([
                ':uid' => $userId,
                ':pfn' => $parentFirstName,
                ':pln' => $parentLastName,
                ':pphone' => $parentPhone,
            ]);

            $pdo->commit();

            // Prepare thank-you flow (do not log the user in)
            $success = true;
            $registeredNumber = $registrationNumber;
            // clear POST to avoid resubmission
            $_POST = [];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Humblekid Pre & Primary School - Register</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="style-register.css?v=20260716">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hidden { display: none; }
    </style>
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
            </ul>
        </div>
    </nav>

    <main class="register-main">
        <div class="register-container">
            <div class="register-box">
                <div class="register-header">
                    <h2>Create Your Account</h2>
                    <p>Join HumbleKid School Community</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="register.php" method="post" enctype="multipart/form-data" novalidate>
                    <div id="studentSection" class="form-section">
                        <h3>Your Personal Details</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="firstName" id="firstName" placeholder=" " required>
                                </div>
                                <label for="firstName" class="floating-label">First Name *</label>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="secondName" id="secondName" placeholder=" ">
                                </div>
                                <label for="secondName" class="floating-label">Second Name</label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="lastName" id="lastName" placeholder=" " required>
                                </div>
                                <label for="lastName" class="floating-label">Last Name *</label>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-calendar"></i>
                                    <input type="date" name="dob" id="dob" required>
                                </div>
                                <label for="dob" class="floating-label">Date of Birth *</label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-venus-mars"></i>
                                    <select name="gender" id="gender" required>
                                        <option value="" disabled selected></option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <label for="gender" class="floating-label">Gender *</label>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-book"></i>
                                    <select name="education_level" id="education_level" required>
                                        <option value="" disabled selected></option>
                                        <option value="Baby Class">Baby Class</option>
                                        <option value="Nursery">Nursery</option>
                                        <option value="Pre-Unit">Pre-Unit</option>
                                        <option value="Class 1">Class 1</option>
                                        <option value="Class 2">Class 2</option>
                                        <option value="Class 3">Class 3</option>
                                        <option value="Class 4">Class 4</option>
                                        <option value="Class 5">Class 5</option>
                                        <option value="Class 6">Class 6</option>
                                    </select>
                                </div>
                                <label for="education_level" class="floating-label">Class Level *</label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="email" placeholder=" " required>
                                </div>
                                <label for="email" class="floating-label">Email Address *</label>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" id="phone" placeholder=" " required>
                                </div>
                                <label for="phone" class="floating-label">Phone Number *</label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="file-label">
                                <i class="fas fa-file-pdf"></i>
                                <span>Upload Birth Certificate (Optional)</span>
                                <input type="file" name="birth_certificate" id="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" hidden>
                            </label>
                            <small>PDF, JPG, JPEG, PNG (Max 5MB)</small>
                        </div>

                        <div class="form-group full-width">
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="password" placeholder=" " minlength="8" required>
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <label for="password" class="floating-label">Password (Min 8 chars) *</label>
                        </div>

                        <button type="button" id="nextBtn" class="btn-primary full-width">
                            <span>Next</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <div id="parentSection" class="form-section hidden">
                        <h3>Parent/Guardian Details</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="parentFirstName" id="parentFirstName" placeholder=" ">
                                </div>
                                <label for="parentFirstName" class="floating-label">First Name</label>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="parentLastName" id="parentLastName" placeholder=" ">
                                </div>
                                <label for="parentLastName" class="floating-label">Last Name</label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="parentPhone" id="parentPhone" placeholder=" " minlength="10">
                            </div>
                            <label for="parentPhone" class="floating-label">Phone Number</label>
                        </div>

                        <div class="form-group full-width">
                            <label class="file-label">
                                <i class="fas fa-id-card"></i>
                                <span>Upload ID/Voter's Card (Optional)</span>
                                <input type="file" name="id_copy" id="id_copy" accept=".pdf,.jpg,.jpeg,.png" hidden>
                            </label>
                            <small>PDF, JPG, JPEG, PNG (Max 5MB)</small>
                        </div>

                        <div class="button-group">
                            <button type="button" id="backBtn" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back</span>
                            </button>
                            <button type="submit" class="btn-primary">
                                <span>Create Account</span>
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <p class="register-footer">Already registered? <a href="login.php" class="link-primary">Login here</a></p>
            </div>

            <div class="register-decoration">
                <div class="decoration-blob blob-1"></div>
                <div class="decoration-blob blob-2"></div>
                <div class="decoration-blob blob-3"></div>
            </div>
        </div>
    </main>
    <?php if ($success): ?>
        <div style="position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); z-index:9999;">
            <div style="background:#fff; padding:40px 32px; border-radius:15px; text-align:center; max-width:420px; width:95%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.5s ease;">
                <div style="width:60px; height:60px; background: linear-gradient(135deg, #ff5a1f, #ff7e3f); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:2em; color:white;">
                    <i class="fas fa-check"></i>
                </div>
                <h2 style="margin:0 0 12px; color:#2c3e50; font-size:1.5em;">Thank you for registering!</h2>
                <p style="margin:0 0 8px; color:#666; font-size:0.95em;">Your registration number is:</p>
                <p style="margin:0 0 20px; font-size:1.3em; font-weight:700; color:#ff5a1f;"><?php echo htmlspecialchars($registeredNumber); ?></p>
                <p style="margin:0; color:#999; font-size:0.9em;">You will be redirected to the login page shortly.</p>
            </div>
        </div>
        <script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>
    <?php endif; ?>
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

        // Form Navigation
        const nextBtn = document.getElementById('nextBtn');
        const backBtn = document.getElementById('backBtn');
        const registerForm = document.getElementById('registerForm');

        function validateStudentSection() {
            const required = ['firstName', 'lastName', 'dob', 'gender', 'education_level', 'email', 'phone', 'password'];
            for (let i = 0; i < required.length; i++) {
                const el = document.getElementById(required[i]);
                if (!el || !el.value || !el.value.trim()) {
                    alert('Please fill all required student fields before continuing.');
                    return false;
                }
            }
            return true;
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (!validateStudentSection()) {
                    return;
                }
                document.getElementById('studentSection').classList.add('hidden');
                document.getElementById('parentSection').classList.remove('hidden');
            });
        }

        if (backBtn) {
            backBtn.addEventListener('click', function () {
                document.getElementById('parentSection').classList.add('hidden');
                document.getElementById('studentSection').classList.remove('hidden');
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', function (event) {
                if (!validateStudentSection()) {
                    event.preventDefault();
                    document.getElementById('parentSection').classList.add('hidden');
                    document.getElementById('studentSection').classList.remove('hidden');
                    return;
                }
            });
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

            // Input focus animations
            document.querySelectorAll('.form-group input, .form-group select').forEach(input => {
                const formGroup = input.closest('.form-group');

                const updateFieldState = () => {
                    const hasValue = input.value.trim().length > 0;
                    formGroup.classList.toggle('has-value', hasValue);
                    formGroup.classList.toggle('focused', document.activeElement === input);
                };

                input.addEventListener('input', updateFieldState);
                input.addEventListener('focus', updateFieldState);
                input.addEventListener('blur', function() {
                    formGroup.classList.remove('focused');
                    updateFieldState();
                });
                input.addEventListener('change', updateFieldState);
                updateFieldState();
            });
        });

        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
    <style>
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>