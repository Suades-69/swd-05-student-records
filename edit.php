<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

session_start();

$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$errors = [];
$student = [];

if ($studentId === false || $studentId === null) {
    http_response_code(404);
    $pageTitle = 'Student not found';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Student not found</h1>
        <p>The requested student record does not exist.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $connection = db();
    $studentQuery = $connection->prepare(
        'SELECT id, first_name, last_name, email, phone, course, enrolled_on
         FROM students
         WHERE id = :id'
    );
    $studentQuery->execute(['id' => $studentId]);
    $existingStudent = $studentQuery->fetch();

    if ($existingStudent === false) {
        http_response_code(404);
        $pageTitle = 'Student not found';
        require __DIR__ . '/partials/header.php';
        ?>
        <section class="card error-state">
            <h1>Student not found</h1>
            <p>The requested student record does not exist.</p>
            <a class="button" href="index.php">Return to students</a>
        </section>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }

    $student = $existingStudent;
    $student['phone'] = $student['phone'] ?? '';
    $student['enrolled_on'] = (string) $student['enrolled_on'];
    $courses = studentCourses($connection);

    if ($requestMethod === 'POST') {
        foreach (['first_name', 'last_name', 'email', 'phone', 'course', 'enrolled_on'] as $field) {
            $postedValue = $_POST[$field] ?? null;
            $student[$field] = is_string($postedValue) ? trim($postedValue) : '';
        }

        $postedCsrfToken = $_POST['csrf_token'] ?? null;
        if (!isValidCsrfToken(is_string($postedCsrfToken) ? $postedCsrfToken : null)) {
            $errors['general'] = 'Your form session expired. Please review the form and submit it again.';
        }

        $errors = array_merge($errors, validateStudent($connection, $student, $courses, $studentId));

        if ($errors === []) {
            $update = $connection->prepare(
                'UPDATE students
                 SET first_name = :first_name,
                     last_name = :last_name,
                     email = :email,
                     phone = :phone,
                     course = :course,
                     enrolled_on = :enrolled_on
                 WHERE id = :id'
            );
            $update->execute([
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'] === '' ? null : $student['phone'],
                'course' => $student['course'],
                'enrolled_on' => $student['enrolled_on'],
                'id' => $studentId,
            ]);

            flash('success', 'Student updated');
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
            <a class="button" href="index.php">Return to students</a>
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

$pageTitle = 'Edit student';
$formAction = 'edit.php?id=' . $studentId;
$submitLabel = 'Save changes';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Edit student</h1>
        <p class="page-description">Update the student's details below.</p>
    </div>
</section>

<section class="card form-card">
    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
