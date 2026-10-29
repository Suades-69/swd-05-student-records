<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    $pageTitle = 'Method not allowed';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Method not allowed</h1>
        <p>Student records can only be deleted by submitting the delete form.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$postedCsrfToken = $_POST['csrf_token'] ?? null;
if (!isValidCsrfToken(is_string($postedCsrfToken) ? $postedCsrfToken : null)) {
    http_response_code(403);
    $pageTitle = 'Request rejected';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Request rejected</h1>
        <p>Your form session expired. Return to the student list and try again.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$studentId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(400);
    $pageTitle = 'Invalid request';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Invalid request</h1>
        <p>A valid student record must be selected for deletion.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

try {
    $delete = db()->prepare('DELETE FROM students WHERE id = :id');
    $delete->execute(['id' => $studentId]);

    if ($delete->rowCount() === 0) {
        flash('error', 'Student not found');
    } else {
        flash('success', 'Student deleted');
    }

    redirect('index.php');
} catch (PDOException $exception) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't update the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
