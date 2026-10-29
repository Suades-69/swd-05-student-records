<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

session_start();

$search = $_GET['q'] ?? '';
$search = is_string($search) ? trim($search) : '';
$selectedCourse = $_GET['course'] ?? '';
$selectedCourse = is_string($selectedCourse) ? $selectedCourse : '';
$requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentPage = $requestedPage === false ? 1 : $requestedPage;
$perPage = 10;

try {
    $connection = db();
    $courses = studentCourses($connection);

    if ($selectedCourse !== '' && !in_array($selectedCourse, $courses, true)) {
        $selectedCourse = '';
    }

    $where = [];
    $filterParams = [];

    if ($search !== '') {
        $where[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email)';
        $searchPattern = '%' . $search . '%';
        $filterParams = [
            'first_name' => $searchPattern,
            'last_name' => $searchPattern,
            'email' => $searchPattern,
        ];
    }

    if ($selectedCourse !== '') {
        $where[] = 'course = :course';
        $filterParams['course'] = $selectedCourse;
    }

    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
    $countQuery = $connection->prepare('SELECT COUNT(*) FROM students' . $whereSql);
    $countQuery->execute($filterParams);
    $totalStudents = (int) $countQuery->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    $studentQuery = $connection->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students'
        . $whereSql
        . ' ORDER BY last_name, first_name
           LIMIT :limit OFFSET :offset'
    );
    foreach ($filterParams as $name => $value) {
        $studentQuery->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $studentQuery->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $studentQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
    $studentQuery->execute();
    $students = $studentQuery->fetchAll();
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
} catch (RuntimeException $exception) {
    http_response_code(500);
    $pageTitle = 'Student list unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h1>Student list is unavailable</h1>
        <p>The course options could not be loaded. Please check the students table configuration.</p>
        <a class="button" href="index.php">Try again</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Students';
$filterParamsForLinks = [];
if ($search !== '') {
    $filterParamsForLinks['q'] = $search;
}
if ($selectedCourse !== '') {
    $filterParamsForLinks['course'] = $selectedCourse;
}
$pageUrl = static function (int $page) use ($filterParamsForLinks): string {
    return 'index.php?' . http_build_query($filterParamsForLinks + ['page' => $page]);
};
$firstStudent = $totalStudents === 0 ? 0 : $offset + 1;
$lastStudent = min($offset + $perPage, $totalStudents);
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
    <form class="filters" method="get" action="index.php">
        <div class="filter-search">
            <label for="q">Search students</label>
            <input id="q" name="q" type="search" value="<?= e($search) ?>"
                placeholder="Name or email">
        </div>
        <div class="filter-course">
            <label for="course">Course</label>
            <select id="course" name="course">
                <option value="">All courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit">Search</button>
        <?php if ($search !== '' || $selectedCourse !== ''): ?>
            <a class="filter-clear" href="index.php">Clear filters</a>
        <?php endif; ?>
    </form>

    <?php if ($students === []): ?>
        <div class="empty-state">
            <h2><?= $totalStudents === 0 && $search === '' && $selectedCourse === '' ? 'No students yet' : 'No matching students' ?></h2>
            <p>
                <?= $totalStudents === 0 && $search === '' && $selectedCourse === ''
                    ? 'Add a student to start building your records.'
                    : 'Try changing or clearing the search and course filters.' ?>
            </p>
            <?php if ($totalStudents === 0 && $search === '' && $selectedCourse === ''): ?>
                <a class="button" href="create.php">Add student</a>
            <?php endif; ?>
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
        <div class="table-footer">
            <p class="result-count">
                Showing <?= $firstStudent ?>–<?= $lastStudent ?> of <?= $totalStudents ?> students
            </p>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Student list pages">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link" href="<?= e($pageUrl($currentPage - 1)) ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <?php if ($page === $currentPage): ?>
                            <span class="page-link current" aria-current="page"><?= $page ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e($pageUrl($page)) ?>"><?= $page ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link" href="<?= e($pageUrl($currentPage + 1)) ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
