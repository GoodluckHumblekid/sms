<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/subjects-config.php';

function getClassCode(string $educationLevel): string
{
    $map = [
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

    return $map[$educationLevel] ?? 'UNK';
}

function getSubjectGroups(string $educationLevel): array
{
    $subjects = getSubjectsForLevel($educationLevel);
    if (empty($subjects)) {
        return [];
    }
    
    // Return in the format expected by the application (with class name as key)
    return [
        $educationLevel => $subjects
    ];
}

function esc($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

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

function getSubjectMarkValue($performance, array $subjects): string
{
    $subjectCount = count($subjects);
    if ($subjectCount <= 0) {
        $subjectCount = (int)($performance['number_of_subjects'] ?? 0);
    }

    if ($subjectCount <= 0) {
        return (string)($performance['total'] ?? '0');
    }

    $totalMarks = (float)($performance['total'] ?? 0);
    return number_format($totalMarks / $subjectCount, 2, '.', '');
}

function getBillAmountLabel(string $billType, string $months): string
{
    $normalizedType = strtolower(trim($billType));
    $monthCount = 0;

    if (preg_match('/(\d+)/', $months, $matches) === 1) {
        $monthCount = (int) $matches[1];
    }

    if ($monthCount <= 0) {
        $monthCount = 3;
    }

    $suffix = $monthCount === 1 ? 'month' : 'months';

    if ($normalizedType === 'fees') {
        return '460,000 Tshs for ' . $monthCount . ' ' . $suffix;
    }

    if ($normalizedType === 'transport') {
        return '150,000 Tshs for ' . $monthCount . ' ' . $suffix;
    }

    if ($normalizedType === 'uniform') {
        return '120,000 Tshs';
    }

    return '0 Tshs';
}

function resolveUploadUrl(string $path, string $scriptName = ''): string
{
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    $base = rtrim(dirname($scriptName ?: '/admin.php'), '/');
    $base = $base !== '' && $base !== '.' ? $base : '';

    return $base . '/' . ltrim($path, '/');
}

function uploadTeacherImage(array $file, string $uploadDir)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['error' => 'Allowed image types: JPG, JPEG, PNG, GIF, WEBP.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Each image must be 5MB or smaller.'];
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['error' => 'Unable to create upload directory.'];
    }

    $safeName = uniqid('teacher_', true) . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($file['name']));
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['error' => 'Failed to move uploaded image.'];
    }

    return ['path' => 'data/uploads/' . $safeName];
}

function sendStudentCredentialsEmail(string $studentEmail, string $firstName, string $lastName, string $registrationNumber, string $password, string $schoolName = 'Humblekid School'): bool
{
    $studentName = trim($firstName . ' ' . $lastName);
    $subject = "Welcome to {$schoolName} - Your Login Credentials";
    
    $message = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        .header { background: linear-gradient(135deg, #ff5a1f, #ff7e3f); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
        .content { background: white; padding: 20px; border-radius: 0 0 5px 5px; }
        .credentials-box { background: #f0f4f8; border-left: 4px solid #ff5a1f; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .label { font-weight: bold; color: #555; }
        .value { color: #ff5a1f; font-family: monospace; font-size: 14px; }
        .instructions { background: #e8f4f8; border: 1px solid #b3d9e8; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .footer { color: #999; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        .button { display: inline-block; padding: 10px 20px; background: #ff5a1f; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$schoolName}</h1>
            <p>Student Account Created Successfully</p>
        </div>
        <div class='content'>
            <p>Dear <strong>{$studentName}</strong>,</p>
            
            <p>Welcome to {$schoolName}! We are delighted to have you as a student member of our community. Your account has been successfully created in our School Management System.</p>
            
            <p>Your login credentials are provided below. Please keep this information secure and do not share it with anyone.</p>
            
            <div class='credentials-box'>
                <p><span class='label'>Registration Number:</span><br><span class='value'>{$registrationNumber}</span></p>
                <p><span class='label'>Email Address:</span><br><span class='value'>{$studentEmail}</span></p>
                <p><span class='label'>Password:</span><br><span class='value'>{$password}</span></p>
            </div>
            
            <div class='instructions'>
                <h3 style='margin-top: 0;'>How to Log In:</h3>
                <ol>
                    <li>Visit the login page at your school's portal</li>
                    <li>Enter your email address: <strong>{$studentEmail}</strong></li>
                    <li>Enter your password</li>
                    <li>Click 'Login' to access your dashboard</li>
                </ol>
                <p><strong>Important:</strong> We recommend changing your password upon first login for security purposes.</p>
            </div>
            
            <p>In your dashboard, you will be able to:</p>
            <ul>
                <li>View your enrolled subjects</li>
                <li>Check your academic performance and grades</li>
                <li>View billing information</li>
                <li>Update your profile information</li>
            </ul>
            
            <p>If you experience any issues accessing your account or have questions, please contact the school administration office.</p>
            
            <p>Best regards,<br>
            <strong>{$schoolName} - Administration Team</strong></p>
            
            <div class='footer'>
                <p>This is an automated email. Please do not reply to this email address.</p>
                <p>&copy; " . date('Y') . " {$schoolName}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . $schoolName . " <noreply@school.local>" . "\r\n";
    
    return mail($studentEmail, $subject, $message, $headers);
}

$students = [];
$selectedStudent = null;
$teachers = [];
$selectedTeacher = null;
$message = '';
$errorMessage = '';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@example.com';
$totalStudents = 0;
$totalTeachers = 0;
$totalClasses = 0;

$allowedViews = ['student', 'teachers', 'classes', 'staff', 'payment', 'subjects'];
$view = $_GET['view'] ?? 'student';
$view = in_array($view, $allowedViews, true) ? $view : 'student';
$isStudentView = $view === 'student';
$isTeacherView = $view === 'teachers';
$isClassesView = $view === 'classes';
$isStaffView = $view === 'staff';
$isPaymentView = $view === 'payment';
$isSubjectsView = $view === 'subjects';
$selectedId = $_GET['student_id'] ?? null;
$selectedTeacherId = $_GET['teacher_id'] ?? null;

try {
    if (isset($_GET['download_template'])) {
        $pdo = getDb();
        $exportView = $_GET['view'] ?? 'student';
        $filename = 'export-' . $exportView . '-template.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "sep=,\n";

        if ($exportView === 'teachers') {
            echo "id,first_name,second_name,last_name,education_level,subject,email,phone,image_path,created_at\n";
            $rows = $pdo->query('SELECT id, first_name, second_name, last_name, education_level, subject, email, phone, image_path, created_at FROM teachers ORDER BY last_name ASC, first_name ASC')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                echo implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row));
                echo "\n";
            }
        } elseif ($exportView === 'classes') {
            echo "class,children_count,teacher_count,teacher_names\n";
            $classLevels = ['Baby Class','Nursery','Pre-Unit','Class 1','Class 2','Class 3','Class 4','Class 5','Class 6'];
            $studentCounts = array_fill_keys($classLevels, 0);
            $teacherCounts = array_fill_keys($classLevels, 0);
            $teacherNames = array_fill_keys($classLevels, '');

            $studentRows = $pdo->query('SELECT education_level FROM users')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($studentRows as $student) {
                $level = $student['education_level'] ?? '';
                if ($level !== '' && isset($studentCounts[$level])) {
                    $studentCounts[$level]++;
                }
            }

            $teacherRows = $pdo->query('SELECT education_level, GROUP_CONCAT(first_name || " " || last_name, ", ") AS teachers FROM teachers GROUP BY education_level')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($teacherRows as $row) {
                $level = $row['education_level'] ?? '';
                if ($level !== '' && isset($teacherCounts[$level])) {
                    $teacherCounts[$level] = (int) $pdo->query('SELECT COUNT(*) FROM teachers WHERE education_level = ' . $pdo->quote($level))->fetchColumn();
                    $teacherNames[$level] = $row['teachers'] ?? '';
                }
            }

            foreach ($classLevels as $level) {
                $row = [
                    $level,
                    $studentCounts[$level],
                    $teacherCounts[$level],
                    $teacherNames[$level],
                ];
                echo implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row));
                echo "\n";
            }
        } else {
            echo "registration_number,first_name,second_name,last_name,dob,gender,education_level,email,phone,password\n";
            $rows = $pdo->query('SELECT registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone FROM users ORDER BY registration_number ASC')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $row['password'] = '';
                echo implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row));
                echo "\n";
            }
        }

        exit;
    }

    $pdo = getDb();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $studentId = $_POST['student_id'] ?? null;
        $teacherId = $_POST['teacher_id'] ?? null;

        if ($action === 'save_admin_profile') {
            $adminName = trim($_POST['admin_name'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            if ($adminName !== '') {
                $_SESSION['admin_name'] = $adminName;
            }
            if ($adminEmail !== '') {
                $_SESSION['admin_email'] = $adminEmail;
            }
            $_SESSION['flash_message'] = 'Admin profile updated successfully.';
            header('Location: admin.php?view=' . urlencode($view) . '&student_id=' . urlencode((string) ($studentId ?? '')));
            exit;
        }

        if ($action === 'import_teachers') {
            if (!isset($_FILES['teacher_template']) || $_FILES['teacher_template']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Please choose a valid teacher template file to upload.';
            } else {
                $uploadedFile = $_FILES['teacher_template'];
                $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['xls', 'csv', 'txt'], true)) {
                    $errorMessage = 'Only XLS/CSV templates are allowed for teacher upload.';
                } else {
                    $imported = 0;
                    $pdo->beginTransaction();
                    $tmpPath = $uploadedFile['tmp_name'];
                    if (($handle = fopen($tmpPath, 'r')) !== false) {
                        $row = 0;
                        while (($data = fgetcsv($handle)) !== false) {
                            $row++;
                            if ($row === 1) {
                                continue;
                            }

                            if (count($data) < 8) {
                                continue;
                            }

                            [$id, $fn, $sn, $ln, $educationLevel, $subject, $email, $phone, $imagePath] = array_map('trim', $data);
                            if ($fn === '' || $ln === '' || $educationLevel === '' || $email === '') {
                                continue;
                            }

                            $existing = null;
                            if ($id !== '') {
                                $existsStmt = $pdo->prepare('SELECT id FROM teachers WHERE id = :id LIMIT 1');
                                $existsStmt->execute([':id' => (int) $id]);
                                $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);
                            }

                            if (!$existing && $email !== '') {
                                $existsStmt = $pdo->prepare('SELECT id FROM teachers WHERE email = :email LIMIT 1');
                                $existsStmt->execute([':email' => $email]);
                                $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);
                            }

                            if ($existing) {
                                $update = $pdo->prepare('UPDATE teachers SET first_name = :fn, second_name = :sn, last_name = :ln, education_level = :education_level, subject = :subject, email = :email, phone = :phone, image_path = :image_path WHERE id = :id');
                                $update->execute([
                                    ':fn' => $fn,
                                    ':sn' => $sn,
                                    ':ln' => $ln,
                                    ':education_level' => $educationLevel,
                                    ':subject' => $subject,
                                    ':email' => $email,
                                    ':phone' => $phone,
                                    ':image_path' => $imagePath,
                                    ':id' => $existing['id'],
                                ]);
                                $imported++;
                            } else {
                                $insert = $pdo->prepare('INSERT INTO teachers (first_name, second_name, last_name, education_level, subject, email, phone, image_path, created_at) VALUES (:fn, :sn, :ln, :education_level, :subject, :email, :phone, :image_path, datetime("now"))');
                                $insert->execute([
                                    ':fn' => $fn,
                                    ':sn' => $sn,
                                    ':ln' => $ln,
                                    ':education_level' => $educationLevel,
                                    ':subject' => $subject,
                                    ':email' => $email,
                                    ':phone' => $phone,
                                    ':image_path' => $imagePath,
                                ]);
                                $imported++;
                            }
                        }
                        fclose($handle);
                        $pdo->commit();
                        $message = "Teacher template imported successfully. {$imported} records updated or added.";
                    } else {
                        $pdo->rollBack();
                        $errorMessage = 'Unable to open uploaded teacher template file.';
                    }
                }
            }
        }

        if ($action === 'delete_teacher' && $teacherId) {
            $delete = $pdo->prepare('DELETE FROM teachers WHERE id = :id');
            $delete->execute([':id' => $teacherId]);
            header('Location: admin.php?view=teachers&message=' . urlencode('Teacher deleted successfully.'));
            exit;
        }

        if ($action === 'add_teacher' || $action === 'edit_teacher') {
            $firstName = trim($_POST['first_name'] ?? '');
            $secondName = trim($_POST['second_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $educationLevel = trim($_POST['education_level'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $imagePath = '';
            $teacherImage = $_FILES['teacher_image'] ?? null;

            if ($teacherImage && $teacherImage['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadTeacherImage($teacherImage, __DIR__ . '/data/uploads');
                if (isset($uploadResult['error'])) {
                    $errorMessage = $uploadResult['error'];
                } else {
                    $imagePath = $uploadResult['path'];
                }
            } elseif ($teacherId) {
                $existingTeacherStmt = $pdo->prepare('SELECT image_path FROM teachers WHERE id = :id LIMIT 1');
                $existingTeacherStmt->execute([':id' => $teacherId]);
                $existingTeacher = $existingTeacherStmt->fetch(PDO::FETCH_ASSOC);
                $imagePath = $existingTeacher['image_path'] ?? '';
            }

            if ($errorMessage === '' && ($firstName === '' || $lastName === '' || $educationLevel === '' || $email === '')) {
                $errorMessage = 'First name, last name, class, and email are required.';
            } else {
                if ($action === 'add_teacher') {
                    $insert = $pdo->prepare('INSERT INTO teachers (first_name, second_name, last_name, education_level, subject, email, phone, image_path, created_at) VALUES (:fn, :sn, :ln, :education_level, :subject, :email, :phone, :image_path, datetime("now"))');
                    $insert->execute([
                        ':fn' => $firstName,
                        ':sn' => $secondName,
                        ':ln' => $lastName,
                        ':education_level' => $educationLevel,
                        ':subject' => $subject,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':image_path' => $imagePath,
                    ]);
                    $newId = $pdo->lastInsertId();
                    $_SESSION['flash_message'] = 'Teacher created successfully.';
                    header('Location: admin.php?view=teachers&teacher_id=' . $newId);
                    exit;
                }

                if ($action === 'edit_teacher' && $teacherId) {
                    $update = $pdo->prepare('UPDATE teachers SET first_name = :fn, second_name = :sn, last_name = :ln, education_level = :education_level, subject = :subject, email = :email, phone = :phone, image_path = :image_path WHERE id = :id');
                    $update->execute([
                        ':fn' => $firstName,
                        ':sn' => $secondName,
                        ':ln' => $lastName,
                        ':education_level' => $educationLevel,
                        ':subject' => $subject,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':image_path' => $imagePath,
                        ':id' => $teacherId,
                    ]);
                    $_SESSION['flash_message'] = 'Teacher updated successfully.';
                    header('Location: admin.php?view=teachers&teacher_id=' . $teacherId);
                    exit;
                }
            }
        }

        if ($action === 'save_student_performance' && $studentId) {
            $performanceId = $_POST['performance_id'] ?? null;
            $grade = trim($_POST['grade'] ?? '');
            $total = trim($_POST['total'] ?? '');
            $average = trim($_POST['average'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');
            $remarksDirector = trim($_POST['remarks_director'] ?? '');
            $numberOfSubjects = trim($_POST['number_of_subjects'] ?? '8');

            if ($performanceId) {
                $stmt = $pdo->prepare('UPDATE academic_performance SET number_of_subjects = :number_of_subjects, grade = :grade, total = :total, average = :average, position = :position, remarks = :remarks, remarks_director = :remarks_director WHERE id = :id AND user_id = :user_id');
                $stmt->execute([
                    ':number_of_subjects' => $numberOfSubjects,
                    ':grade' => $grade,
                    ':total' => $total,
                    ':average' => $average,
                    ':position' => $position,
                    ':remarks' => $remarks,
                    ':remarks_director' => $remarksDirector,
                    ':id' => $performanceId,
                    ':user_id' => $studentId,
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO academic_performance (user_id, number_of_subjects, grade, total, average, position, remarks, remarks_director) VALUES (:user_id, :number_of_subjects, :grade, :total, :average, :position, :remarks, :remarks_director)');
                $stmt->execute([
                    ':user_id' => $studentId,
                    ':number_of_subjects' => $numberOfSubjects,
                    ':grade' => $grade,
                    ':total' => $total,
                    ':average' => $average,
                    ':position' => $position,
                    ':remarks' => $remarks,
                    ':remarks_director' => $remarksDirector,
                ]);
            }
            $_SESSION['flash_message'] = 'Academic performance saved successfully.';
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'delete_student_performance' && $studentId) {
            $performanceId = $_POST['performance_id'] ?? null;
            if ($performanceId) {
                $stmt = $pdo->prepare('DELETE FROM academic_performance WHERE id = :id AND user_id = :user_id');
                $stmt->execute([':id' => $performanceId, ':user_id' => $studentId]);
                $_SESSION['flash_message'] = 'Academic performance deleted successfully.';
            }
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'save_student_billing' && $studentId) {
            $billId = $_POST['bill_id'] ?? null;
            $type = trim($_POST['bill_type'] ?? '');
            $months = trim($_POST['bill_months'] ?? '');
            $accountNumber = trim($_POST['account_number'] ?? '');
            $referenceNumber = trim($_POST['reference_number'] ?? '');
            $status = trim($_POST['status'] ?? 'Pending');

            if ($billId) {
                $stmt = $pdo->prepare('UPDATE bills SET type = :type, months = :months, account_number = :account_number, reference_number = :reference_number, status = :status WHERE id = :id AND user_id = :user_id');
                $stmt->execute([
                    ':type' => $type,
                    ':months' => $months,
                    ':account_number' => $accountNumber,
                    ':reference_number' => $referenceNumber,
                    ':status' => $status,
                    ':id' => $billId,
                    ':user_id' => $studentId,
                ]);
                $_SESSION['flash_message'] = 'Billing record updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO bills (user_id, type, months, account_number, reference_number, status) VALUES (:user_id, :type, :months, :account_number, :reference_number, :status)');
                $stmt->execute([
                    ':user_id' => $studentId,
                    ':type' => $type,
                    ':months' => $months,
                    ':account_number' => $accountNumber,
                    ':reference_number' => $referenceNumber,
                    ':status' => $status,
                ]);
                $_SESSION['flash_message'] = 'Billing record added successfully.';
            }
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'delete_student_billing' && $studentId) {
            $billId = $_POST['bill_id'] ?? null;
            if ($billId) {
                $stmt = $pdo->prepare('DELETE FROM bills WHERE id = :id AND user_id = :user_id');
                $stmt->execute([':id' => $billId, ':user_id' => $studentId]);
                $_SESSION['flash_message'] = 'Billing record deleted successfully.';
            }
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'save_subject_performance' && $studentId) {
            $subjPerfId = $_POST['subject_perf_id'] ?? null;
            $subject = trim($_POST['subject'] ?? '');
            $marks = $_POST['marks'] !== '' ? (int)$_POST['marks'] : null;
            $grade = trim($_POST['grade'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');

            if (!$subject) {
                $_SESSION['flash_message'] = 'Subject name is required.';
            } else {
                if ($subjPerfId) {
                    $stmt = $pdo->prepare('UPDATE subject_performance SET subject = :subject, marks = :marks, grade = :grade, remarks = :remarks WHERE id = :id AND user_id = :user_id');
                    $stmt->execute([
                        ':subject' => $subject,
                        ':marks' => $marks,
                        ':grade' => $grade,
                        ':remarks' => $remarks,
                        ':id' => $subjPerfId,
                        ':user_id' => $studentId,
                    ]);
                    $_SESSION['flash_message'] = 'Subject performance updated successfully.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO subject_performance (user_id, subject, marks, grade, remarks) VALUES (:user_id, :subject, :marks, :grade, :remarks)');
                    $stmt->execute([
                        ':user_id' => $studentId,
                        ':subject' => $subject,
                        ':marks' => $marks,
                        ':grade' => $grade,
                        ':remarks' => $remarks,
                    ]);
                    $_SESSION['flash_message'] = 'Subject performance added successfully.';
                }
            }
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'delete_subject_performance' && $studentId) {
            $subjPerfId = $_POST['subject_perf_id'] ?? null;
            if ($subjPerfId) {
                $stmt = $pdo->prepare('DELETE FROM subject_performance WHERE id = :id AND user_id = :user_id');
                $stmt->execute([':id' => $subjPerfId, ':user_id' => $studentId]);
                $_SESSION['flash_message'] = 'Subject performance deleted successfully.';
            }
            header('Location: admin.php?view=student&student_id=' . $studentId);
            exit;
        }

        if ($action === 'import_students') {
            if (!isset($_FILES['student_template']) || $_FILES['student_template']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Please choose a valid template file to upload.';
            } else {
                $uploadedFile = $_FILES['student_template'];
                $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['xls', 'csv', 'txt'], true)) {
                    $errorMessage = 'Only XLS/CSV templates are allowed for upload.';
                } else {
                    $importErrors = [];
                    $imported = 0;
                    $pdo->beginTransaction();

                    $tmpPath = $uploadedFile['tmp_name'];
                    if (($handle = fopen($tmpPath, 'r')) !== false) {
                        $row = 0;
                        while (($data = fgetcsv($handle)) !== false) {
                            $row++;
                            if ($row === 1) {
                                continue;
                            }

                            if (count($data) < 10) {
                                continue;
                            }

                            list($reg, $fn, $sn, $ln, $dob, $gender, $educationLevel, $email, $phone, $password) = array_map('trim', $data);
                            if ($fn === '' || $ln === '' || $dob === '' || $educationLevel === '' || $email === '') {
                                continue;
                            }

                            if ($reg !== '') {
                                $existsStmt = $pdo->prepare('SELECT id FROM users WHERE registration_number = :reg LIMIT 1');
                                $existsStmt->execute([':reg' => $reg]);
                                $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);
                            } else {
                                $existing = false;
                            }

                            if ($existing) {
                                $update = $pdo->prepare('UPDATE users SET first_name = :fn, second_name = :sn, last_name = :ln, dob = :dob, gender = :gender, education_level = :educationLevel, email = :email, phone = :phone' . ($password !== '' ? ', password_hash = :pass' : '') . ' WHERE id = :id');
                                $params = [
                                    ':fn' => $fn,
                                    ':sn' => $sn,
                                    ':ln' => $ln,
                                    ':dob' => $dob,
                                    ':gender' => $gender,
                                    ':educationLevel' => $educationLevel,
                                    ':email' => $email,
                                    ':phone' => $phone,
                                    ':id' => $existing['id'],
                                ];
                                if ($password !== '') {
                                    $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
                                }
                                $update->execute($params);
                                $imported++;
                            } else {
                                if ($reg === '') {
                                    $classCode = getClassCode($educationLevel);
                                    $year = date('Y');
                                    $prefix = "HUM/{$year}/{$classCode}";
                                    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE registration_number LIKE :prefix');
                                    $countStmt->execute([':prefix' => $prefix . '/%']);
                                    $sequence = (int) $countStmt->fetchColumn() + 1;
                                    $reg = sprintf('%s/%04d', $prefix, $sequence);
                                }
                                $insert = $pdo->prepare('INSERT INTO users (registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone, password_hash, created_at) VALUES (:reg, :fn, :sn, :ln, :dob, :gender, :educationLevel, :email, :phone, :pass, datetime("now"))');
                                $insert->execute([
                                    ':reg' => $reg,
                                    ':fn' => $fn,
                                    ':sn' => $sn,
                                    ':ln' => $ln,
                                    ':dob' => $dob,
                                    ':gender' => $gender,
                                    ':educationLevel' => $educationLevel,
                                    ':email' => $email,
                                    ':phone' => $phone,
                                    ':pass' => password_hash($password !== '' ? $password : 'changeme123', PASSWORD_DEFAULT),
                                ]);
                                $imported++;
                            }
                        }
                        fclose($handle);
                        $pdo->commit();
                        $message = "Template imported successfully. {$imported} records updated or added.";
                    } else {
                        $pdo->rollBack();
                        $errorMessage = 'Unable to open uploaded file for import.';
                    }
                }
            }
        }

        if ($action === 'delete' && $studentId) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM academic_performance WHERE user_id = :id')->execute([':id' => $studentId]);
                $pdo->prepare('DELETE FROM bills WHERE user_id = :id')->execute([':id' => $studentId]);
                $pdo->prepare('DELETE FROM parents WHERE user_id = :id')->execute([':id' => $studentId]);
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $studentId]);
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            header('Location: admin.php?message=' . urlencode('Student deleted successfully.'));
            exit;
        }

        if ($action === 'add' || $action === 'edit') {
            $firstName = trim($_POST['first_name'] ?? '');
            $secondName = trim($_POST['second_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $dob = trim($_POST['dob'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $educationLevel = trim($_POST['education_level'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($firstName === '' || $lastName === '' || $educationLevel === '' || $email === '') {
                $errorMessage = 'First name, last name, education level and email are required.';
            } else {
                if ($action === 'add') {
                    $classCode = getClassCode($educationLevel);
                    $year = date('Y');
                    $prefix = "HUM/{$year}/{$classCode}";
                    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE registration_number LIKE :prefix');
                    $countStmt->execute([':prefix' => $prefix . '/%']);
                    $sequence = (int) $countStmt->fetchColumn() + 1;
                    $registrationNumber = sprintf('%s/%04d', $prefix, $sequence);
                    $passwordHash = password_hash($password ?: 'changeme123', PASSWORD_DEFAULT);

                    $insert = $pdo->prepare('INSERT INTO users (registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone, password_hash, created_at) VALUES (:reg, :fn, :sn, :ln, :dob, :gender, :education_level, :email, :phone, :pass, datetime(\'now\'))');
                    $insert->execute([
                        ':reg' => $registrationNumber,
                        ':fn' => $firstName,
                        ':sn' => $secondName,
                        ':ln' => $lastName,
                        ':dob' => $dob,
                        ':gender' => $gender,
                        ':education_level' => $educationLevel,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':pass' => $passwordHash,
                    ]);

                    $newId = $pdo->lastInsertId();
                    
                    // Send confirmation email with credentials
                    $plainPassword = $password ?: 'changeme123';
                    $emailSent = sendStudentCredentialsEmail($email, $firstName, $lastName, $registrationNumber, $plainPassword);
                    $emailMessage = $emailSent ? ' Email confirmation sent to ' . $email . '.' : ' (Note: Email sending encountered an issue, but student was created successfully.)';
                    
                    header('Location: admin.php?student_id=' . $newId . '&message=' . urlencode('Student added successfully.' . $emailMessage));
                    exit;
                }

                if ($action === 'edit' && $studentId) {
                    $updateSql = 'UPDATE users SET first_name = :fn, second_name = :sn, last_name = :ln, dob = :dob, gender = :gender, education_level = :education_level, email = :email, phone = :phone';
                    $params = [
                        ':fn' => $firstName,
                        ':sn' => $secondName,
                        ':ln' => $lastName,
                        ':dob' => $dob,
                        ':gender' => $gender,
                        ':education_level' => $educationLevel,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':id' => $studentId,
                    ];

                    if ($password !== '') {
                        $updateSql .= ', password_hash = :pass';
                        $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $updateSql .= ' WHERE id = :id';

                    $update = $pdo->prepare($updateSql);
                    $update->execute($params);
                    header('Location: admin.php?student_id=' . $studentId . '&message=' . urlencode('Student details updated successfully.'));
                    exit;
                }
            }
        }
    }

    $classLevels = [
        'Baby Class','Nursery','Pre-Unit','Class 1','Class 2','Class 3','Class 4','Class 5','Class 6'
    ];
    $studentCounts = array_fill_keys($classLevels, 0);
    $teacherCounts = array_fill_keys($classLevels, 0);
    $teacherLists = array_fill_keys($classLevels, '');

    $stmt = $pdo->query('SELECT id, registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone, created_at FROM users ORDER BY registration_number ASC');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $studentOverviewRows = [];
    $studentSubjectDetailsMap = [];
    
    foreach ($students as $student) {
        $level = $student['education_level'] ?? '';
        if ($level !== '' && isset($studentCounts[$level])) {
            $studentCounts[$level]++;
        }

        $studentPerformanceRow = null;
        $perfStmt = $pdo->prepare('SELECT grade, total, number_of_subjects FROM academic_performance WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $perfStmt->execute([':user_id' => $student['id']]);
        $studentPerformanceRow = $perfStmt->fetch(PDO::FETCH_ASSOC);

        $subjectList = [];
        foreach (getSubjectGroups($student['education_level'] ?? '') as $groupSubjects) {
            foreach ($groupSubjects as $subject) {
                $subjectList[] = $subject;
            }
        }

        // Fetch subject performance details for each student
        $subjPerfStmt = $pdo->prepare('SELECT id, subject, marks, grade, remarks FROM subject_performance WHERE user_id = :user_id ORDER BY subject ASC');
        $subjPerfStmt->execute([':user_id' => $student['id']]);
        $subjectPerformanceDetails = $subjPerfStmt->fetchAll(PDO::FETCH_ASSOC);
        $studentSubjectDetailsMap[$student['id']] = $subjectPerformanceDetails;

        $studentOverviewRows[] = [
            'id' => $student['id'],
            'name' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
            'class_name' => $student['education_level'] ?? '',
            'registration_number' => $student['registration_number'] ?? '',
            'subjects' => $subjectList,
            'marks' => (string)($studentPerformanceRow['total'] ?? '0'),
            'grade' => $studentPerformanceRow['grade'] ?? 'N/A',
            'avatar_initials' => strtoupper(substr(($student['first_name'] ?? 'S'), 0, 1) . substr(($student['last_name'] ?? 'S'), 0, 1)),
            'subject_performance_count' => count($subjectPerformanceDetails),
        ];
    }

    $teacherStmt = $pdo->query('SELECT education_level, COUNT(*) AS count, GROUP_CONCAT(first_name || " " || last_name, ", ") AS teachers FROM teachers GROUP BY education_level');
    $teacherRows = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($teacherRows as $row) {
        $level = $row['education_level'] ?? '';
        if ($level !== '' && isset($teacherCounts[$level])) {
            $teacherCounts[$level] = (int) $row['count'];
            $teacherLists[$level] = $row['teachers'] ?? '';
        }
    }

    $teacherListStmt = $pdo->query('SELECT id, first_name, second_name, last_name, education_level, subject, email, phone, image_path, created_at FROM teachers ORDER BY last_name ASC, first_name ASC');
    $teachers = $teacherListStmt->fetchAll(PDO::FETCH_ASSOC);

    $studentPerformance = null;
    $studentBills = [];
    $studentSubjectRows = [];
    $studentSubjectPerformance = [];
    $teacherList = [];
    if ($selectedStudent) {
        $perfStmt = $pdo->prepare('SELECT id, user_id, number_of_subjects, grade, total, average, position, remarks, remarks_director FROM academic_performance WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $perfStmt->execute([':user_id' => $selectedStudent['id']]);
        $studentPerformance = $perfStmt->fetch(PDO::FETCH_ASSOC);

        $billStmt = $pdo->prepare('SELECT id, type, months, account_number, reference_number, status FROM bills WHERE user_id = :user_id ORDER BY id');
        $billStmt->execute([':user_id' => $selectedStudent['id']]);
        $studentBills = $billStmt->fetchAll(PDO::FETCH_ASSOC);

        $subjPerfStmt = $pdo->prepare('SELECT id, subject, marks, grade, remarks FROM subject_performance WHERE user_id = :user_id ORDER BY subject ASC');
        $subjPerfStmt->execute([':user_id' => $selectedStudent['id']]);
        $studentSubjectPerformance = $subjPerfStmt->fetchAll(PDO::FETCH_ASSOC);

        $teacherStmt = $pdo->prepare('SELECT first_name, last_name, education_level, subject FROM teachers ORDER BY last_name, first_name');
        $teacherStmt->execute();
        $teacherList = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);

        $studentSubjectRows = getSubjectGroups($selectedStudent['education_level'] ?? '');
    }

    $totalStudents = count($students);
    $totalTeachers = count($teachers);
    $totalClasses = count($classLevels);

    if ($isTeacherView) {
        if ($selectedTeacherId) {
            $selTeacherStmt = $pdo->prepare('SELECT id, first_name, second_name, last_name, education_level, subject, email, phone, image_path, created_at FROM teachers WHERE id = :id LIMIT 1');
            $selTeacherStmt->execute([':id' => $selectedTeacherId]);
            $selectedTeacher = $selTeacherStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$selectedTeacher && !empty($teachers)) {
            $selectedTeacher = $teachers[0];
        }
    } else {
        if ($selectedId) {
            $selStmt = $pdo->prepare('SELECT id, registration_number, first_name, second_name, last_name, dob, gender, education_level, email, phone, created_at FROM users WHERE id = :id LIMIT 1');
            $selStmt->execute([':id' => $selectedId]);
            $selectedStudent = $selStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$selectedStudent && !empty($students)) {
            $selectedStudent = $students[0];
        }
    }

    if (isset($_SESSION['flash_message'])) {
        $message = trim($_SESSION['flash_message']);
        unset($_SESSION['flash_message']);
    } elseif (isset($_GET['message'])) {
        $message = trim($_GET['message']);
    }
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin - Humblekid</title>
    <link rel="stylesheet" href="style-admin.css?v=20260716">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="top-nav">
        <a class="logo" href="admin.php?view=student">
            <i class="fas fa-graduation-cap"></i>
            <span>HumbleKid</span>
        </a>
        <ul>
            <li><a href="admin.php?view=student">Overview</a></li>
            <li><a href="admin.php?view=student">Students</a></li>
            <li><a href="admin.php?view=classes">Classes</a></li>
            <li><a href="admin.php?view=teachers">Teachers</a></li>
            <li><a href="admin.php?view=staff">Staff</a></li>
            <li><a href="admin.php?view=payment">Payment</a></li>
            <li><a href="admin.php?view=subjects">Subjects</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>ko
    <main class="admin-main">
        <div class="page-header">
            <div class="page-title-group">
                <div class="page-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Console</span>
                </div>
                <h1>
                    <?php if ($isClassesView): ?>Classes Overview<?php elseif ($isTeacherView): ?>Teachers Dashboard<?php else: ?>Students Dashboard<?php endif; ?>
                </h1>
                <p>
                    <?php if ($isClassesView): ?>See children and teacher counts for each class.<?php elseif ($isTeacherView): ?>Click a teacher from the left sidebar to see details on the right.<?php else: ?>Click a student from the left sidebar to see details on the right.<?php endif; ?>
                </p>
            </div>
        </div>

        <div class="hero-card">
            <div class="hero-copy">
                <div class="hero-badge">Live operations center</div>
                <h2>Administration Hub</h2>
                <p>Track students, teachers, classes and school operations in one polished workspace.</p>
                <div class="hero-highlights">
                    <span><i class="fas fa-users"></i> Student records</span>
                    <span><i class="fas fa-chalkboard-teacher"></i> Teacher access</span>
                    <span><i class="fas fa-chart-line"></i> Class insights</span>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat-chip">
                    <strong><?php echo esc($totalStudents); ?></strong>
                    <span>Students</span>
                </div>
                <div class="stat-chip">
                    <strong><?php echo esc($totalTeachers); ?></strong>
                    <span>Teachers</span>
                </div>
                <div class="stat-chip">
                    <strong><?php echo esc($totalClasses); ?></strong>
                    <span>Classes</span>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message-box"><?php echo esc($message); ?></div>
            <script>
                window.alert(<?php echo json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            </script>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="error-box"><?php echo esc($errorMessage); ?></div>
        <?php endif; ?>

        <div class="panel" style="margin-bottom:20px;">
            <h2>Export Current View</h2>
            <p>Download the template file prefilled with the current data for the selected navigation tab.</p>
            <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <a class="button" href="admin.php?view=<?php echo esc($view); ?>&download_template=1">Download .xls Template</a>
                <?php if ($isStudentView): ?>
                    <form action="admin.php?view=student" method="post" enctype="multipart/form-data" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0;">
                        <input type="file" name="student_template" accept=".xls,.csv,.txt" style="border:1px solid #cbd5e1; padding:10px; border-radius:10px; background:#fff;" required>
                        <input type="hidden" name="action" value="import_students">
                        <button type="submit" class="button-secondary">Upload filled template</button>
                    </form>
                <?php elseif ($isTeacherView): ?>
                    <form action="admin.php?view=teachers" method="post" enctype="multipart/form-data" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0;">
                        <input type="file" name="teacher_template" accept=".xls,.csv,.txt" style="border:1px solid #cbd5e1; padding:10px; border-radius:10px; background:#fff;" required>
                        <input type="hidden" name="action" value="import_teachers">
                        <button type="submit" class="button-secondary">Upload filled template</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isStudentView && !empty($studentOverviewRows)): ?>
            <div class="panel" style="margin-bottom:20px;">
                <h2>Student Performance Summary</h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Student</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Class</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subjects</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Reg Number</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Marks</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Grade</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentOverviewRows as $overviewRow): ?>
                                <tr>
                                    <td style="border:1px solid #e2e8f0; padding:10px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg, #f97316, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;">
                                                <?php echo esc($overviewRow['avatar_initials']); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;"><?php echo esc($overviewRow['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($overviewRow['class_name']); ?></td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;">
                                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                            <?php foreach ($overviewRow['subjects'] as $subject): ?>
                                                <span style="padding:4px 8px; border-radius:999px; background:#eff6ff; color:#2563eb; font-size:0.82em;"><?php echo esc($subject); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($overviewRow['registration_number']); ?></td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($overviewRow['marks']); ?></td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($overviewRow['grade']); ?></td>
                                    <td style="border:1px solid #e2e8f0; padding:10px;">
                                        <a class="button-secondary" href="admin.php?student_id=<?php echo esc($overviewRow['id']); ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isStudentView && !empty($studentOverviewRows)): ?>
            <div class="panel" style="margin-bottom:20px;">
                <h2>Subject Performance Details by User</h2>
                <p style="margin:0 0 12px 0; color:#64748b;">Detailed subject-wise marks and grades for all registered students.</p>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Student</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Reg #</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Class</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Marks</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Grade</th>
                                <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentOverviewRows as $student): ?>
                                <?php $subjectPerfs = $studentSubjectDetailsMap[$student['id']] ?? []; ?>
                                <?php if (!empty($subjectPerfs)): ?>
                                    <?php foreach ($subjectPerfs as $idx => $subjPerf): ?>
                                        <tr>
                                            <?php if ($idx === 0): ?>
                                                <td style="border:1px solid #e2e8f0; padding:10px; font-weight:600;" rowspan="<?php echo count($subjectPerfs); ?>">
                                                    <div style="display:flex; align-items:center; gap:10px;">
                                                        <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg, #f97316, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">
                                                            <?php echo esc($student['avatar_initials']); ?>
                                                        </div>
                                                        <span><?php echo esc($student['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;" rowspan="<?php echo count($subjectPerfs); ?>"><?php echo esc($student['registration_number']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;" rowspan="<?php echo count($subjectPerfs); ?>"><?php echo esc($student['class_name']); ?></td>
                                            <?php endif; ?>
                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($subjPerf['subject']); ?></td>
                                            <td style="border:1px solid #e2e8f0; padding:10px; text-align:center;">
                                                <?php if ($subjPerf['marks'] !== null): ?>
                                                    <span style="padding:4px 8px; background:#dbeafe; color:#1e40af; border-radius:3px; font-weight:600;"><?php echo esc($subjPerf['marks']); ?></span>
                                                <?php else: ?>
                                                    <span style="color:#999;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="border:1px solid #e2e8f0; padding:10px; text-align:center;">
                                                <?php if ($subjPerf['grade']): ?>
                                                    <span style="padding:4px 8px; background:<?php echo match($subjPerf['grade']) { 'A' => '#dcfce7', 'B' => '#e0e7ff', 'C' => '#fef3c7', 'D' => '#fed7aa', 'F' => '#fee2e2', default => '#f3f4f6' }; ?>; color:<?php echo match($subjPerf['grade']) { 'A' => '#166534', 'B' => '#3730a3', 'C' => '#92400e', 'D' => '#9a3412', 'F' => '#991b1b', default => '#374151' }; ?>; border-radius:3px; font-weight:600;"><?php echo esc($subjPerf['grade']); ?></span>
                                                <?php else: ?>
                                                    <span style="color:#999;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="border:1px solid #e2e8f0; padding:10px; font-size:13px;"><?php echo esc($subjPerf['remarks'] ?? '--'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="border:1px solid #e2e8f0; padding:10px;">
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg, #f97316, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">
                                                    <?php echo esc($student['avatar_initials']); ?>
                                                </div>
                                                <span><?php echo esc($student['name']); ?></span>
                                            </div>
                                        </td>
                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($student['registration_number']); ?></td>
                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($student['class_name']); ?></td>
                                        <td colspan="4" style="border:1px solid #e2e8f0; padding:10px; color:#999; font-style:italic;">No subject performance data recorded</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-grid">
            <aside class="sidebar">
                <div class="sidebar-search">
                    <input id="studentSearch" type="search" placeholder="Search by name or registration">
                </div>
                <div class="student-list" id="studentList">
                    <?php if ($isTeacherView): ?>
                        <?php if (empty($teachers)): ?>
                            <p>No teachers found.</p>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <?php $isActive = $selectedTeacher && $selectedTeacher['id'] == $teacher['id']; ?>
                                <a class="student-item<?php echo $isActive ? ' active' : ''; ?>" href="admin.php?view=teachers&teacher_id=<?php echo esc($teacher['id']); ?>">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if (!empty($teacher['image_path'])): ?>
                                            <img src="<?php echo esc(resolveUploadUrl($teacher['image_path'], $_SERVER['SCRIPT_NAME'] ?? '')); ?>" alt="<?php echo esc($teacher['first_name'] . ' ' . $teacher['last_name']); ?>" style="width:42px;height:42px;object-fit:cover;border-radius:50%;border:1px solid #e2e8f0;">
                                        <?php else: ?>
                                            <div style="width:42px;height:42px;border-radius:50%;background:#edf2f7;color:#2d3748;display:flex;align-items:center;justify-content:center;font-weight:700;">
                                                <?php echo esc(substr($teacher['first_name'],0,1) . substr($teacher['last_name'],0,1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="meta">
                                            <strong><?php echo esc($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                            <small><?php echo esc($teacher['subject']); ?></small>
                                        </div>
                                    </div>
                                    <small><?php echo esc($teacher['education_level']); ?> • <?php echo esc($teacher['email']); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php elseif ($isStudentView): ?>
                        <?php if (empty($students)): ?>
                            <div class="empty-state empty-state-inline">
                                <div class="empty-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h4>No students yet</h4>
                                <p>Start by adding your first student record.</p>
                                <div class="empty-actions">
                                    <a class="button" href="admin.php?action=add"><i class="fas fa-plus"></i> Add New Student</a>
                                    <a class="button-secondary" href="admin.php?view=student&download_template=1"><i class="fas fa-file-download"></i> Download Template</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <?php $isActive = $selectedStudent && $selectedStudent['id'] == $student['id']; ?>
                                <a class="student-item<?php echo $isActive ? ' active' : ''; ?>" href="admin.php?student_id=<?php echo esc($student['id']); ?>">
                                    <div class="meta">
                                        <strong><?php echo esc($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        <small><?php echo esc($student['registration_number']); ?></small>
                                    </div>
                                    <small><?php echo esc($student['education_level']); ?> • <?php echo esc($student['gender']); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>This section does not use the sidebar list. Use the main panel to view the selected feature.</p>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="details">
                <?php $isAdd = isset($_GET['action']) && $_GET['action'] === 'add'; ?>
                <?php $isEdit = isset($_GET['action']) && $_GET['action'] === 'edit'; ?>

                <?php if ($isAdd || $isEdit): ?>
                    <div class="panel">
                        <h2><?php echo $isAdd ? 'Add New Student' : 'Edit Student Details'; ?></h2>
                        <form method="post" action="admin.php">
                            <input type="hidden" name="action" value="<?php echo $isAdd ? 'add' : 'edit'; ?>">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id'] ?? ''); ?>">
                            <?php endif; ?>
                            <div class="field-row">
                                <label>First Name
                                    <input type="text" name="first_name" value="<?php echo esc($selectedStudent['first_name'] ?? ''); ?>" required>
                                </label>
                                <label>Second Name
                                    <input type="text" name="second_name" value="<?php echo esc($selectedStudent['second_name'] ?? ''); ?>">
                                </label>
                            </div>
                            <div class="field-row">
                                <label>Last Name
                                    <input type="text" name="last_name" value="<?php echo esc($selectedStudent['last_name'] ?? ''); ?>" required>
                                </label>
                                <label>Date of Birth
                                    <input type="date" name="dob" value="<?php echo esc($selectedStudent['dob'] ?? ''); ?>">
                                </label>
                            </div>
                            <div class="field-row">
                                <label>Gender
                                    <select name="gender">
                                        <option value="">Select gender</option>
                                        <option value="Male"<?php echo (isset($selectedStudent['gender']) && $selectedStudent['gender'] === 'Male') ? ' selected' : ''; ?>>Male</option>
                                        <option value="Female"<?php echo (isset($selectedStudent['gender']) && $selectedStudent['gender'] === 'Female') ? ' selected' : ''; ?>>Female</option>
                                    </select>
                                </label>
                                <label>Education Level
                                    <select name="education_level" required>
                                        <option value="">Select class</option>
                                        <?php foreach (['Baby Class','Nursery','Pre-Unit','Class 1','Class 2','Class 3','Class 4','Class 5','Class 6'] as $level): ?>
                                            <option value="<?php echo esc($level); ?>"<?php echo (isset($selectedStudent['education_level']) && $selectedStudent['education_level'] === $level) ? ' selected' : ''; ?>><?php echo esc($level); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="field-row">
                                <label>Email
                                    <input type="email" name="email" value="<?php echo esc($selectedStudent['email'] ?? ''); ?>" required>
                                </label>
                                <label>Phone
                                    <input type="tel" name="phone" value="<?php echo esc($selectedStudent['phone'] ?? ''); ?>">
                                </label>
                            </div>
                            <div class="field-row">
                                <label>Password
                                    <input type="password" name="password" placeholder="Leave empty to keep current password">
                                </label>
                                <div></div>
                            </div>
                            <div class="actions">
                                <button type="submit"><?php echo $isAdd ? 'Create Student' : 'Save Changes'; ?></button>
                                <a href="admin.php" class="button-secondary">Cancel</a>
                            </div>
                        </form>

                        <?php if ($isEdit && $selectedStudent): ?>
                            <div class="panel" style="margin-top:16px;">
                                <h3>Subject Enrollment & Performance Summary</h3>
                                <?php 
                                    $totalSubjects = count($studentSubjectRows > 0 ? array_merge(...$studentSubjectRows) : []);
                                    $recordedSubjects = count($studentSubjectPerformance ?? []);
                                    $avgMarks = 0;
                                    if (!empty($studentSubjectPerformance)) {
                                        $totalMarks = array_reduce($studentSubjectPerformance, function($carry, $item) {
                                            return $carry + ((int)($item['marks'] ?? 0));
                                        }, 0);
                                        $avgMarks = $recordedSubjects > 0 ? round($totalMarks / $recordedSubjects, 2) : 0;
                                    }
                                ?>
                                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px;">
                                    <div style="background:#e0f2fe; border-left:4px solid #0284c7; padding:12px; border-radius:4px;">
                                        <p style="margin:0; font-size:12px; color:#0c4a6e; font-weight:600;">Total Subjects</p>
                                        <p style="margin:4px 0 0; font-size:24px; font-weight:bold; color:#0c4a6e;"><?php echo $totalSubjects; ?></p>
                                    </div>
                                    <div style="background:#dcfce7; border-left:4px solid #16a34a; padding:12px; border-radius:4px;">
                                        <p style="margin:0; font-size:12px; color:#15803d; font-weight:600;">Recorded Subjects</p>
                                        <p style="margin:4px 0 0; font-size:24px; font-weight:bold; color:#15803d;"><?php echo $recordedSubjects; ?></p>
                                    </div>
                                    <div style="background:#fef3c7; border-left:4px solid #ca8a04; padding:12px; border-radius:4px;">
                                        <p style="margin:0; font-size:12px; color:#b45309; font-weight:600;">Avg Marks</p>
                                        <p style="margin:4px 0 0; font-size:24px; font-weight:bold; color:#b45309;"><?php echo $avgMarks > 0 ? number_format($avgMarks, 1) : '--'; ?></p>
                                    </div>
                                    <div style="background:#f3e8ff; border-left:4px solid #9333ea; padding:12px; border-radius:4px;">
                                        <p style="margin:0; font-size:12px; color:#6b21a8; font-weight:600;">Pending Subjects</p>
                                        <p style="margin:4px 0 0; font-size:24px; font-weight:bold; color:#6b21a8;"><?php echo max(0, $totalSubjects - $recordedSubjects); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="panel" style="margin-top:16px;">
                                <h3>Academic Performance</h3>
                                <?php if (!empty($studentPerformance)): ?>
                                    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px; margin-bottom:16px;">
                                        <div style="background:#f7fafc; padding:12px; border-radius:4px;">
                                            <p style="margin:0; font-size:12px; color:#64748b;">Overall Grade</p>
                                            <p style="margin:4px 0 0; font-size:20px; font-weight:bold; color:#1e293b;"><?php echo esc($studentPerformance['grade'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div style="background:#f7fafc; padding:12px; border-radius:4px;">
                                            <p style="margin:0; font-size:12px; color:#64748b;">Average Score</p>
                                            <p style="margin:4px 0 0; font-size:20px; font-weight:bold; color:#1e293b;"><?php echo esc(number_format((float)($studentPerformance['average'] ?? 0), 2)); ?></p>
                                        </div>
                                        <div style="background:#f7fafc; padding:12px; border-radius:4px;">
                                            <p style="margin:0; font-size:12px; color:#64748b;">Total Marks</p>
                                            <p style="margin:4px 0 0; font-size:20px; font-weight:bold; color:#1e293b;"><?php echo esc($studentPerformance['total'] ?? '0'); ?></p>
                                        </div>
                                        <div style="background:#f7fafc; padding:12px; border-radius:4px;">
                                            <p style="margin:0; font-size:12px; color:#64748b;">Class Position</p>
                                            <p style="margin:4px 0 0; font-size:20px; font-weight:bold; color:#1e293b;"><?php echo esc($studentPerformance['position'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($studentPerformance['remarks_director'])): ?>
                                        <div style="background:#fffbeb; border-left:4px solid #f59e0b; padding:12px; margin-bottom:16px; border-radius:2px;">
                                            <p style="margin:0; font-size:12px; color:#92400e; font-weight:600;">Director's Remarks</p>
                                            <p style="margin:4px 0 0; color:#78350f;"><?php echo esc($studentPerformance['remarks_director']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (!empty($studentSubjectRows)): ?>
                                    <div style="overflow-x:auto; margin-top:12px;">
                                        <table style="width:100%; border-collapse:collapse;">
                                            <thead>
                                                <tr>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Marks</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Grade</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Teacher</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($studentSubjectRows as $className => $subjects): ?>
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <?php $teacherName = getSubjectTeacherName($subject, $selectedStudent['education_level'] ?? '', $teacherList); ?>
                                                        <tr>
                                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($subject); ?></td>
                                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($studentPerformance['total'] ?? '--'); ?></td>
                                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($studentPerformance['grade'] ?? '--'); ?></td>
                                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($teacherName); ?></td>
                                                            <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($studentPerformance['remarks'] ?? 'No remarks yet'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p style="margin:8px 0 0; color:#64748b;">No subject list available for this class yet.</p>
                                <?php endif; ?>
                            </div>

                            <div class="panel" style="margin-top:16px;">
                                <h3>Billing Information</h3>
                                <p style="margin:8px 0 12px; color:#64748b;">Fees = 460,000 Tshs for 3 months, Transport = 150,000 Tshs for 3 months, Uniform = 120,000 Tshs.</p>
                                
                                <?php
                                    $totalDue = 0;
                                    $totalPaid = 0;
                                    $paidCount = 0;
                                    $pendingCount = 0;
                                    
                                    if (!empty($studentBills)): 
                                        foreach ($studentBills as $bill):
                                            $amount = (float)getBillAmountLabel($bill['type'] ?? '', $bill['months'] ?? '');
                                            $totalDue += $amount;
                                            if (strtolower($bill['status'] ?? '') === 'paid'):
                                                $totalPaid += $amount;
                                                $paidCount++;
                                            else:
                                                $pendingCount++;
                                            endif;
                                        endforeach;
                                    endif;
                                    $balance = $totalDue - $totalPaid;
                                ?>
                                
                                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;">
                                    <div style="background:#e0f2fe; padding:12px; border-radius:4px; border-left:4px solid #0284c7;">
                                        <p style="margin:0; font-size:12px; color:#0c4a6e;">Total Amount Due</p>
                                        <p style="margin:4px 0 0; font-size:18px; font-weight:bold; color:#0c4a6e;"><?php echo number_format($totalDue, 0); ?> Tshs</p>
                                    </div>
                                    <div style="background:#dcfce7; padding:12px; border-radius:4px; border-left:4px solid #16a34a;">
                                        <p style="margin:0; font-size:12px; color:#15803d;">Amount Paid</p>
                                        <p style="margin:4px 0 0; font-size:18px; font-weight:bold; color:#15803d;"><?php echo number_format($totalPaid, 0); ?> Tshs</p>
                                    </div>
                                    <div style="background:<?php echo $balance > 0 ? '#fee2e2' : '#dcfce7'; ?>; padding:12px; border-radius:4px; border-left:4px solid <?php echo $balance > 0 ? '#dc2626' : '#16a34a'; ?>;">
                                        <p style="margin:0; font-size:12px; color:<?php echo $balance > 0 ? '#7f1d1d' : '#15803d'; ?>;">Outstanding Balance</p>
                                        <p style="margin:4px 0 0; font-size:18px; font-weight:bold; color:<?php echo $balance > 0 ? '#7f1d1d' : '#15803d'; ?>"><?php echo number_format($balance, 0); ?> Tshs</p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($studentBills)): ?>
                                    <div style="overflow-x:auto;">
                                        <table style="width:100%; border-collapse:collapse;">
                                            <thead>
                                                <tr>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Bill Type</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Months</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Amount</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Account No.</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Reference No.</th>
                                                    <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($studentBills as $bill): ?>
                                                    <tr>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['type'] ?? ''); ?></td>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['months'] ?? ''); ?></td>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc(getBillAmountLabel($bill['type'] ?? '', $bill['months'] ?? '')); ?> Tshs</td>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['account_number'] ?? 'N/A'); ?></td>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['reference_number'] ?? 'N/A'); ?></td>
                                                        <td style="border:1px solid #e2e8f0; padding:10px;">
                                                            <span style="display:inline-block; padding:4px 8px; border-radius:3px; font-size:12px; font-weight:600; background:<?php echo strtolower($bill['status'] ?? '') === 'paid' ? '#d1fae5' : '#fef3c7'; ?>; color:<?php echo strtolower($bill['status'] ?? '') === 'paid' ? '#065f46' : '#92400e'; ?>;">
                                                                <?php echo esc($bill['status'] ?? 'Pending'); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p style="margin:8px 0 0; color:#64748b;">No billing records available yet.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isClassesView): ?>
                    <div class="panel">
                        <h2>Class Summary</h2>
                        <p>Number of students and teachers assigned to each class.</p>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse; margin-top:16px;">
                                <thead>
                                    <tr>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Class</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Children</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Teachers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classLevels as $level): ?>
                                        <tr>
                                            <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($level); ?></td>
                                            <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($studentCounts[$level]); ?></td>
                                            <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacherCounts[$level]); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif ($isTeacherView): ?>
                    <?php $isTeacherAdd = isset($_GET['action']) && $_GET['action'] === 'add_teacher'; ?>
                    <?php $isTeacherEdit = isset($_GET['action']) && $_GET['action'] === 'edit_teacher'; ?>
                    <?php if ($isTeacherAdd || $isTeacherEdit): ?>
                        <div class="panel">
                            <h2><?php echo $isTeacherAdd ? 'Add New Teacher' : 'Edit Teacher Details'; ?></h2>
                            <form method="post" action="admin.php?view=teachers" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="<?php echo $isTeacherAdd ? 'add_teacher' : 'edit_teacher'; ?>">
                                <?php if ($isTeacherEdit): ?>
                                    <input type="hidden" name="teacher_id" value="<?php echo esc($selectedTeacher['id'] ?? ''); ?>">
                                <?php endif; ?>
                                <div class="field-row">
                                    <label>First Name
                                        <input type="text" name="first_name" value="<?php echo esc($selectedTeacher['first_name'] ?? ''); ?>" required>
                                    </label>
                                    <label>Second Name
                                        <input type="text" name="second_name" value="<?php echo esc($selectedTeacher['second_name'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Last Name
                                        <input type="text" name="last_name" value="<?php echo esc($selectedTeacher['last_name'] ?? ''); ?>" required>
                                    </label>
                                    <label>Education Level
                                        <select name="education_level" required>
                                            <option value="">Select class</option>
                                            <?php foreach ($classLevels as $level): ?>
                                                <option value="<?php echo esc($level); ?>"<?php echo (isset($selectedTeacher['education_level']) && $selectedTeacher['education_level'] === $level) ? ' selected' : ''; ?>><?php echo esc($level); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Subject
                                        <input type="text" name="subject" value="<?php echo esc($selectedTeacher['subject'] ?? ''); ?>">
                                    </label>
                                    <label>Email
                                        <input type="email" name="email" value="<?php echo esc($selectedTeacher['email'] ?? ''); ?>" required>
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Phone
                                        <input type="tel" name="phone" value="<?php echo esc($selectedTeacher['phone'] ?? ''); ?>">
                                    </label>
                                    <label>Teacher Image
                                        <input type="file" name="teacher_image" accept="image/*">
                                    </label>
                                </div>
                                <div class="actions">
                                    <button type="submit"><?php echo $isTeacherAdd ? 'Create Teacher' : 'Save Changes'; ?></button>
                                    <a href="admin.php?view=teachers" class="button-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                    <div class="panel">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                            <div>
                                <h2>Teacher Directory</h2>
                                <p>Manage current teachers, class assignments, subjects, and contact details from one table.</p>
                            </div>
                            <div class="actions" style="margin-top:0;">
                                <a href="admin.php?view=teachers&action=add_teacher" class="button">Add Teacher</a>
                            </div>
                        </div>
                        <div style="overflow-x:auto; margin-top:16px;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Photo</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Name</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Class</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Subject</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Email</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Phone</th>
                                        <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; background:#f7fafc;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teachers)): ?>
                                        <tr>
                                            <td colspan="7" style="border:1px solid #e2e8f0; padding:16px; text-align:center; color:#64748b;">No teachers found yet. Add your first teacher to get started.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <tr>
                                                <td style="border:1px solid #e2e8f0; padding:12px;">
                                                    <?php if (!empty($teacher['image_path'])): ?>
                                                        <img src="<?php echo esc(resolveUploadUrl($teacher['image_path'], $_SERVER['SCRIPT_NAME'] ?? '')); ?>" alt="<?php echo esc($teacher['first_name'] . ' ' . $teacher['last_name']); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:50%;border:1px solid #e2e8f0;">
                                                    <?php else: ?>
                                                        <div style="width:44px;height:44px;border-radius:50%;background:#edf2f7;color:#2d3748;display:flex;align-items:center;justify-content:center;font-weight:700;">
                                                            <?php echo esc(substr($teacher['first_name'],0,1) . substr($teacher['last_name'],0,1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacher['education_level']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacher['subject']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacher['email']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;"><?php echo esc($teacher['phone']); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:12px;">
                                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                        <a class="button-secondary" href="admin.php?view=teachers&teacher_id=<?php echo esc($teacher['id']); ?>&action=edit_teacher">Edit</a>
                                                        <form method="post" action="admin.php" style="display:inline-block; margin:0;">
                                                            <input type="hidden" name="action" value="delete_teacher">
                                                            <input type="hidden" name="teacher_id" value="<?php echo esc($teacher['id']); ?>">
                                                            <button type="submit" onclick="return confirm('Delete this teacher?');">Remove</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($teachers) && $selectedTeacher): ?>
                        <div class="panel" style="margin-top:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:16px;">
                                    <?php if (!empty($selectedTeacher['image_path'])): ?>
                                        <img src="<?php echo esc(resolveUploadUrl($selectedTeacher['image_path'], $_SERVER['SCRIPT_NAME'] ?? '')); ?>" alt="Teacher photo" style="width:96px;height:96px;object-fit:cover;border-radius:18px;border:1px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div style="width:96px;height:96px;border-radius:18px;background:#edf2f7;color:#2d3748;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;border:1px solid #e2e8f0;">
                                            <?php echo esc(substr($selectedTeacher['first_name'],0,1) . substr($selectedTeacher['last_name'],0,1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h2>Teacher Details</h2>
                                        <p><?php echo esc($selectedTeacher['first_name'] . ' ' . $selectedTeacher['second_name'] . ' ' . $selectedTeacher['last_name']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <strong>Subject</strong>
                                    <span><?php echo esc($selectedTeacher['subject']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <strong>Class</strong>
                                    <span><?php echo esc($selectedTeacher['education_level']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <strong>Email</strong>
                                    <span><?php echo esc($selectedTeacher['email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <strong>Phone</strong>
                                    <span><?php echo esc($selectedTeacher['phone']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <strong>Added At</strong>
                                    <span><?php echo esc($selectedTeacher['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php elseif ($isStaffView): ?>
                    <div class="panel">
                        <h2>Staff Area</h2>
                        <p>Staff management is coming soon. Use this section to manage staff details and assignments.</p>
                    </div>
                <?php elseif ($isPaymentView): ?>
                    <div class="panel">
                        <h2>Payment Management</h2>
                        <p>Payment tracking is coming soon. Use this section to manage fees and receipts.</p>
                    </div>
                <?php elseif ($isSubjectsView): ?>
                    <div class="panel">
                        <h2>Subjects</h2>
                        <p>Subject management is coming soon. Use this section to configure subjects and assignments.</p>
                    </div>
                <?php elseif ($selectedStudent): ?>
                    <div class="panel">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                            <div>
                                <h2>Student Details</h2>
                                <p>Registration: <strong><?php echo esc($selectedStudent['registration_number']); ?></strong></p>
                            </div>
                            <div class="actions" style="margin-top:0;">
                                <a href="admin.php?action=add" class="button-secondary">Add</a>
                                <a href="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>&action=edit" class="button-secondary">Edit</a>
                                <form method="post" action="admin.php" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                    <button type="submit" onclick="return confirm('Delete this student? This cannot be undone.');">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <strong>Full Name</strong>
                                <span><?php echo esc($selectedStudent['first_name'] . ' ' . $selectedStudent['second_name'] . ' ' . $selectedStudent['last_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Class</strong>
                                <span><?php echo esc($selectedStudent['education_level']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Date of Birth</strong>
                                <span><?php echo esc($selectedStudent['dob']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Gender</strong>
                                <span><?php echo esc($selectedStudent['gender']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Email</strong>
                                <span><?php echo esc($selectedStudent['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Phone</strong>
                                <span><?php echo esc($selectedStudent['phone']); ?></span>
                            </div>
                            <div class="detail-row">
                                <strong>Registered At</strong>
                                <span><?php echo esc($selectedStudent['created_at']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:16px;">
                        <h2>Academic Performance</h2>
                        <?php if (!empty($studentSubjectRows)): ?>
                            <div style="overflow-x:auto; margin-bottom:16px;">
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Marks</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Grade</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject Teacher</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $subjectCount = 0; $subjectMarksTotal = 0; ?>
                                        <?php foreach ($studentSubjectRows as $className => $subjects): ?>
                                            <?php foreach ($subjects as $subject): ?>
                                                <?php $subjectCount++; ?>
                                                <?php $teacherName = getSubjectTeacherName($subject, $selectedStudent['education_level'] ?? '', $teacherList); ?>
                                                <?php $subjectMarksValue = $subjectCount > 0 ? number_format(((float)($studentPerformance['total'] ?? 0)) / max(1, count($studentSubjectRows) > 0 ? count($studentSubjectRows) : 1), 2, '.', '') : '0'; ?>
                                                <?php $subjectMarksTotal += (float)$subjectMarksValue; ?>
                                                <tr>
                                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($subject); ?></td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($subjectMarksValue); ?></td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($studentPerformance['grade'] ?? '--'); ?></td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($teacherName); ?></td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($studentPerformance['remarks'] ?? 'No remarks yet'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td style="border:1px solid #e2e8f0; padding:10px; font-weight:700;">Total</td>
                                            <td style="border:1px solid #e2e8f0; padding:10px; font-weight:700;"><?php echo esc(number_format($subjectMarksTotal, 2, '.', '')); ?></td>
                                            <td style="border:1px solid #e2e8f0; padding:10px;"></td>
                                            <td style="border:1px solid #e2e8f0; padding:10px;"></td>
                                            <td style="border:1px solid #e2e8f0; padding:10px;"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($studentPerformance)): ?>
                            <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>">
                                <input type="hidden" name="action" value="save_student_performance">
                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                <input type="hidden" name="performance_id" value="<?php echo esc($studentPerformance['id']); ?>">
                                <div class="field-row">
                                    <label>Grade
                                        <select name="grade">
                                            <option value="A"<?php echo (($studentPerformance['grade'] ?? '') === 'A') ? ' selected' : ''; ?>>A</option>
                                            <option value="B"<?php echo (($studentPerformance['grade'] ?? '') === 'B') ? ' selected' : ''; ?>>B</option>
                                            <option value="C"<?php echo (($studentPerformance['grade'] ?? '') === 'C') ? ' selected' : ''; ?>>C</option>
                                            <option value="D"<?php echo (($studentPerformance['grade'] ?? '') === 'D') ? ' selected' : ''; ?>>D</option>
                                            <option value="F"<?php echo (($studentPerformance['grade'] ?? '') === 'F') ? ' selected' : ''; ?>>F</option>
                                        </select>
                                    </label>
                                    <label>Total Marks
                                        <input type="number" name="total" value="<?php echo esc($studentPerformance['total'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Average
                                        <input type="text" name="average" value="<?php echo esc($studentPerformance['average'] ?? ''); ?>">
                                    </label>
                                    <label>Position
                                        <input type="text" name="position" value="<?php echo esc($studentPerformance['position'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Number of Subjects
                                        <input type="number" name="number_of_subjects" value="<?php echo esc($studentPerformance['number_of_subjects'] ?? '8'); ?>">
                                    </label>
                                    <label>Remarks
                                        <input type="text" name="remarks" value="<?php echo esc($studentPerformance['remarks'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Director Remarks
                                        <input type="text" name="remarks_director" value="<?php echo esc($studentPerformance['remarks_director'] ?? ''); ?>">
                                    </label>
                                    <div></div>
                                </div>
                                <div class="actions">
                                    <button type="submit">Save Academic Performance</button>
                                    <button type="submit" formaction="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>" formmethod="post" onclick="this.form.action='admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>'; this.form.elements.action.value='delete_student_performance'; return confirm('Delete this academic performance record?');">Delete</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>">
                                <input type="hidden" name="action" value="save_student_performance">
                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                <div class="field-row">
                                    <label>Grade
                                        <select name="grade">
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="F">F</option>
                                        </select>
                                    </label>
                                    <label>Total Marks
                                        <input type="number" name="total" value="">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Average
                                        <input type="text" name="average" value="">
                                    </label>
                                    <label>Position
                                        <input type="text" name="position" value="">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Number of Subjects
                                        <input type="number" name="number_of_subjects" value="8">
                                    </label>
                                    <label>Remarks
                                        <input type="text" name="remarks" value="">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Director Remarks
                                        <input type="text" name="remarks_director" value="">
                                    </label>
                                    <div></div>
                                </div>
                                <div class="actions">
                                    <button type="submit">Add Academic Performance</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="panel" style="margin-top:16px;">
                        <h2>Enrolled Subjects with Performance</h2>
                        <p style="margin:0 0 12px 0; color:#64748b;">All subjects for <?php echo esc($selectedStudent['education_level']); ?> with current performance status.</p>
                        
                        <?php if (!empty($studentSubjectRows)): ?>
                            <div style="overflow-x:auto;">
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Subject</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:center; background:#f7fafc;">Marks</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:center; background:#f7fafc;">Grade</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Remarks</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:center; background:#f7fafc;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($studentSubjectRows as $className => $subjects): ?>
                                            <?php foreach ($subjects as $subject): ?>
                                                <?php $subjectPerf = null; ?>
                                                <?php foreach ($studentSubjectPerformance as $perf): ?>
                                                    <?php if (strtolower(trim($perf['subject'])) === strtolower(trim($subject))): ?>
                                                        <?php $subjectPerf = $perf; ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                
                                                <tr style="background:<?php echo $subjectPerf ? '#f0fdf4' : '#fafafa'; ?>;">
                                                    <td style="border:1px solid #e2e8f0; padding:10px; font-weight:600;"><?php echo esc($subject); ?></td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px; text-align:center;">
                                                        <?php if ($subjectPerf && $subjectPerf['marks'] !== null): ?>
                                                            <span style="padding:4px 8px; background:#dbeafe; color:#1e40af; border-radius:3px; font-weight:600; font-size:13px;"><?php echo esc($subjectPerf['marks']); ?></span>
                                                        <?php else: ?>
                                                            <span style="color:#999; font-size:13px;">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px; text-align:center;">
                                                        <?php if ($subjectPerf && $subjectPerf['grade']): ?>
                                                            <span style="padding:4px 8px; background:<?php echo match($subjectPerf['grade']) { 'A' => '#dcfce7', 'B' => '#e0e7ff', 'C' => '#fef3c7', 'D' => '#fed7aa', 'F' => '#fee2e2', default => '#f3f4f6' }; ?>; color:<?php echo match($subjectPerf['grade']) { 'A' => '#166534', 'B' => '#3730a3', 'C' => '#92400e', 'D' => '#9a3412', 'F' => '#991b1b', default => '#374151' }; ?>; border-radius:3px; font-weight:600; font-size:13px;"><?php echo esc($subjectPerf['grade']); ?></span>
                                                        <?php else: ?>
                                                            <span style="color:#999; font-size:13px;">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px; font-size:13px;">
                                                        <?php echo $subjectPerf ? esc($subjectPerf['remarks'] ?? '--') : '--'; ?>
                                                    </td>
                                                    <td style="border:1px solid #e2e8f0; padding:10px; text-align:center;">
                                                        <?php if ($subjectPerf): ?>
                                                            <span style="padding:4px 8px; background:#dcfce7; color:#15803d; border-radius:3px; font-size:12px; font-weight:600;">Recorded</span>
                                                        <?php else: ?>
                                                            <span style="padding:4px 8px; background:#f3e8ff; color:#6b21a8; border-radius:3px; font-size:12px; font-weight:600;">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="color:#64748b;">No subjects available for this class.</p>
                        <?php endif; ?>
                    </div>

                    <div class="panel" style="margin-top:16px;">
                        <h2>Record Subject Performance - Registered Subjects</h2>
                        <p style="margin:0 0 12px 0; color:#64748b;">Manage marks, grades, and remarks for all registered subjects. Add, edit, delete, or print records.</p>
                        
                        <?php
                            $allSubjectsData = [];
                            if (!empty($studentSubjectRows)) {
                                foreach ($studentSubjectRows as $className => $subjects) {
                                    foreach ($subjects as $subject) {
                                        $subjectPerf = null;
                                        foreach ($studentSubjectPerformance as $perf) {
                                            if (strtolower(trim($perf['subject'])) === strtolower(trim($subject))) {
                                                $subjectPerf = $perf;
                                                break;
                                            }
                                        }
                                        $allSubjectsData[] = [
                                            'subject' => $subject,
                                            'perf' => $subjectPerf
                                        ];
                                    }
                                }
                            }
                        ?>
                        
                        <?php if (!empty($allSubjectsData)): ?>
                            <div style="display:flex; gap:8px; margin-bottom:12px;">
                                <button onclick="window.print()" style="padding:8px 12px; background:#3b82f6; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:13px;">
                                    <i class="fas fa-print" style="margin-right:6px;"></i>Print All Records
                                </button>
                            </div>
                            
                            <div style="overflow-x:auto; margin-bottom:16px;">
                                <table style="width:100%; border-collapse:collapse; background:white;">
                                    <thead>
                                        <tr style="background:#f7fafc; border-bottom:2px solid #e2e8f0;">
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-weight:600;">Subject Name</th>
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-weight:600;">Marks</th>
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-weight:600;">Grade</th>
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-weight:600;">Remarks</th>
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-weight:600;">Status</th>
                                            <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-weight:600;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allSubjectsData as $idx => $item): ?>
                                            <?php $subjectPerf = $item['perf']; ?>
                                            <?php $subject = $item['subject']; ?>
                                            <tr style="border-bottom:1px solid #e2e8f0; background:<?php echo $subjectPerf ? '#f0fdf4' : '#fafafa'; ?>;">
                                                <td style="border:1px solid #e2e8f0; padding:12px; font-weight:600;">
                                                    <?php echo esc($subject); ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px; text-align:center;">
                                                    <?php if ($subjectPerf && $subjectPerf['marks'] !== null): ?>
                                                        <span style="padding:6px 10px; background:#dbeafe; color:#1e40af; border-radius:4px; font-weight:600; font-size:13px;">
                                                            <?php echo esc($subjectPerf['marks']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#999; font-size:12px;">--</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px; text-align:center;">
                                                    <?php if ($subjectPerf && $subjectPerf['grade']): ?>
                                                        <span style="padding:6px 10px; background:<?php echo match($subjectPerf['grade']) { 'A' => '#dcfce7', 'B' => '#e0e7ff', 'C' => '#fef3c7', 'D' => '#fed7aa', 'F' => '#fee2e2', default => '#f3f4f6' }; ?>; color:<?php echo match($subjectPerf['grade']) { 'A' => '#166534', 'B' => '#3730a3', 'C' => '#92400e', 'D' => '#9a3412', 'F' => '#991b1b', default => '#374151' }; ?>; border-radius:4px; font-weight:600; font-size:13px;">
                                                            <?php echo esc($subjectPerf['grade']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#999; font-size:12px;">--</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px; font-size:13px;">
                                                    <?php echo $subjectPerf ? esc($subjectPerf['remarks'] ?? '--') : '--'; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px; text-align:center;">
                                                    <?php if ($subjectPerf): ?>
                                                        <span style="padding:6px 10px; background:#dcfce7; color:#15803d; border-radius:3px; font-size:12px; font-weight:600;">Recorded</span>
                                                    <?php else: ?>
                                                        <span style="padding:6px 10px; background:#f3e8ff; color:#6b21a8; border-radius:3px; font-size:12px; font-weight:600;">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:12px; text-align:center;">
                                                    <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                                        <?php if ($subjectPerf): ?>
                                                            <!-- Edit Button -->
                                                            <button type="button" onclick="document.getElementById('edit_form_<?php echo $idx; ?>').style.display='block'" style="padding:6px 10px; background:#0284c7; color:white; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600;">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <!-- Delete Button -->
                                                            <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_subject_performance">
                                                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                                                <input type="hidden" name="subject_perf_id" value="<?php echo esc($subjectPerf['id']); ?>">
                                                                <button type="submit" style="padding:6px 10px; background:#dc2626; color:white; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600;" onclick="return confirm('Delete this subject record?');">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <!-- Add Button -->
                                                            <button type="button" onclick="document.getElementById('add_form_<?php echo $idx; ?>').style.display='block'" style="padding:6px 10px; background:#16a34a; color:white; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600;">
                                                                <i class="fas fa-plus"></i> Add
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Add/Edit Forms (Hidden by default) -->
                            <?php foreach ($allSubjectsData as $idx => $item): ?>
                                <?php $subjectPerf = $item['perf']; ?>
                                <?php $subject = $item['subject']; ?>
                                
                                <!-- Add Form -->
                                <div id="add_form_<?php echo $idx; ?>" style="display:none; margin-bottom:12px; border:2px solid #16a34a; padding:16px; border-radius:6px; background:#f0fdf4;">
                                    <h4 style="margin:0 0 12px 0; color:#15803d;">Add Performance for: <strong><?php echo esc($subject); ?></strong></h4>
                                    <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>">
                                        <input type="hidden" name="action" value="save_subject_performance">
                                        <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                        <input type="hidden" name="subject" value="<?php echo esc($subject); ?>">
                                        
                                        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px;">
                                            <div>
                                                <label style="display:block; margin-bottom:6px; font-weight:600;">Marks</label>
                                                <input type="number" name="marks" placeholder="0-100" min="0" max="100" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px;">
                                            </div>
                                            <div>
                                                <label style="display:block; margin-bottom:6px; font-weight:600;">Grade</label>
                                                <select name="grade" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px;">
                                                    <option value="">Select Grade</option>
                                                    <option value="A">A - Excellent</option>
                                                    <option value="B">B - Good</option>
                                                    <option value="C">C - Average</option>
                                                    <option value="D">D - Below Average</option>
                                                    <option value="F">F - Fail</option>
                                                </select>
                                            </div>
                                            <div style="grid-column:1/-1;">
                                                <label style="display:block; margin-bottom:6px; font-weight:600;">Remarks (Optional)</label>
                                                <textarea name="remarks" placeholder="Add remarks..." style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px; min-height:60px;"></textarea>
                                            </div>
                                        </div>
                                        <div style="display:flex; gap:10px; margin-top:12px;">
                                            <button type="submit" style="padding:10px 16px; background:#16a34a; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                                                <i class="fas fa-save"></i> Save Record
                                            </button>
                                            <button type="button" onclick="document.getElementById('add_form_<?php echo $idx; ?>').style.display='none'" style="padding:10px 16px; background:#999; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Edit Form -->
                                <?php if ($subjectPerf): ?>
                                    <div id="edit_form_<?php echo $idx; ?>" style="display:none; margin-bottom:12px; border:2px solid #0284c7; padding:16px; border-radius:6px; background:#eff6ff;">
                                        <h4 style="margin:0 0 12px 0; color:#0c4a6e;">Edit Performance for: <strong><?php echo esc($subject); ?></strong></h4>
                                        <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>">
                                            <input type="hidden" name="action" value="save_subject_performance">
                                            <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                            <input type="hidden" name="subject_perf_id" value="<?php echo esc($subjectPerf['id']); ?>">
                                            <input type="hidden" name="subject" value="<?php echo esc($subject); ?>">
                                            
                                            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px;">
                                                <div>
                                                    <label style="display:block; margin-bottom:6px; font-weight:600;">Marks</label>
                                                    <input type="number" name="marks" value="<?php echo esc($subjectPerf['marks'] ?? ''); ?>" min="0" max="100" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px;">
                                                </div>
                                                <div>
                                                    <label style="display:block; margin-bottom:6px; font-weight:600;">Grade</label>
                                                    <select name="grade" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px;">
                                                        <option value="">Select Grade</option>
                                                        <option value="A" <?php echo ($subjectPerf['grade'] === 'A') ? 'selected' : ''; ?>>A - Excellent</option>
                                                        <option value="B" <?php echo ($subjectPerf['grade'] === 'B') ? 'selected' : ''; ?>>B - Good</option>
                                                        <option value="C" <?php echo ($subjectPerf['grade'] === 'C') ? 'selected' : ''; ?>>C - Average</option>
                                                        <option value="D" <?php echo ($subjectPerf['grade'] === 'D') ? 'selected' : ''; ?>>D - Below Average</option>
                                                        <option value="F" <?php echo ($subjectPerf['grade'] === 'F') ? 'selected' : ''; ?>>F - Fail</option>
                                                    </select>
                                                </div>
                                                <div style="grid-column:1/-1;">
                                                    <label style="display:block; margin-bottom:6px; font-weight:600;">Remarks (Optional)</label>
                                                    <textarea name="remarks" placeholder="Add remarks..." style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:4px; min-height:60px;"><?php echo esc($subjectPerf['remarks'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div style="display:flex; gap:10px; margin-top:12px;">
                                                <button type="submit" style="padding:10px 16px; background:#0284c7; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                                                    <i class="fas fa-save"></i> Update Record
                                                </button>
                                                <button type="button" onclick="document.getElementById('edit_form_<?php echo $idx; ?>').style.display='none'" style="padding:10px 16px; background:#999; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="background:#f3e8ff; border:1px solid #9333ea; padding:12px; border-radius:6px; text-align:center;">
                                <p style="margin:0; color:#6b21a8; font-weight:600;">
                                    <i class="fas fa-info-circle" style="margin-right:6px;"></i>
                                    No subjects enrolled for this class yet.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel" style="margin-top:16px;">
                        <h2>Edit All Subject Performance</h2>
                        <p style="margin:0 0 12px 0; color:#64748b;">View and edit marks, grades, and remarks for all enrolled subjects.</p>
                        
                        <?php if (!empty($studentSubjectRows)): ?>
                            <?php foreach ($studentSubjectRows as $className => $subjects): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <?php $subjectPerf = null; ?>
                                    <?php foreach ($studentSubjectPerformance as $perf): ?>
                                        <?php if (strtolower(trim($perf['subject'])) === strtolower(trim($subject))): ?>
                                            <?php $subjectPerf = $perf; ?>
                                            <?php break; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>" style="margin-bottom:12px; border:1px solid #e2e8f0; padding:12px; border-radius:6px;">
                                        <input type="hidden" name="action" value="<?php echo $subjectPerf ? 'save_subject_performance' : 'save_subject_performance'; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                        <?php if ($subjectPerf): ?>
                                            <input type="hidden" name="subject_perf_id" value="<?php echo esc($subjectPerf['id']); ?>">
                                        <?php endif; ?>
                                        
                                        <div style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:10px; align-items:flex-end;">
                                            <div>
                                                <label style="display:block; margin-bottom:4px; font-weight:600;">Subject</label>
                                                <input type="hidden" name="subject" value="<?php echo esc($subject); ?>">
                                                <input type="text" value="<?php echo esc($subject); ?>" disabled style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:4px; background:#f7fafc;">
                                            </div>
                                            <div>
                                                <label style="display:block; margin-bottom:4px; font-weight:600;">Marks</label>
                                                <input type="number" name="marks" value="<?php echo esc($subjectPerf['marks'] ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:4px;">
                                            </div>
                                            <div>
                                                <label style="display:block; margin-bottom:4px; font-weight:600;">Grade</label>
                                                <select name="grade" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:4px;">
                                                    <option value="">--</option>
                                                    <option value="A"<?php echo (($subjectPerf['grade'] ?? '') === 'A') ? ' selected' : ''; ?>>A</option>
                                                    <option value="B"<?php echo (($subjectPerf['grade'] ?? '') === 'B') ? ' selected' : ''; ?>>B</option>
                                                    <option value="C"<?php echo (($subjectPerf['grade'] ?? '') === 'C') ? ' selected' : ''; ?>>C</option>
                                                    <option value="D"<?php echo (($subjectPerf['grade'] ?? '') === 'D') ? ' selected' : ''; ?>>D</option>
                                                    <option value="F"<?php echo (($subjectPerf['grade'] ?? '') === 'F') ? ' selected' : ''; ?>>F</option>
                                                </select>
                                            </div>
                                            <div>
                                                <button type="submit" style="padding:8px 12px; background:#0284c7; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
                                                    <?php echo $subjectPerf ? 'Update' : 'Add'; ?>
                                                </button>
                                                <?php if ($subjectPerf): ?>
                                                    <button type="submit" style="padding:8px 12px; background:#dc2626; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; margin-left:4px;" onclick="this.form.action='admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>'; this.form.elements.action.value='delete_subject_performance'; return confirm('Delete this subject performance?');">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-top:10px;">
                                            <label style="display:block; margin-bottom:4px; font-weight:600;">Remarks</label>
                                            <input type="text" name="remarks" value="<?php echo esc($subjectPerf['remarks'] ?? ''); ?>" placeholder="e.g., Excellent, Needs improvement" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:4px;">
                                        </div>
                                    </form>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#64748b;">No subjects available for this class.</p>
                        <?php endif; ?>
                    </div>

                    <div class="panel" style="margin-top:16px;">
                        <h2>Billing Information</h2>
                        <p style="margin:0 0 6px 0; color:#64748b;">Current bills: <?php echo count($studentBills); ?></p>
                        <p style="margin:0 0 12px 0; color:#64748b;">Fees = 460,000 Tshs for 3 months, Transport = 150,000 Tshs for 3 months, Uniform = 120,000 Tshs.</p>
                        <?php if (!empty($studentBills)): ?>
                            <div style="overflow-x:auto; margin-bottom:16px;">
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Bill Type</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Months</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Amount</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Account #</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Reference #</th>
                                            <th style="border:1px solid #e2e8f0; padding:10px; text-align:left; background:#f7fafc;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($studentBills as $bill): ?>
                                            <tr>
                                                <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['type'] ?? ''); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;">
                                                    <?php
                                                        $monthItems = array_values(array_filter(array_map('trim', preg_split('/[,;\n|]+/', (string)($bill['months'] ?? ''))), static function ($month) {
                                                            return $month !== '';
                                                        }));
                                                    ?>
                                                    <?php if (!empty($monthItems)): ?>
                                                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                                            <?php foreach ($monthItems as $monthItem): ?>
                                                                <span style="padding:4px 8px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:0.82em;">
                                                                    <?php echo esc($monthItem); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color:#999;">No month selected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc(getBillAmountLabel($bill['type'] ?? '', $bill['months'] ?? '')); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['account_number'] ?? ''); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['reference_number'] ?? ''); ?></td>
                                                <td style="border:1px solid #e2e8f0; padding:10px;"><?php echo esc($bill['status'] ?? 'Pending'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($studentBills as $bill): ?>
                            <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>" style="margin-bottom:12px; border:1px solid #e2e8f0; padding:12px; border-radius:12px;">
                                <input type="hidden" name="action" value="save_student_billing">
                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                <input type="hidden" name="bill_id" value="<?php echo esc($bill['id']); ?>">
                                <div class="field-row">
                                    <label>Bill Type
                                        <select name="bill_type">
                                            <option value="Fees"<?php echo (($bill['type'] ?? '') === 'Fees') ? ' selected' : ''; ?>>Fees</option>
                                            <option value="Transport"<?php echo (($bill['type'] ?? '') === 'Transport') ? ' selected' : ''; ?>>Transport</option>
                                            <option value="Uniform"<?php echo (($bill['type'] ?? '') === 'Uniform') ? ' selected' : ''; ?>>Uniform</option>
                                        </select>
                                    </label>
                                    <label>Months
                                        <input type="text" name="bill_months" value="<?php echo esc($bill['months'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Account Number
                                        <input type="text" name="account_number" value="<?php echo esc($bill['account_number'] ?? ''); ?>">
                                    </label>
                                    <label>Reference Number
                                        <input type="text" name="reference_number" value="<?php echo esc($bill['reference_number'] ?? ''); ?>">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Status
                                        <select name="status">
                                            <option value="Pending"<?php echo (($bill['status'] ?? '') === 'Pending') ? ' selected' : ''; ?>>Pending</option>
                                            <option value="Paid"<?php echo (($bill['status'] ?? '') === 'Paid') ? ' selected' : ''; ?>>Paid</option>
                                            <option value="Not Paid"<?php echo (($bill['status'] ?? '') === 'Not Paid') ? ' selected' : ''; ?>>Not Paid</option>
                                        </select>
                                    </label>
                                    <div></div>
                                </div>
                                <div class="actions">
                                    <button type="submit">Save Billing</button>
                                    <button type="submit" formaction="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>" formmethod="post" onclick="this.form.action='admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>'; this.form.elements.action.value='delete_student_billing'; return confirm('Delete this billing record?');">Delete Billing</button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                        <?php if (empty($studentBills)): ?>
                            <form method="post" action="admin.php?student_id=<?php echo esc($selectedStudent['id']); ?>">
                                <input type="hidden" name="action" value="save_student_billing">
                                <input type="hidden" name="student_id" value="<?php echo esc($selectedStudent['id']); ?>">
                                <div class="field-row">
                                    <label>Bill Type
                                        <select name="bill_type">
                                            <option value="Fees">Fees</option>
                                            <option value="Transport">Transport</option>
                                            <option value="Uniform">Uniform</option>
                                        </select>
                                    </label>
                                    <label>Months
                                        <input type="text" name="bill_months" value="">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Account Number
                                        <input type="text" name="account_number" value="">
                                    </label>
                                    <label>Reference Number
                                        <input type="text" name="reference_number" value="">
                                    </label>
                                </div>
                                <div class="field-row">
                                    <label>Status
                                        <select name="status">
                                            <option value="Pending">Pending</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Not Paid">Not Paid</option>
                                        </select>
                                    </label>
                                    <div></div>
                                </div>
                                <div class="actions">
                                    <button type="submit">Add Billing</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="panel panel-empty">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h2>No student selected</h2>
                            <p>Choose a student from the list or create a fresh record from here.</p>
                            <div class="empty-actions">
                                <a class="button" href="admin.php?action=add"><i class="fas fa-plus"></i> Add New Student</a>
                                <a class="button-secondary" href="admin.php?view=student&download_template=1"><i class="fas fa-file-download"></i> Download Template</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle && navMenu) {
            navToggle.addEventListener('click', function () {
                navMenu.classList.toggle('active');
                navToggle.classList.toggle('active');
            });
        }

        const studentSearch = document.getElementById('studentSearch');
        if (studentSearch) {
            studentSearch.addEventListener('input', function () {
                var filter = this.value.toLowerCase();
                document.querySelectorAll('.student-item').forEach(function (item) {
                    var text = item.textContent.toLowerCase();
                    item.style.display = text.indexOf(filter) !== -1 ? 'block' : 'none';
                });
            });
        }

        const trigger = document.getElementById('adminProfileTrigger');
        const panel = document.getElementById('adminProfilePanel');
        const cancel = document.getElementById('adminProfileCancel');

        if (trigger && panel) {
            trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = panel.classList.toggle('active');
                trigger.setAttribute('aria-expanded', String(isOpen));
            });

            cancel?.addEventListener('click', function () {
                panel.classList.remove('active');
                trigger.setAttribute('aria-expanded', 'false');
            });

            document.addEventListener('click', function (event) {
                if (!panel.contains(event.target) && !trigger.contains(event.target)) {
                    panel.classList.remove('active');
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    </script>
</body>
</html>
