<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

session_start();

$confirmed = ($_GET['confirm'] ?? null) === 'yes';
$inserted = 0;
$error = null;

if ($confirmed) {
    try {
        $connection = db();
        $courses = studentCourses($connection);
        if ($courses === []) {
            throw new RuntimeException('No course options are configured.');
        }

        $firstNames = [
            'Adaeze', 'Chinedu', 'Folake', 'Ifeanyi', 'Kemi',
            'Ngozi', 'Oluwaseun', 'Tunde', 'Yemi', 'Zainab',
        ];
        $lastNames = ['Adebayo', 'Okafor', 'Bello', 'Nwosu', 'Ogunleye'];
        $emailCheck = $connection->prepare(
            'SELECT id FROM students WHERE email = :email LIMIT 1'
        );
        $insert = $connection->prepare(
            'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
             VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
        );

        foreach ($firstNames as $firstIndex => $firstName) {
            foreach ($lastNames as $lastIndex => $lastName) {
                $number = ($firstIndex * count($lastNames)) + $lastIndex + 1;
                $email = sprintf('student.seed.%02d@example.test', $number);
                $emailCheck->execute(['email' => $email]);
                if ($emailCheck->fetch() !== false) {
                    continue;
                }

                $result = $insert->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => sprintf('080%08d', $number),
                    'course' => $courses[($number - 1) % count($courses)],
                    'enrolled_on' => date('Y-m-d', strtotime('-' . (30 + ($number * 19)) . ' days')),
                ]);
                if ($result && $insert->rowCount() > 0) {
                    $inserted++;
                }
            }
        }
    } catch (PDOException $exception) {
        http_response_code(503);
        $error = 'We could not access the student database. Please make sure MySQL is running and try again.';
    } catch (RuntimeException $exception) {
        http_response_code(500);
        $error = 'Seed data could not be prepared. Please check the students table and course options.';
    }
}

$pageTitle = 'Seed test students';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Seed test students</h1>
        <p class="page-description">Insert 50 fictional student records to test search and pagination.</p>
    </div>
</section>

<section class="card seed-card">
    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php elseif ($confirmed): ?>
        <div class="alert alert-success" role="status">
            Seed complete. <?= $inserted ?> new fictional student records inserted. Existing seed email addresses were skipped.
        </div>
        <a class="button" href="index.php">View students</a>
    <?php else: ?>
        <p>This will add 50 fictional students using the configured course options. Running it again will not duplicate the seed email addresses.</p>
        <a class="button" href="seed.php?confirm=yes">Insert 50 test students</a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
