<?php
$student = $student ?? [];
$errors = $errors ?? [];
$courses = $courses ?? [];
$formAction = $formAction ?? '';
$submitLabel = $submitLabel ?? 'Save student';
$fieldValue = static fn (string $field): string => e((string) ($student[$field] ?? ''));
?>
<?php if (isset($errors['general'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
<?php endif; ?>

<form class="student-form" method="post" action="<?= e($formAction) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" maxlength="60" required
                value="<?= $fieldValue('first_name') ?>" autocomplete="given-name"
                aria-invalid="<?= isset($errors['first_name']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['first_name'])): ?>
                <p class="field-error"><?= e($errors['first_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" maxlength="60" required
                value="<?= $fieldValue('last_name') ?>" autocomplete="family-name"
                aria-invalid="<?= isset($errors['last_name']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['last_name'])): ?>
                <p class="field-error"><?= e($errors['last_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="254" required
                value="<?= $fieldValue('email') ?>" autocomplete="email"
                aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['email'])): ?>
                <p class="field-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="optional">(optional)</span></label>
            <input id="phone" name="phone" type="tel" maxlength="11"
                value="<?= $fieldValue('phone') ?>" autocomplete="tel"
                aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['phone'])): ?>
                <p class="field-error"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required
                aria-invalid="<?= isset($errors['course']) ? 'true' : 'false' ?>">
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?>
                <p class="field-error"><?= e($errors['course']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="enrolled_on">Enrolled on</label>
            <input id="enrolled_on" name="enrolled_on" type="date" required
                value="<?= $fieldValue('enrolled_on') ?>"
                aria-invalid="<?= isset($errors['enrolled_on']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['enrolled_on'])): ?>
                <p class="field-error"><?= e($errors['enrolled_on']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button-secondary" href="index.php">Cancel</a>
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
    </div>
</form>
