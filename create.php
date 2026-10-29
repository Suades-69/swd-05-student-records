<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => date('Y-m-d'),
];
$errors = [];

try {
    $connection = db();
    $courseColumn = $connection
        ->query("SHOW COLUMNS FROM students LIKE 'course'")
        ->fetch();
    $courseType = is_array($courseColumn) ? (string) ($courseColumn['Type'] ?? '') : '';

    if (preg_match('/^enum\((.*)\)$/i', $courseType, $enumMatch) !== 1) {
        throw new RuntimeException('The students.course column must be an ENUM.');
    }

    $courses = str_getcsv($enumMatch[1], ',', "'", '\\');

    if ($requestMethod === 'POST') {
        foreach (array_keys($student) as $field) {
            $postedValue = $_POST[$field] ?? null;
            $student[$field] = is_string($postedValue) ? trim($postedValue) : '';
        }

        $postedCsrfToken = $_POST['csrf_token'] ?? null;
        if (!isValidCsrfToken(is_string($postedCsrfToken) ? $postedCsrfToken : null)) {
            $errors['general'] = 'Your form session expired. Please review the form and submit it again.';
        }

        foreach (['first_name', 'last_name'] as $nameField) {
            $name = $student[$nameField];
            if (preg_match('/^(?=.*\p{L})[\p{L} -]{2,60}$/u', $name) !== 1) {
                $errors[$nameField] = 'Enter 2–60 letters, spaces, or hyphens.';
            }
        }

        if (filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $emailCheck = $connection->prepare(
                'SELECT id FROM students WHERE email = :email LIMIT 1'
            );
            $emailCheck->execute(['email' => $student['email']]);
            if ($emailCheck->fetch() !== false) {
                $errors['email'] = 'A student with this email address already exists.';
            }
        }

        if ($student['phone'] !== '' && preg_match('/\A0[789][01]\d{8}\z/', $student['phone']) !== 1) {
            $errors['phone'] = 'Enter a valid 11-digit phone number beginning with 07, 08, or 09.';
        }

        if (!in_array($student['course'], $courses, true)) {
            $errors['course'] = 'Select one of the available courses.';
        }

        $dateParts = explode('-', $student['enrolled_on']);
        $validDate = count($dateParts) === 3
            && preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $student['enrolled_on']) === 1
            && checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0]);
        if (!$validDate || $student['enrolled_on'] > date('Y-m-d')) {
            $errors['enrolled_on'] = 'Enter a valid enrolment date that is not in the future.';
        }

        if ($errors === []) {
            $insert = $connection->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );
            $insert->execute([
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'] === '' ? null : $student['phone'],
                'course' => $student['course'],
                'enrolled_on' => $student['enrolled_on'],
            ]);

            flash('success', 'Student added');
            redirect('index.php');
        }
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000' && $requestMethod === 'POST') {
        $errors['email'] = 'A student with this email address already exists.';
    } else {
        http_response_code(503);
        $pageTitle = 'Database unavailable';
        require __DIR__ . '/partials/header.php';
        ?>
        <section class="card error-state">
            <h1>Student records are temporarily unavailable</h1>
            <p>We couldn't access the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
            <a class="button" href="create.php">Try again</a>
        </section>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }
} catch (RuntimeException $exception) {
    http_response_code(500);
    $pageTitle = 'Form unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Student form is unavailable</h1>
        <p>The course options could not be loaded. Please check the students table configuration.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Add student';
$formAction = 'create.php';
$submitLabel = 'Add student';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Add student</h1>
        <p class="page-description">Enter the student's details below.</p>
    </div>
</section>

<section class="card form-card">
    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
