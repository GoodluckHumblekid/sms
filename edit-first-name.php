<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    if ($fullName === '') {
        $message = 'Full name cannot be empty.';
    } else {
        $nameParts = preg_split('/\s+/', $fullName);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[count($nameParts) - 1] ?? '';

        if ($firstName === '' || $lastName === '') {
            $message = 'Please enter both first and last name.';
        } else {
            try {
                $pdo = getDb();
                $stmt = $pdo->prepare('UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id');
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':id' => $_SESSION['userId']
                ]);
                $message = 'Full name updated successfully.';
            } catch (Exception $e) {
                $message = 'Unable to update full name.';
            }
        }
    }
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['userId']]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
$currentFullName = trim(($current['first_name'] ?? '') . ' ' . ($current['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Full Name - Humblekid School</title>
    <link rel="stylesheet" href="style-dashboard.css">
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

    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>Edit Full Name</h1>
                <p>Update your profile information</p>
            </div>
            <a href="sms.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="card card-form">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i>
                    <h2>Your Full Name</h2>
                </div>

                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                            <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="form">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input 
                                    type="text" 
                                    name="full_name" 
                                    value="<?php echo htmlspecialchars($currentFullName, ENT_QUOTES, 'UTF-8'); ?>" 
                                    placeholder="Enter your full name"
                                    required
                                >
                            </div>
                            <label class="floating-label">Full Name</label>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </form>

                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> Tip</h4>
                        <p>Enter both your first and last name separated by a space. Your name will be used throughout your student profile.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
                    if (!this.value) {
                        this.parentElement.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>
