<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

session_start();

try {
    $students = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name, first_name'
    )->fetchAll();
} catch (PDOException $exception) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't connect to the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="index.php">Try again</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Students';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Students</h1>
        <p class="page-description">Manage student enrolments and contact details.</p>
    </div>
    <a class="button" href="create.php">Add student</a>
</section>

<section class="card" aria-label="Student records">
    <?php if ($students === []): ?>
        <div class="empty-state">
            <h2>No students yet</h2>
            <p>Add a student to start building your records.</p>
            <a class="button" href="create.php">Add student</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Course</th>
                        <th scope="col">Enrolled date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="student-name">
                                <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                            </td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['course']) ?></td>
                            <td><?= e(date('d M Y', strtotime($student['enrolled_on']))) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="action-link" href="show.php?id=<?= (int) $student['id'] ?>">View</a>
                                    <a class="action-link" href="edit.php?id=<?= (int) $student['id'] ?>">Edit</a>
                                    <form class="inline-form" method="post" action="delete.php"
                                        data-student-name="<?= e($student['first_name'] . ' ' . $student['last_name']) ?>"
                                        onsubmit="return confirm('Delete ' + this.dataset.studentName + '?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                        <button class="action-link delete" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
