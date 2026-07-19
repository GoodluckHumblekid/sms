<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/subjects-config.php';

function getSubjectTeacherName(string $subject, string $educationLevel, array $teachers): string
{
    $subjectText = strtolower(trim($subject));
    $levelText = strtolower(trim($educationLevel));

    foreach ($teachers as $teacher) {
        $teacherSubject = strtolower(trim($teacher['subject'] ?? ''));
        $teacherLevel = strtolower(trim($teacher['education_level'] ?? ''));

        if ($teacherSubject !== '' && strpos($subjectText, $teacherSubject) !== false) {
            return trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?: 'Class Teacher';
        }

        if ($teacherLevel !== '' && $teacherLevel === $levelText) {
            return trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?: 'Class Teacher';
        }
    }

    return 'Class Teacher';
}

if (empty($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}
$user = 'Student';
$studentName = 'Student';
$educationLevel = 'Not set';
$registrationNumber = 'Not assigned';
$subjectGroups = [];
$subjectRows = [];
$userId = $_SESSION['userId'] ?? null;
$academicRows = [];
$billRows = [];
$teachers = [];
$latestAcademic = [];
if ($userId) {
    try {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT registration_number, first_name, last_name, education_level FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $studentName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($studentName === '') {
                $studentName = 'Student';
            }
            $educationLevel = $row['education_level'] ?? 'Not set';
            $registrationNumber = $row['registration_number'] ?? 'Not assigned';
            $user = htmlspecialchars($registrationNumber . ' - ' . $studentName, ENT_QUOTES, 'UTF-8');
        }

        $perfStmt = $pdo->prepare('SELECT number_of_subjects, grade, total, average, position, remarks, remarks_director FROM academic_performance WHERE user_id = :id ORDER BY id');
        $perfStmt->execute([':id' => $userId]);
        $academicRows = $perfStmt->fetchAll(PDO::FETCH_ASSOC);
        $latestAcademic = !empty($academicRows) ? $academicRows[count($academicRows) - 1] : [];

        $teacherStmt = $pdo->prepare('SELECT first_name, last_name, education_level, subject FROM teachers ORDER BY last_name, first_name');
        $teacherStmt->execute();
        $teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get subjects from centralized config
        $allSubjectsConfig = getSubjectsConfig();
        $subjectGroups = [];
        
        // Build subject groups in the format expected (with education level as key)
        foreach ($allSubjectsConfig as $level => $subjects) {
            $subjectGroups[$level] = [$level => $subjects];
        }
        
        // Also support alternative names like "Early Childhood Education" and "Primary School"
        if (!isset($subjectGroups[$educationLevel])) {
            if ($educationLevel === 'Early Childhood Education') {
                $subjectGroups[$educationLevel] = $subjectGroups['Baby Class'] ?? [];
            } elseif ($educationLevel === 'Primary School') {
                $subjectGroups[$educationLevel] = $subjectGroups['Class 1'] ?? [];
            }
        }

        $subjectRows = $subjectGroups[$educationLevel] ?? [];

        $billStmt = $pdo->prepare('SELECT id, type, months, account_number, reference_number, status FROM bills WHERE user_id = :id ORDER BY id');
        $billStmt->execute([':id' => $userId]);
        $billRows = $billStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore DB errors for now
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Humblekid School</title>
    <link rel="stylesheet" href="style-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Dashboard Specific Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), #ff7e3f);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(255, 90, 31, 0.2);
            animation: slideUp 0.6s ease-out;
        }

        .stat-card .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-card .stat-label {
            font-size: 0.95em;
            opacity: 0.9;
        }

        .stat-card i {
            font-size: 2.5em;
            opacity: 0.7;
            float: right;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #3498db, #2980b9);
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #27ae60, #229954);
            animation-delay: 0.2s;
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #8e44ad, #7d3c98);
            animation-delay: 0.3s;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
            transition: var(--transition);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-color);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 5px;
            align-items: center;
        }

        .nav-link {
            padding: 10px 16px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: linear-gradient(135deg, var(--primary-color), #ff7e3f);
            color: white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #ff7e3f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(255, 90, 31, 0.3);
        }

        .user-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            overflow: hidden;
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .user-menu.active {
            display: block;
        }

        .user-menu-header {
            padding: 15px 20px;
            border-bottom: 2px solid #f0f0f0;
            background: #f8f9fa;
        }

        .user-menu-header strong {
            display: block;
            margin-bottom: 5px;
        }

        .user-menu-header small {
            color: #999;
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }

        .user-menu-item:last-child {
            border-bottom: none;
        }

        .user-menu-item:hover {
            background: #f8f9fa;
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .main-content {
            padding-top: 100px;
            padding-bottom: 40px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead th {
            background: #f8f9fa;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #ecf0f1;
        }

        .data-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #ecf0f1;
            color: #555;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status-paid {
            background: #d4edda;
            color: var(--success-color);
        }

        .status-pending {
            background: #fff3cd;
            color: var(--warning-color);
        }

        .status-not-paid {
            background: #f8d7da;
            color: var(--error-color);
        }

        @media (max-width: 768px) {
            .nav-container {
                height: 60px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding-top: 80px;
            }

            .data-table {
                font-size: 0.9em;
            }

            .data-table thead th,
            .data-table tbody td {
                padding: 10px 12px;
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                height: 60px;
            }

            .nav-logo span {
                display: none;
            }

            .nav-menu {
                gap: 0;
            }

            .nav-link {
                padding: 8px 12px;
            }

            .stat-card .stat-value {
                font-size: 2em;
            }

            .stat-card i {
                font-size: 2em;
            }

            .user-menu {
                right: -20px;
                width: calc(100% + 40px);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>HumbleKid</span>
            </div>

            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="#subjects" class="nav-link"><i class="fas fa-book"></i> Subjects</a></li>
                <li><a href="#performance" class="nav-link"><i class="fas fa-chart-line"></i> Performance</a></li>
                <li><a href="#bills" class="nav-link"><i class="fas fa-file-invoice"></i> Bills</a></li>
            </ul>

            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar">
                    <?php
                        $nameParts = preg_split('/\s+/', trim($studentName)) ?: ['S'];
                        $firstInitial = isset($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : 'S';
                        $lastInitial = isset($nameParts[count($nameParts) - 1]) ? strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1)) : 'S';
                        echo htmlspecialchars($firstInitial . $lastInitial, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
                
                <div class="user-menu" id="userMenu">
                    <div class="user-menu-header">
                        <strong><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($registrationNumber, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                    <a href="edit-first-name.php" class="user-menu-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Edit Name</span>
                    </a>
                    <a href="edit-birthday.php" class="user-menu-item">
                        <i class="fas fa-birthday-cake"></i>
                        <span>Edit Birthday</span>
                    </a>
                    <a href="change-password.php" class="user-menu-item">
                        <i class="fas fa-lock"></i>
                        <span>Change Password</span>
                    </a>
                    <a href="logout.php" class="user-menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout Session</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Welcome Section -->
            <div style="margin-bottom: 40px; animation: slideUp 0.6s ease-out;">
                <h1 style="font-size: 2.5em; color: var(--text-dark); margin-bottom: 8px;">
                    <i class="fas fa-wave-hand" style="color: var(--primary-color); margin-right: 10px;"></i>
                    Welcome, <?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>!
                </h1>
                <p style="color: #999; font-size: 1.1em; margin: 0;">
                    Check your academic performance, subjects, and billing information
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <i class="fas fa-user"></i>
                    <div class="stat-label">Registration #</div>
                    <div class="stat-value" style="font-size: 1.8em; word-break: break-all;"><?php echo htmlspecialchars($registrationNumber, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-graduation-cap"></i>
                    <div class="stat-label">Education Level</div>
                    <div class="stat-value" style="font-size: 1.8em;"><?php echo htmlspecialchars($educationLevel, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <?php if (!empty($latestAcademic)): ?>
                    <div class="stat-card">
                        <i class="fas fa-star"></i>
                        <div class="stat-label">Average</div>
                        <div class="stat-value"><?php echo htmlspecialchars($latestAcademic['average'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-medal"></i>
                        <div class="stat-label">Position</div>
                        <div class="stat-value"><?php echo htmlspecialchars($latestAcademic['position'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Subjects Section -->
            <?php if (!empty($subjectRows)): ?>
                <div class="card" id="subjects" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <i class="fas fa-book"></i>
                        <h2>Subjects for <?php echo htmlspecialchars($educationLevel, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Class/Level</th>
                                        <th>Subjects</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjectRows as $className => $subjects): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <span style="padding: 6px 12px; background: #f0f7ff; color: #3498db; border-radius: 6px; font-size: 0.9em;">
                                                            <?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Academic Performance Section -->
            <div class="card" id="performance" style="animation-delay: 0.2s;">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i>
                    <h2>Academic Performance</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($latestAcademic) || !empty($subjectRows)): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                                <div>
                                    <div style="font-size: 0.9em; color: #999; margin-bottom: 5px;">Grade</div>
                                    <div style="font-size: 1.8em; font-weight: 700; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($latestAcademic['grade'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.9em; color: #999; margin-bottom: 5px;">Total Marks</div>
                                    <div style="font-size: 1.8em; font-weight: 700; color: #3498db;">
                                        <?php echo htmlspecialchars($latestAcademic['total'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.9em; color: #999; margin-bottom: 5px;">Remarks</div>
                                    <div style="font-size: 1em; font-weight: 600; color: #27ae60;">
                                        <?php echo htmlspecialchars($latestAcademic['remarks'] ?? 'Good progress', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Subject Teacher</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($subjectRows)): ?>
                                        <?php $subjectRowsCount = 0; $subjectMarksTotal = 0; ?>
                                        <?php foreach ($subjectRows as $className => $subjects): ?>
                                            <?php foreach ($subjects as $subject): ?>
                                                <?php $subjectRowsCount++; ?>
                                                <?php $teacherName = getSubjectTeacherName($subject, $educationLevel, $teachers); ?>
                                                <?php $subjectMarksValue = $subjectRowsCount > 0 ? number_format(((float)($latestAcademic['total'] ?? 0)) / max(1, $subjectRowsCount), 2, '.', '') : '0'; ?>
                                                <?php $subjectMarksTotal += (float)$subjectMarksValue; ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($subjectMarksValue, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string)($latestAcademic['grade'] ?? '--'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string)($latestAcademic['remarks'] ?? 'No remarks yet'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo htmlspecialchars(number_format($subjectMarksTotal, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr><td colspan="5">No subject data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-chart-line" style="font-size: 3em; margin-bottom: 20px; opacity: 0.5;"></i>
                            <p>No academic performance data available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bills Section -->
            <div class="card" id="bills" style="animation-delay: 0.3s;">
                <div class="card-header">
                    <i class="fas fa-file-invoice"></i>
                    <h2>Billing Information</h2>
                </div>
                <div class="card-body">
                    <?php if ($billRows): ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Bill Type</th>
                                        <th>Months</th>
                                        <th>Account #</th>
                                        <th>Reference #</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($billRows as $bill): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars((string)($bill['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td>
                                                <?php
                                                    $monthItems = array_values(array_filter(array_map('trim', preg_split('/[,;\n|]+/', (string)($bill['months'] ?? ''))), static function ($month) {
                                                        return $month !== '';
                                                    }));
                                                ?>
                                                <?php if (!empty($monthItems)): ?>
                                                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                                        <?php foreach ($monthItems as $monthItem): ?>
                                                            <span style="padding:4px 8px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:0.82em;">
                                                                <?php echo htmlspecialchars($monthItem, ENT_QUOTES, 'UTF-8'); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color:#999;">No month selected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)($bill['account_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string)($bill['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php 
                                                    $status = (string)($bill['status'] ?? '');
                                                    $statusClass = $status === 'Paid' ? 'status-paid' : ($status === 'Pending' ? 'status-pending' : 'status-not-paid');
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-file-invoice" style="font-size: 3em; margin-bottom: 20px; opacity: 0.5;"></i>
                            <p>No billing information available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        const userProfileBtn = document.getElementById('userProfileBtn');
        const userMenu = document.getElementById('userMenu');

        if (userProfileBtn && userMenu) {
            userProfileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('active');
            });

            document.addEventListener('click', function() {
                userMenu.classList.remove('active');
            });
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const element = document.querySelector(href);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    </script>
</body>
</html>
