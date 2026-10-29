<?php

function studentCourses(PDO $connection): array
{
    $courseColumn = $connection
        ->query("SHOW COLUMNS FROM students LIKE 'course'")
        ->fetch();
    $courseType = is_array($courseColumn) ? (string) ($courseColumn['Type'] ?? '') : '';

    if (preg_match('/^enum\((.*)\)$/i', $courseType, $enumMatch) !== 1) {
        throw new RuntimeException('The students.course column must be an ENUM.');
    }

    return str_getcsv($enumMatch[1], ',', "'", '\\');
}

function validateStudent(PDO $connection, array $student, array $courses, ?int $excludeId = null): array
{
    $errors = [];

    foreach (['first_name', 'last_name'] as $nameField) {
        if (preg_match('/^(?=.*\p{L})[\p{L} -]{2,60}$/u', $student[$nameField]) !== 1) {
            $errors[$nameField] = 'Enter 2–60 letters, spaces, or hyphens.';
        }
    }

    if (filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $emailSql = 'SELECT id FROM students WHERE email = :email';
        if ($excludeId !== null) {
            $emailSql .= ' AND id <> :exclude_id';
        }
        $emailSql .= ' LIMIT 1';

        $emailCheck = $connection->prepare($emailSql);
        $emailParams = ['email' => $student['email']];
        if ($excludeId !== null) {
            $emailParams['exclude_id'] = $excludeId;
        }
        $emailCheck->execute($emailParams);

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

    return $errors;
}
