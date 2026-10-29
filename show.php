<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

session_start();

$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(404);
    $student = false;
} else {
    try {
        $studentQuery = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $studentQuery->execute(['id' => $studentId]);
        $student = $studentQuery->fetch();

        if ($student === false) {
            http_response_code(404);
        }
    } catch (PDOException $exception) {
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
}

$pageTitle = $student === false ? 'Student not found' : 'Student details';
require __DIR__ . '/partials/header.php';
?>
<?php if ($student === false): ?>
    <section class="card error-state">
        <h1>Student not found</h1>
        <p>The requested student record does not exist.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
<?php else: ?>
    <section class="page-heading">
        <div>
            <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
            <p class="page-description">Student record details.</p>
        </div>
        <a class="button" href="edit.php?id=<?= (int) $student['id'] ?>">Edit student</a>
    </section>

    <section class="card details-card" aria-label="Student details">
        <dl class="details-list">
            <div class="detail-item">
                <dt>Email</dt>
                <dd><?= e($student['email']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Phone</dt>
                <dd><?= e($student['phone'] ?? '') ?: '—' ?></dd>
            </div>
            <div class="detail-item">
                <dt>Course</dt>
                <dd><?= e($student['course']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Enrolled on</dt>
                <dd><?= e(date('d M Y', strtotime($student['enrolled_on']))) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Created</dt>
                <dd><?= e(date('d M Y H:i', strtotime($student['created_at']))) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Last updated</dt>
                <dd><?= e(date('d M Y H:i', strtotime($student['updated_at']))) ?></dd>
            </div>
        </dl>
        <a class="action-link" href="index.php">Back to students</a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
