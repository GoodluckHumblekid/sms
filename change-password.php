<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please fill in all password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New password and confirmation do not match.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $_SESSION['userId']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                $message = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $update->execute([':hash' => $newHash, ':id' => $_SESSION['userId']]);
                $message = 'Password updated successfully.';
            }
        } catch (Exception $e) {
            $message = 'Unable to update password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Humblekid School</title>
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
                <h1>Change Password</h1>
                <p>Update your account security</p>
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
                    <i class="fas fa-lock"></i>
                    <h2>Update Your Password</h2>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                name="current_password" 
                                placeholder="Enter your current password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                        <label class="floating-label">Current Password</label>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-key"></i>
                            <input 
                                type="password" 
                                name="new_password" 
                                placeholder="Enter your new password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >
                        </div>
                        <label class="floating-label">New Password (Min 8 characters)</label>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-check"></i>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                placeholder="Confirm your new password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >
                        </div>
                        <label class="floating-label">Confirm Password</label>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i>
                        <span>Update Password</span>
                    </button>
                </form>

                <div class="form-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Make sure your password is strong and unique. Use a mix of uppercase, lowercase, numbers, and symbols.</p>
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
