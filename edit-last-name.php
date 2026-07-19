<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName = trim($_POST['last_name'] ?? '');
    if ($lastName === '') {
        $message = 'Last name cannot be empty.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('UPDATE users SET last_name = :last_name WHERE id = :id');
            $stmt->execute([':last_name' => $lastName, ':id' => $_SESSION['userId']]);
            $message = 'Last name updated successfully.';
        } catch (Exception $e) {
            $message = 'Unable to update last name.';
        }
    }
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT last_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['userId']]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
$currentLastName = $current['last_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Last Name - Humblekid School</title>
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
                <h1>Edit Last Name</h1>
                <p>Update your surname</p>
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
                    <i class="fas fa-id-card"></i>
                    <h2>Last Name</h2>
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
                                    name="last_name" 
                                    value="<?php echo htmlspecialchars($currentLastName, ENT_QUOTES, 'UTF-8'); ?>" 
                                    placeholder="Enter your last name"
                                    required
                                >
                            </div>
                            <label class="floating-label">Last Name</label>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </form>

                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> Note</h4>
                        <p>Your last name is important for school records and official communications. Please ensure it's spelled correctly.</p>
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
